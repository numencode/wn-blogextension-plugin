<?php

declare(strict_types=1);

namespace NumenCode\BlogExtension\Tests\Components;

use NumenCode\BlogExtension\Components\Breadcrumbs;
use NumenCode\BlogExtension\Components\ReadingTime;
use NumenCode\BlogExtension\Components\TagFilter;
use NumenCode\BlogExtension\Components\TagList;
use NumenCode\BlogExtension\Components\TagRelated;
use NumenCode\BlogExtension\Models\Settings;
use NumenCode\BlogExtension\Models\Tag;
use NumenCode\BlogExtension\Tests\BlogExtensionTestCase;

class ComponentsTest extends BlogExtensionTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->useFixtureTheme();
    }

    public function testPropertiesFollowTheNamingRules(): void
    {
        $this->assertSame(['limit', 'sortOrder', 'showPostCount', 'showEmptyTags', 'tagPage'], array_keys((new TagList())->defineProperties()));
        $this->assertSame(['postAlias', 'limit', 'noPostsMessage', 'postPage', 'categoryPage', 'tagPage'], array_keys((new TagRelated())->defineProperties()));
        $this->assertSame(['categoryPage', 'postsAlias', 'postAlias', 'divider'], array_keys((new Breadcrumbs())->defineProperties()));
        $this->assertSame(['postAlias', 'wordsPerMinute'], array_keys((new ReadingTime())->defineProperties()));
        $this->assertContains('tagPage', array_keys((new TagFilter())->defineProperties()));
        $this->assertArrayHasKey('', (new TagList())->getTagPageOptions(), 'Tag links can be switched off');
    }

    public function testCountWordsHandlesAnyScript(): void
    {
        $this->assertSame(4, ReadingTime::countWords('<p>Čez <b>žgance</b> šel</p> ključ'));
        $this->assertSame(4, ReadingTime::countWords("It's a well-known&nbsp;fact"));
        $this->assertSame(0, ReadingTime::countWords('<p></p>'));
    }

    public function testPostPageComponents(): void
    {
        $parent = $this->makeCategory('News');
        $child = $this->makeCategory('Local', $parent);
        $coffee = $this->makeTag('Coffee');
        $tea = $this->makeTag('Tea');
        $other = $this->makeTag('Other');

        $this->makePost('Main post', ['content' => 'one two three four five'], [], [$coffee, $tea], [$child]);
        $this->makePost('Shares two', ['published_at' => now()->subDays(5)], [['title' => 'Pic', 'picture' => '/pic.jpg']], [$coffee, $tea]);
        $this->makePost('Shares one newer', ['published_at' => now()->subHour()], [], [$coffee]);
        $this->makePost('Unrelated', [], [], [$other]);
        $this->makePost('Unpublished related', ['published' => false], [], [$coffee, $tea]);

        $response = $this->runPage('/blog/post/main-post');
        $this->assertSame(200, $response->getStatusCode());
        $out = static::sections($response->getContent());

        // Breadcrumbs: category path (top level first) and the post, also as the page variable
        $this->assertMatchesRegularExpression('#href="[^"]*/blog/category/news">News</a>.*href="[^"]*/blog/category/local">Local</a>.*aria-current="page">Main post#s', $out['BREADCRUMBS']);
        $this->assertStringContainsString("--bs-breadcrumb-divider: '\\BB ';", $out['BREADCRUMBS']);
        $this->assertSame('News;Local;Main post;', $out['PAGEVAR']);

        // 5 words at 2 words per minute
        $this->assertStringContainsString('3 min read', $out['READING']);

        $this->assertMatchesRegularExpression('#href="[^"]*/blog/tag/coffee"[^>]*>Coffee</a>.*>Tea</a>#s', $out['TAGS']);

        // Related: most shared tags first, then newest; published only; not the post itself
        preg_match_all('#<h3 class="h6 mb-1"><a href="[^"]*">([^<]+)</a>#', $out['RELATED'], $titles);
        $this->assertSame(['Shares two', 'Shares one newer'], $titles[1]);
        $this->assertStringContainsString('alt="Pic"', $out['RELATED']);
    }

    public function testComponentsInTheLayoutStillSeeThePost(): void
    {
        $this->makePost('Layout post', ['content' => 'a b c d'], [], [$this->makeTag('Coffee')]);

        // Layout components run before the page's blogPost component; their data is prepared before rendering
        $out = static::sections($this->runPage('/blog/layout-post/layout-post')->getContent());

        $this->assertSame('Layout post', $out['TITLE']);

        $this->assertStringContainsString('2 min read', $out['READING']);
        $this->assertStringContainsString('Coffee', $out['TAGS']);
    }

    public function testMissingPostShows404(): void
    {
        $response = $this->runPage('/blog/post/missing');

        $this->assertSame(404, $response->getStatusCode());
        $this->assertStringContainsString('NOT FOUND', $response->getContent());
    }

    public function testTagPage(): void
    {
        $coffee = $this->makeTag('Coffee');
        $tea = $this->makeTag('Tea');
        $this->makeTag('Empty');
        $hiddenOnly = $this->makeTag('Hidden only');

        $this->makePost('Coffee one', ['published_at' => now()->subDays(3)], [], [$coffee]);
        $this->makePost('Coffee two', ['published_at' => now()->subDays(2)], [], [$coffee, $tea]);
        $this->makePost('Coffee three', ['published_at' => now()->subDay()], [], [$coffee]);
        $this->makePost('Draft', ['published' => false], [], [$coffee, $hiddenOnly]);

        $out = static::sections($this->runPage('/blog/tag/coffee')->getContent());

        // Tag list: published posts only, most posts first, empty tags hidden
        $this->assertMatchesRegularExpression('#Coffee\s*<span class="badge text-bg-secondary">3</span>.*Tea\s*<span class="badge text-bg-secondary">1</span>#s', $out['LIST']);
        $this->assertStringNotContainsString('Empty', $out['LIST']);
        $this->assertStringNotContainsString('Hidden only', $out['LIST']);

        // Tag filter: newest first, 2 per page, the current tag highlighted
        $this->assertStringContainsString('Posts tagged “Coffee”', $out['FILTER']);
        $this->assertMatchesRegularExpression('#Coffee three.*Coffee two#s', $out['FILTER']);
        $this->assertStringNotContainsString('Coffee one', $out['FILTER']);
        $this->assertStringNotContainsString('Draft', $out['FILTER']);
        $this->assertStringContainsString('class="btn btn-sm btn-secondary" aria-current="page">Coffee</a>', $out['FILTER']);
        $this->assertStringContainsString('/blog/tag/coffee/2', $out['FILTER']);

        $this->assertStringContainsString('Coffee one', static::sections($this->runPage('/blog/tag/coffee/2')->getContent())['FILTER']);
    }

    public function testTagAndCategoryNamesAreEscaped(): void
    {
        $tag = Tag::create(['name' => '<script>x</script>', 'slug' => 'xss']);
        $this->makePost('Escaped', [], [], [$tag], [$this->makeCategory('<b>Cat</b>')]);

        $html = $this->runPage('/blog/tag/xss')->getContent();

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringNotContainsString('<b>Cat</b>', $html);
        $this->assertStringContainsString('&lt;script&gt;x&lt;/script&gt;', $html);
    }

    public function testTagPageErrors(): void
    {
        $this->makePost('Coffee', [], [], [$this->makeTag('Coffee')]);

        $this->assertSame(404, $this->runPage('/blog/tag/unknown')->getStatusCode());
        $this->assertSame(404, $this->runPage('/blog/tag/coffee/abc')->getStatusCode());

        $redirect = $this->runPage('/blog/tag/coffee/5');
        $this->assertSame(302, $redirect->getStatusCode());
        $this->assertStringEndsWith('/blog/tag/coffee/1', $redirect->headers->get('Location'));
    }

    public function testCategoryBreadcrumbs(): void
    {
        $parent = $this->makeCategory('News');
        $this->makeCategory('Local', $parent);

        $out = static::sections($this->runPage('/blog/category/local')->getContent());

        $this->assertMatchesRegularExpression('#>News</a>.*aria-current="page">Local</li>#s', $out['BREADCRUMBS']);
    }

    public function testRssFeed(): void
    {
        Settings::set(['rss_feed_title' => 'My <feed>', 'rss_feed_content' => true, 'rss_feed_author' => 'ed@example.com']);
        $this->makePost('Feed post', ['content' => 'Body with ]]> and <b>html</b>']);

        $response = $this->runPage('/blog/rss');
        $xml = simplexml_load_string($response->getContent());

        $this->assertSame('text/xml', $response->headers->get('Content-Type'));
        $this->assertNotFalse($xml, 'The feed is valid XML');
        $this->assertSame('My <feed>', (string) $xml->channel->title);
        $this->assertSame('en', (string) $xml->channel->language);
        $this->assertSame('Feed post', (string) $xml->channel->item[0]->title);
        $this->assertStringContainsString('Body with ]]', (string) $xml->channel->item[0]->description);
        $this->assertSame('ed@example.com', (string) $xml->channel->item[0]->author);
    }
}
