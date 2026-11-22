<?php

declare(strict_types=1);

namespace NumenCode\BlogExtension\Tests\Models;

use Db;
use NumenCode\BlogExtension\Tests\BlogExtensionTestCase;
use Winter\Blog\Models\Post;

/**
 * With Winter.Translate: every language's rendered content (content_html) is built from that language's content.
 */
class TranslatedContentTest extends BlogExtensionTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        if (!class_exists(\Winter\Translate\Classes\Translator::class)) {
            $this->markTestSkipped('Needs Winter.Translate.');
        }
    }

    public function testSavingWithATranslationKeepsTheDefaultContentHtml(): void
    {
        $post = $this->makePost('Html', ['content' => '<p>English</p>']);

        // Like the backend form in the default language with the Slovenian translation filled in
        $post->setAttributeTranslated('content', '<p>Slovensko</p>', 'sl');
        $post->content = '<p>English 2</p>';
        $post->save();

        $this->assertSame($this->html('<p>English 2</p>'), $this->baseContentHtml($post));
        $this->assertSame($this->html('<p>Slovensko</p>'), $this->translatedContentHtml($post, 'sl'));
        $this->assertSame('en', $post->translateContext(), "The post's language is restored after the save");
    }

    public function testSavingInAnotherLanguage(): void
    {
        $post = $this->makePost('Html', ['content' => '<p>English</p>']);

        $post->translateContext('sl');
        $post->content = '<p>Slovensko</p>';
        $post->save();

        $this->assertSame($this->html('<p>English</p>'), $this->baseContentHtml($post));
        $this->assertSame($this->html('<p>Slovensko</p>'), $this->translatedContentHtml($post, 'sl'));
        $this->assertSame('sl', $post->translateContext());

        $fresh = Post::find($post->id);
        $this->assertSame($this->html('<p>English</p>'), $fresh->content_html);
        $this->assertSame($this->html('<p>Slovensko</p>'), $fresh->lang('sl')->content_html);
    }

    public function testATranslationWithoutContentShowsTheDefaultContentHtml(): void
    {
        $post = $this->makePost('Html', ['content' => '<p>English</p>']);

        $post->setAttributeTranslated('title', 'Naslov', 'sl');
        $post->save();

        $this->assertSame('', $this->translatedContentHtml($post, 'sl'));
        $this->assertSame($this->html('<p>English</p>'), Post::find($post->id)->lang('sl')->content_html);
    }

    protected function baseContentHtml(Post $post): string
    {
        return (string) Db::table('winter_blog_posts')->where('id', $post->id)->value('content_html');
    }

    protected function translatedContentHtml(Post $post, string $locale): ?string
    {
        $data = json_decode((string) Db::table('winter_translate_attributes')
            ->where('model_type', Post::class)
            ->where('model_id', $post->id)
            ->where('locale', $locale)
            ->value('attribute_data'), true);

        return is_array($data) ? ($data['content_html'] ?? null) : null;
    }
}
