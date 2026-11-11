<?php

declare(strict_types=1);

namespace NumenCode\BlogExtension\Models;

/**
 * A file attached to a blog post (a media library path in "file").
 */
class File extends PostAttachment
{
    public $table = 'numencode_blogextension_files';

    public $rules = [
        'file' => 'required',
    ];

    protected $fillable = [
        'title',
        'file',
        'is_published',
    ];
}
