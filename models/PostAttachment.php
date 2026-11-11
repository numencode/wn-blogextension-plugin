<?php

declare(strict_types=1);

namespace NumenCode\BlogExtension\Models;

use Model;
use NumenCode\Fundamentals\Traits\Publishable;
use Winter\Blog\Models\Post;
use Winter\Storm\Database\Traits\Sortable;
use Winter\Storm\Database\Traits\Validation;

/**
 * Base model of a media library item attached to a blog post (a gallery picture or a file).
 *
 * The items are edited inline in the post form (relationrepeater fields "pictures_list" and "files_list").
 * Unpublished items are only visible to backend users.
 */
abstract class PostAttachment extends Model
{
    use Publishable;
    use Sortable;
    use Validation;

    public $implement = ['@Winter.Translate.Behaviors.TranslatableModel'];

    public $translatable = ['title'];

    /**
     * New records are published by default.
     */
    public $attributes = [
        'is_published' => true,
    ];

    protected $casts = [
        'is_published' => 'boolean',
    ];

    public $belongsTo = [
        'post' => [Post::class, 'key' => 'post_id'],
    ];
}
