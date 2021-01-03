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
}
