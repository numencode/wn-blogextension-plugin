<?php

declare(strict_types=1);

namespace NumenCode\BlogExtension\Components;

use NumenCode\BlogExtension\Classes\BlogComponent;

/**
 * The estimated reading time of the page's blog post, in minutes.
 */
class ReadingTime extends BlogComponent
{
    public const DEFAULT_WORDS_PER_MINUTE = 200;

    public ?int $minutes = null;

    public ?int $words = null;

    public function componentDetails(): array
    {
        return [
            'name'        => self::LANG . 'reading_time.name',
            'description' => self::LANG . 'reading_time.description',
        ];
    }

    public function defineProperties(): array
    {
        return [
            'postAlias'      => $this->postAliasProperty(),
            'wordsPerMinute' => [
                'title'             => self::LANG . 'properties.words_per_minute',
                'description'       => self::LANG . 'properties.words_per_minute_description',
                'type'              => 'string',
                'default'           => (string) static::DEFAULT_WORDS_PER_MINUTE,
                'validationPattern' => '^[1-9][0-9]*$',
                'validationMessage' => self::LANG . 'properties.number_validation',
                'showExternalParam' => false,
            ],
        ];
    }

    protected function prepare(): void
    {
        if (!$post = $this->findPost()) {
            return;
        }

        $wordsPerMinute = (int) $this->property('wordsPerMinute') ?: static::DEFAULT_WORDS_PER_MINUTE;

        $this->words = static::countWords((string) ($post->content_html ?: $post->content));
        $this->minutes = max(1, (int) ceil($this->words / $wordsPerMinute));
    }

    /**
     * Counts the words of an HTML text; letters of any script count (č, š, ž, ä, …).
     */
    public static function countWords(string $html): int
    {
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return (int) preg_match_all("/[\p{L}\p{N}]+(?:['’\-][\p{L}\p{N}]+)*/u", $text);
    }
}
