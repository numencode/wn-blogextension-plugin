<?php namespace NumenCode\BlogExtension\Classes;

use RainLab\Blog\Models\Post;
use NumenCode\Fundamentals\Bootstrap\ConfigOverride;

class ExtendBlogPostFields
{
    public function init()
    {
        ConfigOverride::extendFields(Post::class, function ($config) {
            unset($config['secondaryTabs']['fields']['featured_images']);

            $config['secondaryTabs']['fields'] = array_merge($config['secondaryTabs']['fields'], [
                'pictures_list' => [
                    'tab'   => 'numencode.blogextension::lang.tabs.pictures',
                    'label' => 'numencode.blogextension::lang.pictures.gallery',
                    'type'  => 'repeater',
                    'span'  => 'auto',
                    'form'  => [
                        'fields' => [
                            'id'           => [
                                'label'    => 'ID',
                                'type'     => 'number',
                                'cssClass' => 'hidden',
                            ],
                            'title'        => [
                                'label' => 'numencode.blogextension::lang.pictures.title',
                                'type'  => 'text',
                                'span'  => 'full',
                            ],
                            'is_published' => [
                                'label'   => 'numencode.blogextension::lang.pictures.is_published',
                                'comment' => 'numencode.blogextension::lang.pictures.is_published_comment',
                                'type'    => 'switch',
                                'span'    => 'auto',
                                'default' => true,
                            ],
                            'picture'      => [
                                'label' => 'numencode.blogextension::lang.pictures.picture',
                                'type'  => 'mediafinder',
                                'mode'  => 'image',
                                'span'  => 'auto',
                            ],
                        ],
                    ],
                ],
                'files_list'    => [
                    'tab'   => 'numencode.blogextension::lang.tabs.files',
                    'label' => 'numencode.blogextension::lang.files.label',
                    'type'  => 'repeater',
                    'span'  => 'auto',
                    'form'  => [
                        'fields' => [
                            'id'           => [
                                'label'    => 'ID',
                                'type'     => 'number',
                                'cssClass' => 'hidden',
                            ],
                            'title'        => [
                                'label' => 'numencode.blogextension::lang.files.title',
                                'type'  => 'text',
                                'span'  => 'full',
                            ],
                            'is_published' => [
                                'label'   => 'numencode.blogextension::lang.files.is_published',
                                'comment' => 'numencode.blogextension::lang.files.is_published_comment',
                                'type'    => 'switch',
                                'span'    => 'auto',
                                'default' => true,
                            ],
                            'file'         => [
                                'label' => 'numencode.blogextension::lang.files.file',
                                'type'  => 'mediafinder',
                                'mode'  => 'file',
                                'span'  => 'auto',
                            ],
                        ],
                    ],
                ],
            ]);

            $config['secondaryTabs']['fields']['published']['span'] = 'auto';
            $config['secondaryTabs']['fields']['published']['comment'] = 'numencode.fundamentals::lang.field.is_published_comment';
            $config['secondaryTabs']['fields']['user']['span'] = 'auto';
            $config['secondaryTabs']['fields']['published_at']['span'] = 'auto';
            $config['secondaryTabs']['fields']['excerpt']['span'] = 'full';

            $config['secondaryTabs']['fields'] = array_move_element_before($config['secondaryTabs']['fields'], 'user', 'published_at');
            $config['secondaryTabs']['fields'] = array_move_element_before($config['secondaryTabs']['fields'], 'user', 'excerpt');

            return $config;
        });
    }
}
