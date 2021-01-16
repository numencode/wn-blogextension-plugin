<?php namespace NumenCode\BlogExtension;

use System\Classes\PluginBase;
use NumenCode\BlogExtension\Components\RssFeed;
use NumenCode\BlogExtension\Components\Breadcrumbs;
use NumenCode\BlogExtension\Components\ReadingTime;
use NumenCode\BlogExtension\Classes\ExtendBlogPostModel;
use NumenCode\BlogExtension\Classes\ExtendBlogPostFields;
use NumenCode\BlogExtension\Classes\ExtendBlogCategoryColumns;

class Plugin extends PluginBase
{
    public $require = [
        'RainLab.Blog',
        'NumenCode.Fundamentals',
    ];

    public function pluginDetails(): array
    {
        return [
            'name'        => 'numencode.blogextension::lang.plugin.name',
            'description' => 'numencode.blogextension::lang.plugin.description',
            'author'      => 'Blaz Orazem',
            'icon'        => 'icon-pencil-square-o',
            'homepage'    => 'https://github.com/numencode/blogextension-plugin',
        ];
    }

    public function register()
    {
        //
    }

    public function boot()
    {
        (new ExtendBlogPostModel())->init();
        (new ExtendBlogPostFields())->init();
        (new ExtendBlogCategoryColumns())->init();
    }

    public function registerPermissions(): array
    {
        return [
            'numencode.blogextension.manage_settings' => [
                'tab'   => 'numencode.blogextension::lang.tabs.blog',
                'label' => 'numencode.blogextension::lang.settings.general.manage',
            ],
            'numencode.blogextension.access_pictures' => [
                'tab'   => 'numencode.blogextension::lang.tabs.blog',
                'label' => 'numencode.blogextension::lang.settings.extensions.pictures',
            ],
            'numencode.blogextension.access_files' => [
                'tab'   => 'numencode.blogextension::lang.tabs.blog',
                'label' => 'numencode.blogextension::lang.settings.extensions.files',
            ],
        ];
    }

    public function registerSettings(): array
    {
        return [
            'settings' => [
                'label'       => 'numencode.blogextension::lang.plugin.name',
                'description' => 'numencode.blogextension::lang.settings.general.manage',
                'icon'        => 'icon-pencil-square-o',
                'class'       => 'NumenCode\BlogExtension\Models\Settings',
                'category'    => 'rainlab.blog::lang.blog.menu_label',
                'permissions' => ['numencode.blogextension.manage_settings'],
            ],
        ];
    }

    public function registerComponents(): array
    {
        return [
            Breadcrumbs::class => 'blogBreadcrumbs',
            ReadingTime::class => 'blogReadingTime',
            RssFeed::class     => 'blogRssFeed',
        ];
    }
}
