<?php

declare(strict_types=1);

namespace NumenCode\BlogExtension\Models;

/**
 * A picture of a blog post's gallery (a media library path in "picture").
 */
class Picture extends PostAttachment
{
    public $table = 'numencode_blogextension_pictures';

    public $rules = [
        'picture' => 'required',
    ];

    protected $fillable = [
        'title',
        'picture',
        'is_published',
    ];
}
