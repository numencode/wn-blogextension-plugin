<?php

declare(strict_types=1);

namespace NumenCode\BlogExtension\Components;

use Illuminate\Database\Eloquent\Collection;
use NumenCode\BlogExtension\Classes\BlogComponent;

/**
 * The tags of the page's blog post.
 */
class TagPost extends BlogComponent
{
    public ?Collection $tags = null;

    public function componentDetails(): array
    {
        return [
            'name'        => self::LANG . 'tag_post.name',
            'description' => self::LANG . 'tag_post.description',
        ];
    }

    public function defineProperties(): array
    {
        return [
            'postAlias' => $this->postAliasProperty(),
            'tagPage'   => $this->pageProperty('tag', 'blog/tag'),
        ];
    }

    protected function prepare(): void
    {
        $post = $this->findPost();

        $this->tags = $post?->tags;

        if ($this->tags) {
            $this->setTagUrls($this->tags);
        }
    }
}
