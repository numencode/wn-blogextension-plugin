<?php namespace NumenCode\BlogExtension;

use System\Classes\PluginBase;
use NumenCode\BlogExtension\Classes\ExtendBlogPostModel;
use NumenCode\BlogExtension\Classes\ExtendBlogPostFields;
use NumenCode\BlogExtension\Classes\ExtendBlogCategoryColumns;

class Plugin extends PluginBase
{
    public $require = [
        'RainLab.Blog',
        'NumenCode.Fundamentals',
    ];

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

    public function registerPermissions()
    {
        return [
            'numencode.blogextension.manage_settings' => [
                'tab'   => 'numencode.blogextension::lang.tabs.blog',
                'label' => 'numencode.blogextension::lang.settings.manage',
            ],
            'numencode.blogextension.access_pictures' => [
                'tab'   => 'numencode.blogextension::lang.tabs.blog',
                'label' => 'numencode.blogextension::lang.settings.pictures',
            ],
            'numencode.blogextension.access_files' => [
                'tab'   => 'numencode.blogextension::lang.tabs.blog',
                'label' => 'numencode.blogextension::lang.settings.files',
            ],
        ];
    }

    public function registerSettings()
    {
        return [
            'settings' => [
                'label'       => 'numencode.blogextension::lang.plugin.name',
                'description' => 'numencode.blogextension::lang.settings.manage',
                'icon'        => 'icon-pencil-square-o',
                'class'       => 'NumenCode\BlogExtension\Models\Settings',
                'category'    => 'rainlab.blog::lang.blog.menu_label',
                'permissions' => ['numencode.blogextension.manage_settings'],
            ],
        ];
    }
}
