<?php namespace NumenCode\BlogExtension\Components;

use DB;
use Cms\Classes\Page;
use Winter\Blog\Models\Post;
use Cms\Classes\ComponentBase;

class TagRelated extends ComponentBase
{
    public $slug;
    public $posts;
    public $postPage;
    public $categoryPage;

    public function componentDetails(): array
    {
        return [
            'name'        => 'numencode.blogextension::lang.tag_related.name',
            'description' => 'numencode.blogextension::lang.tag_related.description',
        ];
    }

    public function defineProperties(): array
    {
        return [
            'slug'          => [
                'title'       => 'winter.blog::lang.settings.post_slug',
                'description' => 'winter.blog::lang.settings.post_slug_description',
                'default'     => '{{ :slug }}',
                'type'        => 'string',
            ],
            'results'       => [
                'title'             => 'numencode.blogextension::lang.tag_related.results_title',
                'description'       => 'numencode.blogextension::lang.tag_related.results_description',
                'default'           => 5,
                'validationPattern' => '^[1-9]+$',
                'validationMessage' => 'numencode.blogextension::lang.tag_related.results_validation',
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

        $this->posts = $this->loadRelatedPosts();
    }

    protected function prepareVars()
    {
        $this->slug = $this->page['slug'] = $this->property('slug');
        $this->postPage = $this->page['postPage'] = $this->property('postPage');
        $this->categoryPage = $this->page['categoryPage'] = $this->property('categoryPage');
    }

    protected function loadRelatedPosts()
    {
        $post = Post::with('tags')->whereSlug($this->slug)->first();

        if (is_null($post) || !$post->tags->count()) {
            return;
        }

        $tagIds = $post->tags->lists('id');

        $query = Post::isPublished()
            ->where('id', '<>', $post->id)
            ->whereHas('tags', function ($tag) use ($tagIds) {
                $tag->whereIn('id', $tagIds);
            })
            ->with('tags');

        $orderBy = DB::raw('(
            SELECT COUNT(*) FROM `numencode_blogextension_posts_tags`
            WHERE `numencode_blogextension_posts_tags`.`post_id` = `winter_blog_posts`.`id`
            AND `numencode_blogextension_posts_tags`.`tag_id` IN (' . implode(', ', $tagIds) . '))');

        $query->orderby($orderBy, 'desc');

        if ($results = intVal($this->property('results'))) {
            $query->take($results);
        }

        return $query->get()->each(function ($post) {
            $post->setUrl($this->postPage, $this->controller);

            $post->categories->each(function ($category) {
                $category->setUrl($this->categoryPage, $this->controller);
            });

            if ($tagFilterPage = $this->property('tagFilterPage')) {
                $post->tags->each(function ($tag) use ($tagFilterPage) {
                    $tag->setUrl($tagFilterPage, $this->controller);
                });
            }
        });
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
