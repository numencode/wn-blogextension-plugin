<?php

declare(strict_types=1);

namespace NumenCode\BlogExtension\Components;

use Illuminate\Database\Eloquent\Collection;
use NumenCode\BlogExtension\Classes\BlogComponent;
use Winter\Blog\Models\Post;

/**
 * Published posts that share tags with the page's blog post, the most shared tags first.
 */
class TagRelated extends BlogComponent
{
    public ?Collection $posts = null;

    public ?string $noPostsMessage = null;

    public function componentDetails(): array
    {
        return [
            'name'        => self::LANG . 'tag_related.name',
            'description' => self::LANG . 'tag_related.description',
        ];
    }

    public function defineProperties(): array
    {
        return [
            'postAlias'      => $this->postAliasProperty(),
            'limit'          => $this->limitProperty(5, 'related_limit_description'),
            'noPostsMessage' => $this->noPostsMessageProperty(),
            'postPage'       => $this->pageProperty('post', 'blog/post'),
            'categoryPage'   => $this->pageProperty('category', 'blog/category'),
            'tagPage'        => $this->pageProperty('tag', 'blog/tag'),
        ];
    }

    protected function prepare(): void
    {
        $this->noPostsMessage = $this->property('noPostsMessage');
        $this->posts = new Collection();

        if (!$post = $this->findPost()) {
            return;
        }

        $tagIds = static::tagIds($post->tags);

        if (!$tagIds) {
            return;
        }

        $inTags = fn ($tags) => $tags->whereIn('numencode_blogextension_tags.id', $tagIds);

        $query = Post::isPublished()
            ->with($this->postRelations())
            ->where('id', '<>', $post->id)
            ->whereHas('tags', $inTags)
            ->withCount(['tags as shared_tags_count' => $inTags])
            ->orderByDesc('shared_tags_count')
            ->orderByDesc('published_at');

        if ($limit = (int) $this->property('limit')) {
            $query->limit($limit);
        }

        $this->posts = $query->get();
        $this->setPostUrls($this->posts);
    }
}
