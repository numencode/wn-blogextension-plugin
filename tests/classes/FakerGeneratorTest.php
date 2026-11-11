<?php

declare(strict_types=1);

namespace NumenCode\BlogExtension\Tests\Classes;

use NumenCode\BlogExtension\Classes\FakerGenerator;
use NumenCode\BlogExtension\Models\Tag;
use NumenCode\BlogExtension\Tests\BlogExtensionTestCase;
use Winter\Blog\Models\Category;
use Winter\Blog\Models\Post;

class FakerGeneratorTest extends BlogExtensionTestCase
{
    public function testAvailableOnlyInDebugMode(): void
    {
        config(['app.debug' => true]);
        $this->assertTrue(FakerGenerator::isAvailable());

        config(['app.debug' => false]);
        $this->assertFalse(FakerGenerator::isAvailable());
    }

    public function testOptionsAreClamped(): void
    {
        $options = FakerGenerator::normalizeOptions([
            'categories'   => 999,
            'tags'         => -5,
            'posts'        => 'abc',
            'content'      => 1,
            'paragraphs'   => '1',
            'media_folder' => 'photos/',
        ]);

        $this->assertSame(FakerGenerator::MAX_CATEGORIES, $options['categories']);
        $this->assertSame(0, $options['tags']);
        $this->assertSame(0, $options['posts']);
        $this->assertSame(FakerGenerator::MIN_CONTENT, $options['content']);
        $this->assertTrue($options['paragraphs']);
        $this->assertFalse($options['pictures']);
        $this->assertSame('/photos', $options['media_folder']);
    }

    public function testGenerate(): void
    {
        $user = $this->loginBackendUser([], true);

        $result = (new FakerGenerator())->generate(['categories' => 2, 'tags' => 3, 'posts' => 4, 'paragraphs' => true], $user);

        $this->assertSame(['categories' => 2, 'tags' => 3, 'posts' => 4, 'pictures' => 0], $result);
        $this->assertSame(2, Category::count() - 1, 'Plus Winter.Blog\'s "Uncategorized"');
        $this->assertSame(3, Tag::count());
        $this->assertSame(4, Post::count() - Post::where('slug', 'first-blog-post')->count());

        $post = Post::orderBy('id', 'desc')->first();
        $this->assertTrue((bool) $post->published);
        $this->assertSame($user->id, (int) $post->user_id);
        $this->assertCount(1, $post->categories);
        $this->assertGreaterThanOrEqual(1, $post->tags->count());
        $this->assertStringStartsWith('<p>', $post->content);
    }

    public function testParagraphs(): void
    {
        $html = FakerGenerator::toParagraphs(str_repeat("It's a <test> word. ", 60));

        $this->assertGreaterThan(1, substr_count($html, '<p>'));
        $this->assertStringContainsString("It's a &lt;test&gt;", $html, 'Only <, > and & are escaped');
    }
}
