<?php namespace NumenCode\BlogExtension\Components;

use Cms\Classes\Page;
use Rainlab\Blog\Models\Post;
use Cms\Classes\ComponentBase;
use NumenCode\BlogExtension\Models\Tag;

class TagFilter extends ComponentBase
{
    public $tag;
    public $slug;
    public $posts;
    public $postPage;
    public $pageParam;
    public $categoryPage;

    public function componentDetails(): array
    {
        return [
            'name'        => 'numencode.blogextension::lang.tag_filter.name',
            'description' => 'numencode.blogextension::lang.tag_filter.description',
        ];
    }

    public function defineProperties(): array
    {
        return [
            'slug'          => [
                'title'       => 'numencode.blogextension::lang.tag_filter.slug_title',
                'description' => 'numencode.blogextension::lang.tag_filter.slug_description',
                'type'        => 'string',
                'default'     => '{{ :slug }}',
            ],
            'pageNumber'    => [
                'title'       => 'rainlab.blog::lang.settings.posts_pagination',
                'description' => 'rainlab.blog::lang.settings.posts_pagination_description',
                'type'        => 'string',
                'default'     => '{{ :page }}',
            ],
            'postsPerPage'  => [
                'title'             => 'rainlab.blog::lang.settings.posts_per_page',
                'type'              => 'string',
                'validationPattern' => '^[0-9]+$',
                'validationMessage' => 'rainlab.blog::lang.settings.posts_per_page_validation',
                'default'           => '10',
                'showExternalParam' => false,
            ],
            'sortOrder'     => [
                'title'             => 'rainlab.blog::lang.settings.posts_order',
                'description'       => 'rainlab.blog::lang.settings.posts_order_description',
                'type'              => 'dropdown',
                'default'           => 'published_at desc',
                'showExternalParam' => false,
            ],
            'postPage'      => [
                'title'       => 'numencode.blogextension::lang.pages.post_title',
                'description' => 'numencode.blogextension::lang.pages.post_description',
                'type'        => 'dropdown',
                'default'     => 'blog/post',
                'group'       => 'numencode.blogextension::lang.groups.links',
            ],
            'categoryPage'  => [
                'title'       => 'numencode.blogextension::lang.pages.category_title',
                'description' => 'numencode.blogextension::lang.pages.category_description',
                'type'        => 'dropdown',
                'default'     => 'blog/category',
                'group'       => 'numencode.blogextension::lang.groups.links',
            ],
            'tagFilterPage' => [
                'title'       => 'numencode.blogextension::lang.pages.tag_filter_title',
                'description' => 'numencode.blogextension::lang.pages.tag_filter_description',
                'type'        => 'dropdown',
                'placeholder' => 'blog/tag',
                'group'       => 'numencode.blogextension::lang.groups.links',
            ],
        ];
    }

    public function onRun()
    {
        $this->prepareVars();

        $this->tag = $this->loadTag();
        $this->posts = $this->loadPosts();
    }

    protected function prepareVars()
    {
        $this->pageParam = $this->page['pageParam'] = $this->paramName('pageNumber');
        $this->slug = $this->page['slug'] = $this->property('slug');
        $this->postPage = $this->page['postPage'] = $this->property('postPage');
        $this->categoryPage = $this->page['categoryPage'] = $this->property('categoryPage');
    }

    protected function loadTag()
    {
        return Tag::where('slug', $this->property('slug'))->first();
    }

    protected function loadPosts()
    {
        $posts = Post::with(['tags', 'categories'])
            ->whereHas('tags', function ($tag) {
                $tag->whereSlug($this->property('slug'));
            })
            ->listFrontEnd(
                [
                    'page'    => $this->property('pageNumber'),
                    'sort'    => $this->property('sortOrder'),
                    'perPage' => $this->property('postsPerPage'),
                ]
            );

        $posts->each(function ($post) {
            $post->setUrl($this->postPage, $this->controller);

            $post->categories->each(function ($tag) {
                $tag->setUrl($this->categoryPage, $this->controller);
            });

            if ($tagFilterPage = $this->property('tagFilterPage')) {
                $post->tags->each(function ($tag) use ($tagFilterPage) {
                    $tag->setUrl($tagFilterPage, $this->controller);
                });
            }
        });

        return $posts;
    }

    public function getSortOrderOptions(): array
    {
        return [
            'title asc'         => 'numencode.blogextension::lang.sorting.title_asc',
            'title desc'        => 'numencode.blogextension::lang.sorting.title_desc',
            'published_at asc'  => 'numencode.blogextension::lang.sorting.published_asc',
            'published_at desc' => 'numencode.blogextension::lang.sorting.published_desc',
            'created_at asc'    => 'numencode.blogextension::lang.sorting.created_asc',
            'created_at desc'   => 'numencode.blogextension::lang.sorting.created_desc',
            'updated_at asc'    => 'numencode.blogextension::lang.sorting.updated_asc',
            'updated_at desc'   => 'numencode.blogextension::lang.sorting.updated_desc',
        ];
    }

    public function getPostPageOptions(): array
    {
        return Page::sortBy('baseFileName')->lists('baseFileName', 'baseFileName');
    }

    public function getCategoryPageOptions(): array
    {
        return Page::sortBy('baseFileName')->lists('baseFileName', 'baseFileName');
    }

    public function getTagFilterPageOptions(): array
    {
        return array_merge([false => '---'], Page::sortBy('baseFileName')->lists('baseFileName', 'baseFileName'));
    }
}
