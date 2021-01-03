<?php namespace NumenCode\BlogExtension\Classes;

use RainLab\Blog\Models\Category;
use NumenCode\Fundamentals\Bootstrap\ConfigOverride;

class ExtendBlogCategoryColumns
{
    public function init()
    {
        ConfigOverride::extendColumns(Category::class, function ($config) {
            $config['columns']['slug'] = [
                'slug' => [
                    'label'      => 'numencode.fundamentals::lang.field.slug',
                    'type'       => 'text',
                    'searchable' => 'true',
                    'sortable'   => 'true',
                ],
            ];

            return $config;
        });
    }
}
