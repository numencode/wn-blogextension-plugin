<?php namespace NumenCode\BlogExtension\Classes;

use Event;
use BackendAuth;
use Winter\Blog\Models\Post;
use NumenCode\Fundamentals\Bootstrap\ConfigOverride;
use NumenCode\BlogExtension\Models\Settings as BlogSettings;

class ExtendBlogPostFields
{
    public function init()
    {
        $this->beautifyForm();
        $this->addTags();
        $this->addPictureGallery();
        $this->addFileAttachments();
        $this->prepareContentEditor();
        $this->prepareExcerptEditor();
    }

    protected function beautifyForm()
    {
        ConfigOverride::extendFields(Post::class, function ($config) {
            $config['secondaryTabs']['fields']['published']['span'] = 'auto';
            $config['secondaryTabs']['fields']['published']['comment'] = 'numencode.fundamentals::lang.field.is_published_comment';
            $config['secondaryTabs']['fields']['user']['span'] = 'auto';
            $config['secondaryTabs']['fields']['published_at']['span'] = 'auto';
            $config['secondaryTabs']['fields']['excerpt']['span'] = 'full';
            $config['secondaryTabs']['fields']['categories']['span'] = 'auto';

            $config['secondaryTabs']['fields'] = array_move_element_before($config['secondaryTabs']['fields'], 'user', 'published_at');
            $config['secondaryTabs']['fields'] = array_move_element_before($config['secondaryTabs']['fields'], 'user', 'excerpt');

            return $config;
        });
    }

    protected function addTags()
    {
        ConfigOverride::extendFields(Post::class, function ($config) {
            if ($this->hasTagsAccess()) {
                $config['secondaryTabs']['fields'] = array_merge($config['secondaryTabs']['fields'], [
                    'tags' => [
                        'tab'   => 'winter.blog::lang.post.tab_categories',
                        'label' => 'numencode.blogextension::lang.tags.label',
                        'type'  => 'taglist',
                        'mode'  => 'relation',
                        'span'  => 'full',
                    ],
                ]);
            }

            return $config;
        });
    }

    protected function addPictureGallery()
    {
        ConfigOverride::extendFields(Post::class, function ($config) {
            if ($this->hasPicturesAccess()) {
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
                ]);
            }

            return $config;
        });
    }
    protected function addFileAttachments()
    {
        ConfigOverride::extendFields(Post::class, function ($config) {
            if ($this->hasFilesAccess()) {
                $config['secondaryTabs']['fields'] = array_merge($config['secondaryTabs']['fields'], [
                    'files_list' => [
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
            }

            return $config;
        });
    }

    public function prepareContentEditor()
    {
        if (!BlogSettings::get('editor_content_wysiwyg')) {
            return;
        }

        Event::listen('backend.form.extendFields', function ($form) {
            if ($form->model instanceof \Winter\Blog\Models\Post) {
                $replaceable = [
                    'codeeditor',
                    'richeditor',
                    'Winter\Blog\FormWidgets\BlogMarkdown',
                    'Winter\Blog\FormWidgets\MLBlogMarkdown',
                    'mlricheditor',
                ];

                $multilingual = [
                    'Winter\Blog\FormWidgets\MLBlogMarkdown',
                    'mlricheditor',
                ];

                foreach ($form->getFields() as $field) {
                    if (!empty($field->config['type']) && in_array($field->config['type'], $replaceable)) {
                        $editor = in_array($field->config['type'], $multilingual) ? 'mlricheditor' : 'richeditor';
                        $field->config['type'] = $field->config['widget'] = $editor;

                        return;
                    }
                }
            }
        });
    }

    protected function prepareExcerptEditor()
    {
        if (!BlogSettings::get('editor_excerpt_wysiwyg')) {
            return;
        }

        ConfigOverride::extendFields(Post::class, function ($config) {
            $config['secondaryTabs']['fields']['excerpt']['type'] = 'richeditor';
            $config['secondaryTabs']['fields']['excerpt']['size'] = 'large';

            return $config;
        });
    }

    protected function hasTagsAccess(): bool
    {
        $backendUser = BackendAuth::getUser();

        return $backendUser && $backendUser->hasAccess('numencode.blogextension.access_tags') && BlogSettings::get('extension_tags');
    }

    protected function hasPicturesAccess(): bool
    {
        $backendUser = BackendAuth::getUser();

        return $backendUser && $backendUser->hasAccess('numencode.blogextension.access_pictures') && BlogSettings::get('extension_pictures');
    }

    protected function hasFilesAccess(): bool
    {
        $backendUser = BackendAuth::getUser();

        return $backendUser && $backendUser->hasAccess('numencode.blogextension.access_files') && BlogSettings::get('extension_files');
    }
}
