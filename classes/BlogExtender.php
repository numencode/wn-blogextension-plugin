<?php

declare(strict_types=1);

namespace NumenCode\BlogExtension\Classes;

use Backend;
use Backend\Widgets\Form;
use Backend\Widgets\Lists;
use Event;
use NumenCode\BlogExtension\Models\File;
use NumenCode\BlogExtension\Models\Picture;
use NumenCode\BlogExtension\Models\Settings;
use NumenCode\BlogExtension\Models\Tag;
use Winter\Blog\Controllers\Posts as PostsController;
use Winter\Blog\Models\Category;
use Winter\Blog\Models\Post;

/**
 * Extends Winter.Blog: post relations (tags, pictures, files), the post form, the category list, the Blog side
 * menu and the Winter.Pages menu item types for tags.
 */
class BlogExtender
{
    public const POSTS_TAGS_TABLE = 'numencode_blogextension_posts_tags';

    public function init(): void
    {
        $this->extendPostModel();
        $this->extendPostForm();
        $this->extendCategoryList();
        $this->extendNavigation();
        $this->registerMenuItemTypes();
    }

    /**
     * Post relations "tags", "pictures" and "files" (pictures and files are edited inline with relation
     * repeaters) and the "primary_picture" attribute: the first published picture.
     */
    protected function extendPostModel(): void
    {
        Post::extend(function (Post $post): void {
            $post->implement[] = 'NumenCode.Fundamentals.Behaviors.RelationableModel';

            $post->addDynamicProperty('relationable', [
                'pictures_list' => 'pictures',
                'files_list'    => 'files',
            ]);

            $post->belongsToMany['tags'] = [
                Tag::class,
                'table'    => self::POSTS_TAGS_TABLE,
                'key'      => 'post_id',
                'otherKey' => 'tag_id',
                'order'    => 'name',
            ];

            $post->hasMany['pictures'] = [Picture::class, 'key' => 'post_id', 'delete' => true];
            $post->hasMany['files'] = [File::class, 'key' => 'post_id', 'delete' => true];

            $post->addDynamicMethod('getPrimaryPictureAttribute', function () use ($post): ?Picture {
                return $post->pictures->first();
            });
        });
    }

    /**
     * Post form: field layout, tags, picture gallery, files and the excerpt editor.
     */
    protected function extendPostForm(): void
    {
        // Before Winter.Translate's listener, so it turns the fields added here into their multilingual versions
        Event::listen('backend.form.extendFieldsBefore', function (Form $form): void {
            if (!$form->model instanceof Post || $form->isNested || !$form->getController() instanceof PostsController) {
                return;
            }

            $form->tabs['fields'] = $this->extendPostFields($form->tabs['fields'] ?? []);
        }, 10);
    }

    /**
     * @param array<string, array<string, mixed>> $fields The post form's tab fields
     * @return array<string, array<string, mixed>>
     */
    public function extendPostFields(array $fields): array
    {
        foreach (['published', 'user', 'published_at', 'categories'] as $name) {
            if (isset($fields[$name])) {
                $fields[$name]['span'] = 'auto';
            }
        }

        if (isset($fields['published'])) {
            $fields['published']['comment'] = 'numencode.fundamentals::lang.field.is_published_comment';
        }

        $fields = array_before($fields, 'user', 'published_at');
        $fields = array_before($fields, 'user', 'excerpt');

        if (isset($fields['excerpt'])) {
            $fields['excerpt']['span'] = 'full';

            if (Settings::get('editor_excerpt_wysiwyg')) {
                $fields['excerpt']['type'] = 'richeditor';
                $fields['excerpt']['size'] = 'large';
            }
        }

        if (Settings::canUseFeature('tags')) {
            $fields['tags'] = [
                'tab'          => 'winter.blog::lang.post.tab_categories',
                'label'        => 'numencode.blogextension::lang.tags.label',
                'commentAbove' => 'numencode.blogextension::lang.tags.comment',
                'type'         => 'taglist',
                'mode'         => 'relation',
                'span'         => 'auto',
            ];
        }

        if (Settings::canUseFeature('pictures')) {
            unset($fields['featured_images']);

            $fields['pictures_list'] = $this->repeaterField('pictures', 'picture');
        }

        if (Settings::canUseFeature('files')) {
            $fields['files_list'] = $this->repeaterField('files', 'file');
        }

        return $fields;
    }

    /**
     * @return array<string, mixed>
     */
    protected function repeaterField(string $tab, string $model): array
    {
        return [
            'tab'           => 'numencode.blogextension::lang.tabs.' . $tab,
            'type'          => 'relationrepeater',
            'form'          => '$/numencode/blogextension/models/' . $model . '/fields.yaml',
            'prompt'        => 'numencode.blogextension::lang.' . $tab . '.add',
            'titleFrom'     => 'title',
            'style'         => 'collapsed',
            'showToggleAll' => true,
            'span'          => 'full',
        ];
    }

    /**
     * Category list: a slug column after the name.
     */
    protected function extendCategoryList(): void
    {
        Event::listen('backend.list.extendColumns', function (Lists $list): void {
            if (!$list->model instanceof Category) {
                return;
            }

            $list->addColumns([
                'slug' => [
                    'label'      => 'numencode.fundamentals::lang.field.slug',
                    'searchable' => true,
                    'sortable'   => true,
                ],
            ]);
        });
    }

    /**
     * Blog side menu: Tags, and the Faker page while it is available.
     */
    protected function extendNavigation(): void
    {
        Event::listen('backend.menu.extendItems', function ($manager): void {
            $items = [];

            if (Settings::isFeatureEnabled('tags')) {
                $items['tags'] = [
                    'label'       => 'numencode.blogextension::lang.tags.menu_label',
                    'icon'        => 'icon-tags',
                    'url'         => Backend::url('numencode/blogextension/tags'),
                    'permissions' => ['numencode.blogextension.access_tags'],
                ];
            }

            if (FakerGenerator::isAvailable()) {
                $items['faker'] = [
                    'label'       => 'numencode.blogextension::lang.faker.menu_label',
                    'icon'        => 'icon-magic',
                    'url'         => Backend::url('numencode/blogextension/faker'),
                    'permissions' => ['numencode.blogextension.access_faker'],
                ];
            }

            if ($items) {
                $manager->addSideMenuItems('Winter.Blog', 'blog', $items);
            }
        });
    }

    /**
     * Winter.Pages menu item types "blog-tag" and "all-blog-tags" (also used by Winter.Sitemap).
     */
    protected function registerMenuItemTypes(): void
    {
        Event::listen('pages.menuitem.listTypes', function (): array {
            return [
                'blog-tag'      => 'numencode.blogextension::lang.menuitem.blog_tag',
                'all-blog-tags' => 'numencode.blogextension::lang.menuitem.all_blog_tags',
            ];
        });

        Event::listen('pages.menuitem.getTypeInfo', function (string $type): ?array {
            return in_array($type, ['blog-tag', 'all-blog-tags'], true) ? Tag::getMenuTypeInfo($type) : null;
        });

        Event::listen('pages.menuitem.resolveItem', function (string $type, $item, $url, $theme): ?array {
            return in_array($type, ['blog-tag', 'all-blog-tags'], true) ? Tag::resolveMenuItem($item, $url, $theme) : null;
        });
    }
}
