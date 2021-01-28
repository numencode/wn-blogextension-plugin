<?php namespace NumenCode\BlogExtension\Components;

use Cms\Classes\Page;
use Cms\Classes\ComponentBase;

class TagPost extends ComponentBase
{
    public $tags;

    public function componentDetails(): array
    {
        return [
            'name'        => 'numencode.blogextension::lang.tag_post.name',
            'description' => 'numencode.blogextension::lang.tag_post.description',
        ];
    }

    public function defineProperties(): array
    {
        return [
            'postAlias'     => [
                'title'             => 'numencode.blogextension::lang.aliases.post_title',
                'description'       => 'numencode.blogextension::lang.aliases.post_description',
                'type'              => 'string',
                'default'           => 'blogPost',
                'showExternalParam' => false,
            ],
            'tagFilterPage' => [
                'title'       => 'numencode.blogextension::lang.pages.tag_filter_title',
                'description' => 'numencode.blogextension::lang.pages.tag_filter_description',
                'type'        => 'dropdown',
                'placeholder' => 'blog/tag',
                'group'       => 'numencode.blogextension::lang.groups.links',
            ],
        ];
    }

    public function onRun()
    {
        if (!isset($this->page->components[$this->property('postAlias')])) {
            return;
        }

        $this->tags = $this->loadTags();
    }

    protected function loadTags()
    {
        $tags = $this->page->components[$this->property('postAlias')]->post->tags;

        if ($tagFilterPage = $this->property('tagFilterPage')) {
            $tags->each(function ($tag) use ($tagFilterPage) {
                $tag->setUrl($tagFilterPage, $this->controller);
            });
        }

        return $tags;
    }

    public function getTagFilterPageOptions(): array
    {
        return array_merge([false => '---'], Page::sortBy('baseFileName')->lists('baseFileName', 'baseFileName'));
    }
}
