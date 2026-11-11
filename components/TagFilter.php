<?php

declare(strict_types=1);

namespace NumenCode\BlogExtension\Components;

use Event;
use Illuminate\Pagination\LengthAwarePaginator;
use Lang;
use NumenCode\BlogExtension\Classes\BlogComponent;
use NumenCode\BlogExtension\Models\Tag;
use Redirect;
use Winter\Blog\Models\Post;

/**
 * The published posts of a tag (selected by its slug), paginated. An unknown tag shows the 404 page.
 */
class TagFilter extends BlogComponent
{
    public ?Tag $tag = null;

    public ?LengthAwarePaginator $posts = null;

    public ?string $pageParam = null;

    public ?string $noPostsMessage = null;

    public function componentDetails(): array
    {
        return [
            'name'        => self::LANG . 'tag_filter.name',
            'description' => self::LANG . 'tag_filter.description',
        ];
    }

    public function defineProperties(): array
    {
        return [
            'slug'           => [
                'title'       => self::LANG . 'properties.tag_slug',
                'description' => self::LANG . 'properties.tag_slug_description',
                'type'        => 'string',
                'default'     => '{{ :slug }}',
            ],
            'pageNumber'     => [
                'title'       => 'winter.blog::lang.settings.posts_pagination',
                'description' => 'winter.blog::lang.settings.posts_pagination_description',
                'type'        => 'string',
                'default'     => '{{ :page }}',
            ],
            'postsPerPage'   => [
                'title'             => 'winter.blog::lang.settings.posts_per_page',
                'type'              => 'string',
                'validationPattern' => '^[0-9]+$',
                'validationMessage' => 'winter.blog::lang.settings.posts_per_page_validation',
                'default'           => '10',
                'showExternalParam' => false,
            ],
            'sortOrder'      => [
                'title'             => 'winter.blog::lang.settings.posts_order',
                'description'       => 'winter.blog::lang.settings.posts_order_description',
                'type'              => 'dropdown',
                'default'           => 'published_at desc',
                'showExternalParam' => false,
            ],
            'noPostsMessage' => $this->noPostsMessageProperty(),
            'postPage'       => $this->pageProperty('post', 'blog/post'),
            'categoryPage'   => $this->pageProperty('category', 'blog/category'),
            'tagPage'        => $this->pageProperty('tag', 'blog/tag'),
        ];
    }

    public function init()
    {
        // The locale picker (Winter.Translate) switches to the tag's slug in the new language
        Event::listen('translate.localePicker.translateParams', function ($page, $params, $oldLocale, $newLocale) {
            $paramName = $this->paramName('slug');

            if (!$paramName || !isset($params[$paramName])) {
                return null;
            }

            $tag = Tag::transWhere('slug', $params[$paramName], $oldLocale)->first();

            return $tag ? [$paramName => $tag->getAttributeTranslated('slug', $newLocale)] + $params : null;
        });
    }

    public function onRun()
    {
        $this->pageParam = $this->page['pageParam'] = $this->paramName('pageNumber');
        $this->noPostsMessage = $this->page['noPostsMessage'] = $this->property('noPostsMessage');

        $this->tag = $this->page['tag'] = Tag::findBySlug($this->property('slug'));

        if (!$this->tag) {
            return $this->notFound();
        }

        $currentPage = $this->property('pageNumber') ?: 1;

        if (!is_numeric($currentPage) || (int) $currentPage < 1) {
            return $this->notFound();
        }

        $this->posts = $this->page['posts'] = $this->listPosts((int) $currentPage);

        if ($this->pageParam && (int) $currentPage > 1 && (int) $currentPage > $this->posts->lastPage()) {
            return Redirect::to($this->currentPageUrl([$this->pageParam => $this->posts->lastPage()]));
        }
    }

    protected function listPosts(int $page): LengthAwarePaginator
    {
        $posts = Post::with($this->postRelations())
            ->whereHas('tags', fn ($tags) => $tags->where('numencode_blogextension_tags.id', $this->tag->id))
            ->listFrontEnd([
                'page'    => $page,
                'sort'    => $this->property('sortOrder'),
                'perPage' => (int) $this->property('postsPerPage') ?: 10,
            ]);

        $this->setPostUrls($posts);

        return $posts;
    }

    protected function notFound()
    {
        $this->setStatusCode(404);

        return $this->controller->run('404');
    }

    public function getSortOrderOptions(): array
    {
        return array_map(fn (string $key): string => Lang::get($key), Post::$allowedSortingOptions);
    }
}
