<?php

declare(strict_types=1);

namespace NumenCode\BlogExtension\Tests\Updates;

use Db;
use NumenCode\BlogExtension\Tests\BlogExtensionTestCase;
use NumenCode\BlogExtension\Updates\V200UpgradeTables;
use NumenCode\BlogExtension\Updates\V210AddBricksToPosts;
use NumenCode\BlogExtension\Updates\V220RebuildPostContentHtml;
use Schema;
use Winter\Blog\Models\Post;

require_once __DIR__ . '/../../updates/v2_0_0_upgrade_tables.php';
require_once __DIR__ . '/../../updates/v2_1_0_add_bricks_to_posts.php';
require_once __DIR__ . '/../../updates/v2_2_0_rebuild_post_content_html.php';

class MigrationTest extends BlogExtensionTestCase
{
    public function testUpgradeFixesTagsAndMovesTheContentEditorSetting(): void
    {
        $migration = new V200UpgradeTables();
        $migration->down();

        Db::table('numencode_blogextension_tags')->insert([
            ['id' => 1, 'name' => 'Dup', 'slug' => 'dup'],
            ['id' => 2, 'name' => 'Dup', 'slug' => 'dup'],
            ['id' => 3, 'name' => 'No Slug', 'slug' => null],
            ['id' => 4, 'name' => '', 'slug' => ''],
        ]);

        $postId = Db::table('winter_blog_posts')->insertGetId(['title' => 'P', 'slug' => 'p', 'content' => 'x']);
        Db::table('numencode_blogextension_pictures')->insert([
            ['post_id' => $postId, 'title' => 'Kept', 'picture' => '/a.jpg', 'is_published' => true],
            ['post_id' => 99999, 'title' => 'Orphan', 'picture' => '/b.jpg', 'is_published' => true],
        ]);

        Db::table('system_settings')->insert([
            'item'  => 'numencode_blogextension_settings',
            'value' => json_encode(['editor_content_wysiwyg' => '1', 'extension_tags' => '1']),
        ]);

        $migration->up();

        $this->assertSame(
            [1 => 'dup', 2 => 'dup-2', 3 => 'no-slug', 4 => 'tag-4'],
            Db::table('numencode_blogextension_tags')->orderBy('id')->pluck('slug', 'id')->all()
        );
        $this->assertSame('tag-4', Db::table('numencode_blogextension_tags')->where('id', 4)->value('name'));

        $this->assertSame(['Kept'], Db::table('numencode_blogextension_pictures')->pluck('title')->all());

        $settings = json_decode(Db::table('system_settings')->where('item', 'numencode_blogextension_settings')->value('value'), true);
        $blog = json_decode(Db::table('system_settings')->where('item', 'winter_blog_settings')->value('value'), true);
        $this->assertSame(['extension_tags' => '1'], $settings);
        $this->assertTrue($blog['use_rich_editor']);
    }

    public function testNewAttachmentsArePublishedByDefault(): void
    {
        $postId = Db::table('winter_blog_posts')->insertGetId(['title' => 'P', 'slug' => 'p', 'content' => 'x']);
        Db::table('numencode_blogextension_files')->insert(['post_id' => $postId, 'title' => 'F', 'file' => '/f.pdf']);

        $this->assertSame(1, (int) Db::table('numencode_blogextension_files')->value('is_published'));
    }

    public function testPostsGetABricksColumn(): void
    {
        $this->assertTrue(Schema::hasColumn('winter_blog_posts', 'bricks'));

        $migration = new V210AddBricksToPosts();
        $migration->down();
        $this->assertFalse(Schema::hasColumn('winter_blog_posts', 'bricks'));

        $migration->up();
        $migration->up();
        $this->assertTrue(Schema::hasColumn('winter_blog_posts', 'bricks'));
    }

    public function testContentHtmlIsRebuiltInEveryLanguage(): void
    {
        if (!Schema::hasTable('winter_translate_attributes')) {
            $this->markTestSkipped('Needs Winter.Translate.');
        }

        // As saved by Winter.Translate 2.4: the Slovenian HTML in the post, no HTML in the translation
        $postId = Db::table('winter_blog_posts')->insertGetId([
            'title' => 'P', 'slug' => 'p', 'content' => '<p>English</p>', 'content_html' => '<p>Slovensko</p>',
        ]);
        $otherId = Db::table('winter_blog_posts')->insertGetId([
            'title' => 'Q', 'slug' => 'q', 'content' => '<p>Other</p>', 'content_html' => '<p>Other</p>',
        ]);
        $translation = fn (string $locale, int $id, string $type, array $data) => [
            'locale' => $locale, 'model_id' => $id, 'model_type' => $type, 'attribute_data' => json_encode($data),
        ];
        Db::table('winter_translate_attributes')->insert([
            $translation('sl', $postId, Post::class, ['title' => 'P', 'content' => '<p>Slovensko</p>']),
            $translation('de', $postId, Post::class, ['title' => 'P', 'content' => '', 'content_html' => '<p>Stale</p>']),
            $translation('sl', $otherId, 'Other\\Model', ['content' => 'x']),
        ]);

        (new V220RebuildPostContentHtml())->up();

        $html = fn (int $id) => Db::table('winter_blog_posts')->where('id', $id)->value('content_html');
        $this->assertSame($this->html('<p>English</p>'), $html($postId));
        $this->assertSame($this->html('<p>Other</p>'), $html($otherId));

        $data = fn (string $locale, string $type = Post::class) => json_decode(Db::table('winter_translate_attributes')
            ->where('model_type', $type)->where('locale', $locale)->value('attribute_data'), true);

        $this->assertSame(
            ['title' => 'P', 'content' => '<p>Slovensko</p>', 'content_html' => $this->html('<p>Slovensko</p>')],
            $data('sl')
        );
        $this->assertSame('', $data('de')['content_html'], 'Without content, the default content_html is shown');
        $this->assertSame(['content' => 'x'], $data('sl', 'Other\\Model'), 'Other models are not touched');
    }
}
