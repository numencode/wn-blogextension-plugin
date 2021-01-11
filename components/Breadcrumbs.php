<?php namespace NumenCode\BlogExtension\Components;

use Db;
use Cms\Classes\Page;
use RainLab\Blog\Models\Post;
use Cms\Classes\ComponentBase;
use RainLab\Blog\Models\Category;

class Breadcrumbs extends ComponentBase
{
    protected $categories;
    protected $breadcrumbs;

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
            'categoryPage' => [
                'title'       => 'rainlab.blog::lang.settings.post_category',
                'description' => 'rainlab.blog::lang.settings.post_category_description',
                'type'        => 'dropdown',
                'default'     => 'blog/category',
            ],
            'categoryAlias' => [
                'title'       => 'numencode.blogextension::lang.breadcrumbs.category',
                'description' => 'numencode.blogextension::lang.breadcrumbs.category_description',
                'type'        => 'string',
                'default'     => 'blogPosts',
            ],
            'postAlias' => [
                'title'       => 'numencode.blogextension::lang.breadcrumbs.post',
                'description' => 'numencode.blogextension::lang.breadcrumbs.post_description',
                'type'        => 'string',
                'default'     => 'blogPost',
            ],
            'homepage'     => [
                'title'       => 'numencode.blogextension::lang.breadcrumbs.homepage',
                'description' => 'numencode.blogextension::lang.breadcrumbs.homepage_description',
                'default'     => 'Home',
                'type'        => 'string',
            ],
            'divider'      => [
                'title'       => 'numencode.blogextension::lang.breadcrumbs.divider',
                'description' => 'numencode.blogextension::lang.breadcrumbs.divider_description',
                'type'        => 'string',
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
        $this->divider = $this->property('divider');
        $categoryAlias = $this->property('categoryAlias');
        $postAlias = $this->property('postAlias');

        if (isset($this->page->components[$categoryAlias])) {
            $category = $this->page->components[$categoryAlias]->category;
            $categoryPath = $this->getCategoryPath($category);

            $this->breadcrumbs = $this->page['breadcrumbs'] = $this->buildBreadcrumbs($categoryPath);
        }

        if (isset($this->page->components[$postAlias])) {
            $post = $this->page->components[$postAlias]->post;

            if ($category = $post->categories->sortByDesc('nest_depth')->first()) {
                $categoryPath = $this->getCategoryPath($category, $post);
            }

            $this->breadcrumbs = $this->page['breadcrumbs'] = $this->buildBreadcrumbs($categoryPath, $post);
        }
    }

    protected function getCategoryPath(Category $category, Post $post = null): array
    {
        $category->nolink = !$post;
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
        $breadcrumbs[] = [
            'name' => $this->property('homepage'),
            'link' => url('/'),
        ];

        if (!empty($categoryPath)) {
            foreach ($categoryPath as $category) {
                $link = null;

                if (!$category->nolink) {
                    $link = $this->controller->pageUrl($this->property('categoryPage'), [
                        'id'   => $category->id,
                        'slug' => $category->slug,
                    ]);
                }

                $breadcrumbs[] = [
                    'name' => $category->name,
                    'link' => $link,
                ];
            }
        }

        if ($post) {
            $breadcrumbs[] = [
                'link' => null,
                'name' => $post->title,
            ];
        }

        return $breadcrumbs;
    }
}
