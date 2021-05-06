<?php namespace NumenCode\BlogExtension\Classes;

use Event;
use Backend;
use BackendAuth;
use NumenCode\BlogExtension\Models\Settings as BlogSettings;

class ExtendBlogNavigation
{
    public function init()
    {
        Event::listen('backend.menu.extendItems', function ($manager) {
            $backendUser = BackendAuth::getUser();

            if ($backendUser && $backendUser->hasAccess('numencode.blogextension.access_tags') && BlogSettings::get('extension_tags')) {
                $manager->addSideMenuItems('Winter.Blog', 'blog', [
                    'tags' => [
                        'label' => 'numencode.blogextension::lang.tags.title',
                        'icon'  => 'icon-tags',
                        'code'  => 'tags',
                        'owner' => 'Winter.Blog',
                        'url'   => Backend::url('numencode/blogextension/tags'),
                    ]
                ]);
            }
        });
    }
}
