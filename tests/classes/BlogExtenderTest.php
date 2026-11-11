<?php

declare(strict_types=1);

namespace NumenCode\BlogExtension\Tests\Classes;

use Cms\Classes\Theme;
use Event;
use NumenCode\BlogExtension\Classes\BlogExtender;
use NumenCode\BlogExtension\Models\Settings;
use NumenCode\BlogExtension\Tests\BlogExtensionTestCase;
use Yaml;

class BlogExtenderTest extends BlogExtensionTestCase
{
    /**
     * @return array<string, array<string, mixed>>
     */
    protected function postFields(): array
    {
        $config = Yaml::parseFile(plugins_path('winter/blog/models/post/fields.yaml'));

        return (new BlogExtender())->extendPostFields($config['tabs']['fields']);
    }

    public function testSuperuserGetsAllFeatures(): void
    {
        $this->loginBackendUser([], true);
        $fields = $this->postFields();

        $this->assertSame('taglist', $fields['tags']['type']);
        $this->assertSame('relation', $fields['tags']['mode']);

        foreach (['pictures_list' => 'picture', 'files_list' => 'file'] as $field => $model) {
            $this->assertSame('relationrepeater', $fields[$field]['type']);
            $this->assertSame('title', $fields[$field]['titleFrom']);
            $this->assertSame('collapsed', $fields[$field]['style']);
            $this->assertTrue($fields[$field]['showToggleAll']);
            $this->assertFileExists(plugins_path('numencode/blogextension/models/' . $model . '/fields.yaml'));
        }

        $this->assertArrayNotHasKey('featured_images', $fields, 'The gallery replaces the featured images');
    }

    public function testFieldsDependOnPermissionsAndSwitches(): void
    {
        $this->loginBackendUser(['numencode.blogextension.access_files' => 1]);
        $fields = $this->postFields();

        $this->assertArrayNotHasKey('tags', $fields);
        $this->assertArrayNotHasKey('pictures_list', $fields);
        $this->assertArrayHasKey('featured_images', $fields, 'Featured images stay without the gallery');
        $this->assertArrayHasKey('files_list', $fields);

        Settings::set('extension_files', false);
        $this->assertArrayNotHasKey('files_list', $this->postFields());
    }

    public function testFieldLayoutAndExcerptEditor(): void
    {
        $fields = $this->postFields();
        $order = array_keys($fields);

        $this->assertLessThan(array_search('user', $order), array_search('published_at', $order));
        $this->assertLessThan(array_search('user', $order), array_search('excerpt', $order));
        $this->assertSame('textarea', $fields['excerpt']['type']);

        Settings::set('editor_excerpt_wysiwyg', true);
        $this->assertSame('richeditor', $this->postFields()['excerpt']['type']);
    }

    public function testMenuItemTypes(): void
    {
        $this->useFixtureTheme();
        $coffee = $this->makeTag('Coffee');
        $this->makeTag('Unused');
        $this->makePost('Coffee post', [], [], [$coffee]);

        $types = array_merge(...array_map(fn ($r) => (array) $r, Event::fire('pages.menuitem.listTypes')));
        $this->assertArrayHasKey('blog-tag', $types);
        $this->assertArrayHasKey('all-blog-tags', $types);

        $info = array_values(array_filter(Event::fire('pages.menuitem.getTypeInfo', ['blog-tag'])))[0];
        $this->assertSame(['Coffee', 'Unused'], array_values($info['references']));
        $this->assertSame(['tag'], array_map(fn ($page) => $page->getBaseFileName(), $info['cmsPages']));

        $theme = Theme::getActiveTheme();
        $item = (object) ['type' => 'all-blog-tags', 'cmsPage' => 'tag', 'reference' => null, 'nesting' => false];
        $result = array_values(array_filter(Event::fire('pages.menuitem.resolveItem', ['all-blog-tags', $item, url('/'), $theme])))[0];

        $this->assertCount(1, $result['items'], 'Tags without published posts are left out');
        $this->assertSame('Coffee', $result['items'][0]['title']);
        $this->assertStringEndsWith('/blog/tag/coffee', $result['items'][0]['url']);

        $item = (object) ['type' => 'blog-tag', 'cmsPage' => 'tag', 'reference' => $coffee->id, 'nesting' => false];
        $result = array_values(array_filter(Event::fire('pages.menuitem.resolveItem', ['blog-tag', $item, url('/blog/tag/coffee'), $theme])))[0];
        $this->assertTrue($result['isActive']);
    }
}
