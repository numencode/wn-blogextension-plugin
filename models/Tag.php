<?php

declare(strict_types=1);

namespace NumenCode\BlogExtension\Models;

use Cms\Classes\Page as CmsPage;
use Cms\Classes\Theme;
use Model;
use Winter\Blog\Models\Post;
use Winter\Blog\Traits\Urlable;
use Winter\Storm\Database\Traits\Sluggable;
use Winter\Storm\Database\Traits\Validation;
use Winter\Storm\Support\Facades\Url;

/**
 * A blog post tag. Name and slug are translatable (Winter.Translate).
 */
class Tag extends Model
{
    use Sluggable;
    use Urlable;
    use Validation;

    /**
     * Component that lists the posts of a tag; its "slug" property holds the URL parameter of the tag page.
     */
    public const TAG_COMPONENT = 'blogTagFilter';

    public $table = 'numencode_blogextension_tags';

    public $implement = ['@Winter.Translate.Behaviors.TranslatableModel'];

    public $translatable = [
        'name',
        ['slug', 'index' => true],
    ];

    public $rules = [
        'name' => 'required|max:255',
        'slug' => ['required', 'max:255', 'regex:/^[a-z0-9\-_]+$/i', 'unique'],
    ];

    protected $fillable = [
        'name',
        'slug',
    ];

    /**
     * The slug is generated from the name when it's empty.
     */
    protected $slugs = [
        'slug' => 'name',
    ];

    public $belongsToMany = [
        'posts' => [
            Post::class,
            'table'    => 'numencode_blogextension_posts_tags',
            'key'      => 'tag_id',
            'otherKey' => 'post_id',
        ],
    ];

    /**
     * URL parameters of the tag, including the parameter name used by the tag page's blogTagFilter component.
     */
    public function getUrlParams(?CmsPage $page = null): array
    {
        $params = [
            'id'   => $this->id,
            'slug' => $this->slug,
        ];

        if ($page && $paramName = $this->getParamNameFromComponentProperty($page, static::TAG_COMPONENT, 'slug')) {
            $params[$paramName] = $this->slug;
        }

        return $params;
    }

    /**
     * Finds a tag by its slug in the active language (any language's slug works with Winter.Translate).
     */
    public static function findBySlug(?string $slug): ?static
    {
        if ($slug === null || $slug === '') {
            return null;
        }

        $query = static::query();

        if ((new static())->isClassExtendedWith('Winter.Translate.Behaviors.TranslatableModel')) {
            return $query->transWhere('slug', $slug)->first();
        }

        return $query->where('slug', $slug)->first();
    }

    /**
     * Menu item type information for Winter.Pages (types "blog-tag" and "all-blog-tags").
     */
    public static function getMenuTypeInfo(string $type): array
    {
        $result = match ($type) {
            'blog-tag'      => ['references' => static::orderBy('name')->lists('name', 'id'), 'dynamicItems' => false],
            'all-blog-tags' => ['dynamicItems' => true],
            default         => [],
        };

        if ($result && class_exists(Theme::class) && $theme = Theme::getActiveTheme()) {
            $result['cmsPages'] = static::getTagPages($theme);
        }

        return $result;
    }

    /**
     * Resolves a "blog-tag" or "all-blog-tags" menu item (Winter.Pages menus and Winter.Sitemap).
     */
    public static function resolveMenuItem(object $item, string $currentUrl, Theme $theme): ?array
    {
        if (!$item->cmsPage || !$cmsPage = CmsPage::loadCached($theme, $item->cmsPage)) {
            return null;
        }

        if ($item->type === 'blog-tag') {
            if (!$item->reference || !$tag = static::find($item->reference)) {
                return null;
            }

            return $tag->getMenuItem($cmsPage, $currentUrl);
        }

        if ($item->type === 'all-blog-tags') {
            $items = [];

            foreach (static::whereHas('posts', fn ($query) => $query->isPublished())->orderBy('name')->get() as $tag) {
                $items[] = ['title' => $tag->name] + $tag->getMenuItem($cmsPage, $currentUrl);
            }

            return ['items' => $items];
        }

        return null;
    }

    /**
     * Menu item data of this tag: URL, active state, modification time and localized URLs.
     */
    protected function getMenuItem(CmsPage $cmsPage, string $currentUrl): array
    {
        $url = Url::to((string) $this->getUrl($cmsPage));

        $result = [
            'url'      => $url,
            'isActive' => $url === $currentUrl,
            'mtime'    => $this->updated_at,
        ];

        $localizedUrls = $this->getLocalizedUrls($cmsPage);

        if (count($localizedUrls) > 1) {
            $result['alternateLinks'] = $localizedUrls;
        }

        return $result;
    }

    /**
     * CMS pages with a blogTagFilter component whose slug comes from a URL parameter.
     *
     * @return array<int, CmsPage>
     */
    protected static function getTagPages(Theme $theme): array
    {
        $pages = [];

        foreach (CmsPage::listInTheme($theme, true) as $page) {
            if (!$page->hasComponent(static::TAG_COMPONENT)) {
                continue;
            }

            $properties = $page->getComponentProperties(static::TAG_COMPONENT);

            if (preg_match('/{{\s*:/', (string) ($properties['slug'] ?? ''))) {
                $pages[] = $page;
            }
        }

        return $pages;
    }
}
