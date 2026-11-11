<?php

declare(strict_types=1);

namespace NumenCode\BlogExtension\Components;

use Illuminate\Database\Eloquent\Collection;
use NumenCode\BlogExtension\Classes\BlogComponent;
use NumenCode\BlogExtension\Models\Tag;

/**
 * A list of tags with the number of their published posts.
 */
class TagList extends BlogComponent
{
    public const SORT_OPTIONS = [
        'name asc'         => 'sorting.name_asc',
        'name desc'        => 'sorting.name_desc',
        'posts_count desc' => 'sorting.posts_count_desc',
        'created_at asc'   => 'sorting.created_asc',
        'created_at desc'  => 'sorting.created_desc',
    ];

    public ?Collection $tags = null;

    public function componentDetails(): array
    {
        return [
            'name'        => self::LANG . 'tag_list.name',
            'description' => self::LANG . 'tag_list.description',
        ];
    }

    public function defineProperties(): array
    {
        return [
            'limit'         => $this->limitProperty(0, 'tag_limit_description'),
            'sortOrder'     => [
                'title'             => self::LANG . 'properties.sort_order',
                'description'       => self::LANG . 'properties.tag_sort_order_description',
                'type'              => 'dropdown',
                'default'           => 'name asc',
                'showExternalParam' => false,
            ],
            'showPostCount' => [
                'title'             => self::LANG . 'properties.show_post_count',
                'description'       => self::LANG . 'properties.show_post_count_description',
                'type'              => 'checkbox',
                'default'           => true,
                'showExternalParam' => false,
            ],
            'showEmptyTags' => [
                'title'             => self::LANG . 'properties.show_empty_tags',
                'description'       => self::LANG . 'properties.show_empty_tags_description',
                'type'              => 'checkbox',
                'default'           => false,
                'showExternalParam' => false,
            ],
            'tagPage'       => $this->pageProperty('tag', 'blog/tag'),
        ];
    }

    public function onRun()
    {
        $this->tags = $this->page['tags'] = $this->loadTags();
    }

    protected function loadTags(): Collection
    {
        $query = Tag::withCount(['posts' => fn ($posts) => $posts->isPublished()]);

        if (!$this->property('showEmptyTags')) {
            $query->whereHas('posts', fn ($posts) => $posts->isPublished());
        }

        $sortOrder = (string) $this->property('sortOrder');
        [$column, $direction] = explode(' ', array_key_exists($sortOrder, self::SORT_OPTIONS) ? $sortOrder : 'name asc');
        $query->orderBy($column, $direction)->orderBy('name');

        if ($limit = (int) $this->property('limit')) {
            $query->limit($limit);
        }

        $tags = $query->get();
        $this->setTagUrls($tags);

        return $tags;
    }

    public function getSortOrderOptions(): array
    {
        return array_map(fn (string $key): string => trans(self::LANG . $key), self::SORT_OPTIONS);
    }
}
