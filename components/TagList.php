<?php namespace NumenCode\BlogExtension\Components;

use Cms\Classes\Page;
use Cms\Classes\ComponentBase;
use NumenCode\BlogExtension\Models\Tag;

class TagList extends ComponentBase
{
    public $tags;

    public function componentDetails(): array
    {
        return [
            'name'        => 'numencode.blogextension::lang.tag_list.name',
            'description' => 'numencode.blogextension::lang.tag_list.description',
        ];
    }

    public function defineProperties(): array
    {
        return [
            'results'       => [
                'title'             => 'numencode.blogextension::lang.tag_list.results_title',
                'description'       => 'numencode.blogextension::lang.tag_list.results_description',
                'default'           => 0,
                'validationPattern' => '^[0-9]+$',
                'validationMessage' => 'numencode.blogextension::lang.tag_list.results_validation',
            ],
            'sortOrder'     => [
                'title'       => 'numencode.blogextension::lang.tag_list.sort_order_title',
                'description' => 'numencode.blogextension::lang.tag_list.sort_order_description',
                'type'        => 'dropdown',
                'default'     => 'created_at desc',
            ],
            'postsCount'    => [
                'title'       => 'numencode.blogextension::lang.tag_list.posts_count_title',
                'description' => 'numencode.blogextension::lang.tag_list.posts_count_description',
                'type'        => 'checkbox',
                'default'     => true,
            ],
            'emptyTags'     => [
                'title'       => 'numencode.blogextension::lang.tag_list.empty_tags_title',
                'description' => 'numencode.blogextension::lang.tag_list.empty_tags_description',
                'type'        => 'checkbox',
                'default'     => false,
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
        $this->tags = $this->loadTags();
    }

    protected function loadTags()
    {
        $query = Tag::with('posts');

        if (!$this->property('emptyTags')) {
            $query->has('posts', '>', 0);
        }

        if ($take = intVal($this->property('results'))) {
            $query->take($take);
        }

        $query->listTags($this->property('sortOrder'));

        $tags = $query->get();

        $tags->each(function ($tag) {
            if ($this->property('postsCount')) {
                $tag->posts_count = $tag->posts->count();
            }

            if ($tagFilterPage = $this->property('tagFilterPage')) {
                $tag->setUrl($tagFilterPage, $this->controller);
            }
        });

        return $tags;
    }

    public function getSortOrderOptions(): array
    {
        return [
            'name asc'        => 'numencode.blogextension::lang.sorting.name_asc',
            'name desc'       => 'numencode.blogextension::lang.sorting.name_desc',
            'created_at asc'  => 'numencode.blogextension::lang.sorting.created_asc',
            'created_at desc' => 'numencode.blogextension::lang.sorting.created_desc',
            'updated_at asc'  => 'numencode.blogextension::lang.sorting.updated_asc',
            'updated_at desc' => 'numencode.blogextension::lang.sorting.updated_desc',
        ];
    }

    public function getTagFilterPageOptions(): array
    {
        return array_merge([false => '---'], Page::sortBy('baseFileName')->lists('baseFileName', 'baseFileName'));
    }
}
