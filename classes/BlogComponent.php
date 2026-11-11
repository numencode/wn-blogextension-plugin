<?php

declare(strict_types=1);

namespace NumenCode\BlogExtension\Classes;

use Cms\Classes\ComponentBase;
use Cms\Classes\Page;
use Illuminate\Support\Collection;
use NumenCode\BlogExtension\Models\Tag;
use Winter\Blog\Models\Post;

/**
 * Base class of the blog extension components: shared properties, page dropdowns and URL helpers.
 *
 * Components that need the page's post (from Winter.Blog's blogPost component, selected by "postAlias") prepare
 * their data in prepare(), which runs after all page and layout components have run (so it works when the
 * component is in the layout), or when the component is rendered, whichever comes first.
 */
abstract class BlogComponent extends ComponentBase
{
    protected const LANG = 'numencode.blogextension::lang.';

    protected bool $prepared = false;

    /**
     * Prepares the component's data for its template (see the class description).
     */
    protected function prepare(): void
    {
    }

    public function onRun()
    {
        $this->controller->bindEvent('page.beforeRenderPage', function (): void {
            $this->runPrepare();
        });
    }

    public function onRender()
    {
        $this->runPrepare();
    }

    protected function runPrepare(): void
    {
        if (!$this->prepared) {
            $this->prepared = true;
            $this->prepare();
        }
    }

    /**
     * The post of the page's Winter.Blog post component (property "postAlias"), or null.
     */
    protected function findPost(): ?Post
    {
        $component = $this->page->components[$this->property('postAlias')] ?? null;
        $post = $component?->post ?? null;

        return $post instanceof Post && $post->exists ? $post : null;
    }

    /**
     * Sets the "url" attribute of posts and their categories and tags.
     *
     * @param iterable<Post> $posts
     */
    protected function setPostUrls(iterable $posts): void
    {
        $postPage = (string) $this->property('postPage');
        $categoryPage = (string) $this->property('categoryPage');

        foreach ($posts as $post) {
            if ($postPage !== '') {
                $post->setUrl($postPage, $this->controller);
            }

            if ($categoryPage !== '') {
                $post->categories->each(fn ($category) => $category->setUrl($categoryPage, $this->controller));
            }

            $this->setTagUrls($post->tags);
        }
    }

    /**
     * Sets the "url" attribute of tags (property "tagPage").
     *
     * @param iterable<Tag> $tags
     */
    protected function setTagUrls(iterable $tags): void
    {
        $tagPage = (string) $this->property('tagPage');

        if ($tagPage === '') {
            return;
        }

        foreach ($tags as $tag) {
            $tag->setUrl($tagPage, $this->controller);
        }
    }

    /**
     * Relations to load with listed posts.
     *
     * @return array<int, string>
     */
    protected function postRelations(): array
    {
        return ['categories', 'tags', 'pictures'];
    }

    /*
     * Property definitions
     */

    /**
     * @return array<string, mixed>
     */
    protected function postAliasProperty(): array
    {
        return [
            'title'             => self::LANG . 'properties.post_alias',
            'description'       => self::LANG . 'properties.post_alias_description',
            'type'              => 'string',
            'default'           => 'blogPost',
            'showExternalParam' => false,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function pageProperty(string $type, string $default): array
    {
        return [
            'title'             => self::LANG . 'properties.' . $type . '_page',
            'description'       => self::LANG . 'properties.' . $type . '_page_description',
            'type'              => 'dropdown',
            'default'           => $default,
            'group'             => 'winter.blog::lang.settings.group_links',
            'showExternalParam' => false,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function limitProperty(int $default, string $description): array
    {
        return [
            'title'             => self::LANG . 'properties.limit',
            'description'       => self::LANG . 'properties.' . $description,
            'type'              => 'string',
            'default'           => (string) $default,
            'validationPattern' => '^[0-9]+$',
            'validationMessage' => self::LANG . 'properties.number_validation',
            'showExternalParam' => false,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function noPostsMessageProperty(): array
    {
        return [
            'title'             => 'winter.blog::lang.settings.posts_no_posts',
            'description'       => 'winter.blog::lang.settings.posts_no_posts_description',
            'type'              => 'string',
            'default'           => trans('winter.blog::lang.settings.posts_no_posts_default'),
            'showExternalParam' => false,
        ];
    }

    /*
     * Dropdown options
     */

    public function getPostPageOptions(): array
    {
        return static::listPages();
    }

    public function getCategoryPageOptions(): array
    {
        return static::listPages();
    }

    public function getTagPageOptions(): array
    {
        return ['' => trans(self::LANG . 'properties.no_link')] + static::listPages();
    }

    /**
     * @return array<string, string>
     */
    protected static function listPages(): array
    {
        return Page::sortBy('baseFileName')->lists('baseFileName', 'baseFileName');
    }

    /**
     * @param Collection<int, Tag>|iterable<Tag> $tags
     * @return array<int, int>
     */
    protected static function tagIds(iterable $tags): array
    {
        return collect($tags)->pluck('id')->map(fn ($id) => (int) $id)->all();
    }
}
