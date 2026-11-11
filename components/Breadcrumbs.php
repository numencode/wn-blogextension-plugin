<?php

declare(strict_types=1);

namespace NumenCode\BlogExtension\Components;

use NumenCode\BlogExtension\Classes\BlogComponent;
use Winter\Blog\Models\Category;
use Winter\Blog\Models\Post;

/**
 * Breadcrumbs of a blog category page (Winter.Blog blogPosts with a category) or a blog post page (blogPost):
 * the category path and the post. Also available to the page as the "breadcrumbs" variable.
 */
class Breadcrumbs extends BlogComponent
{
    /**
     * @var array<int, array{title: string, url: string|null, isActive: bool}>
     */
    public array $breadcrumbs = [];

    public ?string $divider = null;

    public function componentDetails(): array
    {
        return [
            'name'        => self::LANG . 'breadcrumbs.name',
            'description' => self::LANG . 'breadcrumbs.description',
        ];
    }

    public function defineProperties(): array
    {
        return [
            'categoryPage' => $this->pageProperty('category', 'blog/category'),
            'postsAlias'   => [
                'title'             => self::LANG . 'properties.posts_alias',
                'description'       => self::LANG . 'properties.posts_alias_description',
                'type'              => 'string',
                'default'           => 'blogPosts',
                'showExternalParam' => false,
            ],
            'postAlias'    => $this->postAliasProperty(),
            'divider'      => [
                'title'             => self::LANG . 'properties.divider',
                'description'       => self::LANG . 'properties.divider_description',
                'type'              => 'string',
                'default'           => '',
                'showExternalParam' => false,
            ],
        ];
    }

    protected function prepare(): void
    {
        $this->divider = (string) $this->property('divider') ?: null;
        $this->breadcrumbs = $this->page['breadcrumbs'] = $this->buildBreadcrumbs();
    }

    /**
     * @return array<int, array{title: string, url: string|null, isActive: bool}>
     */
    protected function buildBreadcrumbs(): array
    {
        if ($post = $this->findPost()) {
            $category = $post->categories->sortByDesc('nest_depth')->first();

            return array_merge(
                $category ? $this->categoryPath($category, false) : [],
                [['title' => (string) $post->title, 'url' => $this->currentPageUrl(), 'isActive' => true]]
            );
        }

        $postsComponent = $this->page->components[$this->property('postsAlias')] ?? null;

        if (!$postsComponent) {
            return [];
        }

        if ($category = $postsComponent->category ?? null) {
            return $this->categoryPath($category, true);
        }

        return [['title' => (string) $this->page->title, 'url' => $this->currentPageUrl(), 'isActive' => true]];
    }

    /**
     * The category and its parents, the top level first.
     *
     * @return array<int, array{title: string, url: string|null, isActive: bool}>
     */
    protected function categoryPath(Category $category, bool $lastIsActive): array
    {
        $categoryPage = (string) $this->property('categoryPage');
        $path = $category->getParentsAndSelf();
        $last = $path->count() - 1;
        $crumbs = [];

        foreach ($path->values() as $index => $item) {
            $crumbs[] = [
                'title'    => (string) $item->name,
                'url'      => $categoryPage !== '' ? $item->setUrl($categoryPage, $this->controller) : null,
                'isActive' => $lastIsActive && $index === $last,
            ];
        }

        return $crumbs;
    }
}
