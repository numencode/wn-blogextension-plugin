<?php

declare(strict_types=1);

namespace NumenCode\BlogExtension\Tests\Models;

use Db;
use NumenCode\BlogExtension\Models\File;
use NumenCode\BlogExtension\Models\Picture;
use NumenCode\BlogExtension\Models\Settings;
use NumenCode\BlogExtension\Models\Tag;
use NumenCode\BlogExtension\Tests\BlogExtensionTestCase;
use Winter\Blog\Models\Post;
use Winter\Storm\Exception\ValidationException;

class ModelsTest extends BlogExtensionTestCase
{
    public function testTagSlugIsGeneratedAndUnique(): void
    {
        $tag = $this->makeTag('Čaj in kava');
        $this->assertSame('caj-in-kava', $tag->slug);

        // Sluggable keeps slugs unique, also explicit ones
        $this->assertSame('caj-in-kava-2', Tag::create(['name' => 'Other', 'slug' => 'caj-in-kava'])->slug);
    }

    public function testTagValidation(): void
    {
        $this->expectException(ValidationException::class);

        Tag::create(['name' => '', 'slug' => 'empty']);
    }

    public function testTagSlugMayOnlyContainUrlCharacters(): void
    {
        $this->expectException(ValidationException::class);

        Tag::create(['name' => 'Spaces', 'slug' => 'with spaces']);
    }

    public function testFindBySlug(): void
    {
        $tag = $this->makeTag('Coffee');

        $this->assertSame($tag->id, Tag::findBySlug('coffee')?->id);
        $this->assertNull(Tag::findBySlug('tea'));
        $this->assertNull(Tag::findBySlug(''));
        $this->assertNull(Tag::findBySlug(null));
    }

    public function testPostRelationsAreAddedNotReplaced(): void
    {
        $post = new Post();

        $this->assertArrayHasKey('pictures', $post->hasMany);
        $this->assertArrayHasKey('files', $post->hasMany);
        $this->assertArrayHasKey('tags', $post->belongsToMany);
        $this->assertArrayHasKey('categories', $post->belongsToMany, "Winter.Blog's own relations stay");
        $this->assertArrayHasKey('featured_images', $post->attachMany);
    }

    public function testPicturesAndFilesAreSavedInOrderAndPublishedByDefault(): void
    {
        $post = $this->makePost('Gallery', [], [
            ['title' => 'One', 'picture' => '/one.jpg'],
            ['title' => 'Two', 'picture' => '/two.jpg', 'is_published' => false],
        ]);
        $post->files_list = [['title' => 'Doc', 'file' => '/doc.pdf']];
        $post->save();

        $pictures = Picture::withUnpublished()->where('post_id', $post->id)->orderBy('sort_order')->get();
        $this->assertSame(['One', 'Two'], $pictures->pluck('title')->all());
        $this->assertSame([true, false], $pictures->pluck('is_published')->all());
        $this->assertSame(1, File::where('post_id', $post->id)->count());

        $this->assertTrue((new Picture())->is_published, 'New pictures are published by default');
        $this->assertTrue((new File())->is_published, 'New files are published by default');
    }

    public function testItemsAreUpdatedNotRecreated(): void
    {
        $post = $this->makePost('Update', [], [['title' => 'One', 'picture' => '/one.jpg']]);
        $id = $post->pictures->first()->id;

        $post->pictures_list = [['id' => $id, 'title' => 'Renamed', 'picture' => '/one.jpg'], ['title' => 'New', 'picture' => '/new.jpg']];
        $post->save();

        $pictures = Picture::where('post_id', $post->id)->orderBy('sort_order')->get();
        $this->assertSame($id, $pictures->first()->id);
        $this->assertSame(['Renamed', 'New'], $pictures->pluck('title')->all());
    }

    public function testPictureRequiresAPicture(): void
    {
        $post = $this->makePost('Validation');

        try {
            $post->pictures_list = [['title' => 'No picture']];
            $post->save();
            $this->fail('A picture without a picture path must not validate');
        } catch (ValidationException $e) {
            $this->assertStringStartsWith('Item 1:', $e->getMessage());
        }
    }

    public function testPrimaryPictureIsTheFirstPublishedPictureForGuests(): void
    {
        $post = $this->makePost('Primary', [], [
            ['title' => 'Hidden', 'picture' => '/hidden.jpg', 'is_published' => false],
            ['title' => 'Visible', 'picture' => '/visible.jpg'],
        ]);

        $this->assertSame('Visible', Post::find($post->id)->primaryPicture?->title);
        $this->assertSame('Visible', Post::find($post->id)->primary_picture?->title);
        $this->assertSame(['Visible'], Post::find($post->id)->pictures->pluck('title')->all());

        $this->loginBackendUser([], true);
        $this->assertSame('Hidden', Post::find($post->id)->primaryPicture?->title, 'Backend users see unpublished pictures');

        $this->assertNull($this->makePost('No pictures')->primaryPicture);
    }

    public function testDeletingAPostDeletesItsPicturesFilesAndTagLinks(): void
    {
        $tag = $this->makeTag('Kept');
        $post = $this->makePost('Delete me', [], [['title' => 'P', 'picture' => '/p.jpg']], [$tag]);
        $post->files_list = [['title' => 'F', 'file' => '/f.pdf']];
        $post->save();

        $post->delete();

        $this->assertSame(0, Picture::withUnpublished()->where('post_id', $post->id)->count());
        $this->assertSame(0, File::withUnpublished()->where('post_id', $post->id)->count());
        $this->assertSame(0, Db::table('numencode_blogextension_posts_tags')->where('post_id', $post->id)->count());
        $this->assertNotNull(Tag::find($tag->id), 'Tags stay');
    }

    public function testTagsAreOrderedByName(): void
    {
        $post = $this->makePost('Tagged', [], [], [$this->makeTag('Zeta'), $this->makeTag('Alpha')]);

        $this->assertSame(['Alpha', 'Zeta'], $post->tags->pluck('name')->all());
    }

    public function testFeaturesAreEnabledByDefault(): void
    {
        foreach (array_keys(Settings::FEATURES) as $feature) {
            $this->assertTrue(Settings::isFeatureEnabled($feature), $feature);
        }

        Settings::set('extension_files', false);
        $this->assertFalse(Settings::isFeatureEnabled('files'));
        $this->assertFalse(Settings::isFeatureEnabled('unknown'));
    }

    public function testCanUseFeatureNeedsThePermission(): void
    {
        $this->assertFalse(Settings::canUseFeature('tags'), 'Nobody logged in');

        $this->loginBackendUser(['numencode.blogextension.access_tags' => 1]);
        $this->assertTrue(Settings::canUseFeature('tags'));
        $this->assertFalse(Settings::canUseFeature('pictures'));

        Settings::set('extension_tags', false);
        $this->assertFalse(Settings::canUseFeature('tags'), 'Switched off');
    }
}
