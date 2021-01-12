<?php namespace NumenCode\BlogExtension\Components;

use Cms\Classes\ComponentBase;

class ReadingTime extends ComponentBase
{
    const DEFAULT_READING_SPEED = 200;

    public $minutes;

    public function componentDetails(): array
    {
        return [
            'name'        => 'numencode.blogextension::lang.reading_time.name',
            'description' => 'numencode.blogextension::lang.reading_time.description',
        ];
    }

    public function defineProperties(): array
    {
        return [
            'postAlias'    => [
                'title'             => 'numencode.blogextension::lang.components.post',
                'description'       => 'numencode.blogextension::lang.components.post_description',
                'type'              => 'string',
                'default'           => 'blogPost',
                'showExternalParam' => false,
            ],
            'readingSpeed' => [
                'title'             => 'numencode.blogextension::lang.reading_time.speed',
                'description'       => 'numencode.blogextension::lang.reading_time.speed_comment',
                'default'           => static::DEFAULT_READING_SPEED,
                'type'              => 'string',
                'validationPattern' => '^(0+)?[1-9]\d*$',
                'showExternalParam' => false,
            ],
        ];
    }

    public function onRun()
    {
        $postAlias = $this->property('postAlias');
        $readingSpeed = $this->property('readingSpeed') ?: static::DEFAULT_READING_SPEED;

        if (!isset($this->page->components[$postAlias])) {
            return;
        }

        $post = $this->page->components[$postAlias]->post;

        $this->minutes = ceil(str_word_count(strip_tags($post->content)) / $readingSpeed);
    }
}
