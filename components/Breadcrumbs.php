<?php namespace NumenCode\BlogExtension\Components;

use Cms\Classes\Page;
use RainLab\Blog\Models\Post;
use Cms\Classes\ComponentBase;
use RainLab\Blog\Models\Category;

class Breadcrumbs extends ComponentBase
{
    protected $categories;

    public $blogBreadcrumbs;
    public $compatibility;
    public $divider;

    public function componentDetails(): array
    {
        return [
            'name'        => 'numencode.blogextension::lang.breadcrumbs.name',
            'description' => 'numencode.blogextension::lang.breadcrumbs.description',
        ];
    }

    public function defineProperties(): array
    {
        return [
            'categoryPage'  => [
                'title'       => 'rainlab.blog::lang.settings.post_category',
                'description' => 'rainlab.blog::lang.settings.post_category_description',
                'type'        => 'dropdown',
                'default'     => 'blog/category',
            ],
            'categoryAlias' => [
                'title'             => 'numencode.blogextension::lang.components.category',
                'description'       => 'numencode.blogextension::lang.components.category_description',
                'type'              => 'string',
                'default'           => 'blogPosts',
                'showExternalParam' => false,
            ],
            'postAlias'     => [
                'title'             => 'numencode.blogextension::lang.components.post',
                'description'       => 'numencode.blogextension::lang.components.post_description',
                'type'              => 'string',
                'default'           => 'blogPost',
                'showExternalParam' => false,
            ],
            'compatibility' => [
                'title'             => 'numencode.blogextension::lang.breadcrumbs.compatibility',
                'description'       => 'numencode.blogextension::lang.breadcrumbs.compatibility_description',
                'type'              => 'checkbox',
                'showExternalParam' => false,
            ],
            'divider'       => [
                'title'             => 'numencode.blogextension::lang.breadcrumbs.divider',
                'description'       => 'numencode.blogextension::lang.breadcrumbs.divider_description',
                'type'              => 'string',
                'showExternalParam' => false,
            ],
        ];
    }

    public function init()
    {
        $this->categories = Category::all();
    }

    public function getCategoryPageOptions()
    {
        return Page::sortBy('baseFileName')->lists('baseFileName', 'baseFileName');
    }

    public function onRun()
    {
        $categoryPath = [];
        $categoryAlias = $this->property('categoryAlias');
        $postAlias = $this->property('postAlias');

        $this->divider = $this->property('divider');
        $this->compatibility = (bool)$this->property('compatibility');

        if (isset($this->page->components[$categoryAlias])) {
            if (!$category = $this->page->components[$categoryAlias]->category) {
                return $this->blogBreadcrumbs = $this->page['breadcrumbs'] = [[
                    'title'    => $this->page->title,
                    'url'      => $this->page->url,
                    'isActive' => true,
                ]];
            }

            $categoryPath = $this->getCategoryPath($category);

            return $this->blogBreadcrumbs = $this->page['breadcrumbs'] = $this->buildBreadcrumbs($categoryPath);
        }

        if (isset($this->page->components[$postAlias])) {
            $post = $this->page->components[$postAlias]->post;

            if ($category = $post->categories->sortByDesc('nest_depth')->first()) {
                $categoryPath = $this->getCategoryPath($category, $post);
            }

            return $this->blogBreadcrumbs = $this->page['breadcrumbs'] = $this->buildBreadcrumbs($categoryPath, $post);
        }
    }

    protected function getCategoryPath(Category $category, Post $post = null): array
    {
        $categoryPath[] = $category;

        $this->getLastParent($category->parent_id, $categoryPath);

        return array_reverse($categoryPath);
    }

    protected function getLastParent($parentId, &$categoryPath = []): ?array
    {
        if (!$parentId) {
            return null;
        }

        if ($category = $this->categories->where('id', intval($parentId))->first()) {
            $categoryPath[] = $category;

            if (intval($category->parent_id)) {
                $this->getLastParent($category->parent_id, $categoryPath);
            }

            return $categoryPath;
        }

        return null;
    }

    protected function buildBreadcrumbs($categoryPath, Post $post = null): array
    {
        $breadcrumbs = [];

        if (!empty($categoryPath)) {
            foreach ($categoryPath as $key => $category) {
                $link = $this->controller->pageUrl($this->property('categoryPage'), [
                    'id'   => $category->id,
                    'slug' => $category->slug,
                ]);

                $breadcrumbs[] = [
                    'title'    => $category->name,
                    'url'      => $link,
                    'isActive' => !$post && count($categoryPath) == $key + 1,
                ];
            }
        }

        if ($post) {
            $breadcrumbs[] = [
                'title'    => $post->title,
                'url'      => $post->slug,
                'isActive' => true,
            ];
        }

        return $breadcrumbs;
    }
}
