<?php

declare(strict_types=1);

namespace NumenCode\BlogExtension;

use Backend\Models\UserRole;
use NumenCode\BlogExtension\Classes\BlogExtender;
use NumenCode\BlogExtension\Components\Breadcrumbs;
use NumenCode\BlogExtension\Components\ReadingTime;
use NumenCode\BlogExtension\Components\RssFeed;
use NumenCode\BlogExtension\Components\TagFilter;
use NumenCode\BlogExtension\Components\TagList;
use NumenCode\BlogExtension\Components\TagPost;
use NumenCode\BlogExtension\Components\TagRelated;
use NumenCode\BlogExtension\Models\Settings;
use System\Classes\PluginBase;

class Plugin extends PluginBase
{
    public $require = [
        'Winter.Blog',
        'NumenCode.Fundamentals',
    ];

    public function pluginDetails(): array
    {
        return [
            'name'        => 'numencode.blogextension::lang.plugin.name',
            'description' => 'numencode.blogextension::lang.plugin.description',
            'author'      => 'Blaz Orazem',
            'icon'        => 'icon-pencil-square-o',
            'homepage'    => 'https://www.numencode.com',
        ];
    }

    public function boot(): void
    {
        (new BlogExtender())->init();
    }

    public function registerComponents(): array
    {
        return [
            Breadcrumbs::class => 'blogBreadcrumbs',
            ReadingTime::class => 'blogReadingTime',
            RssFeed::class     => 'blogRssFeed',
            TagList::class     => 'blogTagList',
            TagPost::class     => 'blogTagPost',
            TagFilter::class   => 'blogTagFilter',
            TagRelated::class  => 'blogTagRelated',
        ];
    }

    public function registerPermissions(): array
    {
        $tab = 'winter.blog::lang.blog.tab';
        $editors = [UserRole::CODE_DEVELOPER, UserRole::CODE_PUBLISHER];

        return [
            'numencode.blogextension.manage_settings' => [
                'tab'   => $tab,
                'label' => 'numencode.blogextension::lang.permissions.manage_settings',
                'roles' => $editors,
            ],
            'numencode.blogextension.access_tags'     => [
                'tab'   => $tab,
                'label' => 'numencode.blogextension::lang.permissions.access_tags',
                'roles' => $editors,
            ],
            'numencode.blogextension.access_pictures' => [
                'tab'   => $tab,
                'label' => 'numencode.blogextension::lang.permissions.access_pictures',
                'roles' => $editors,
            ],
            'numencode.blogextension.access_files'    => [
                'tab'   => $tab,
                'label' => 'numencode.blogextension::lang.permissions.access_files',
                'roles' => $editors,
            ],
            'numencode.blogextension.access_faker'    => [
                'tab'   => $tab,
                'label' => 'numencode.blogextension::lang.permissions.access_faker',
                'roles' => [UserRole::CODE_DEVELOPER],
            ],
        ];
    }

    public function registerSettings(): array
    {
        return [
            'settings' => [
                'label'       => 'numencode.blogextension::lang.plugin.name',
                'description' => 'numencode.blogextension::lang.settings.description',
                'icon'        => 'icon-pencil-square-o',
                'class'       => Settings::class,
                'category'    => 'winter.blog::lang.blog.menu_label',
                'permissions' => ['numencode.blogextension.manage_settings'],
                'order'       => 510,
            ],
        ];
    }
}
