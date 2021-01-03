<?php

return [
    'plugin'   => [
        'name'        => 'Blog Extension',
        'description' => 'NumenCode Blog Extension plugin extends the RainLab.Blog plugin.',
    ],
    'tabs'     => [
        'pictures' => 'Pictures',
        'files'    => 'Files',
    ],
    'pictures' => [
        'gallery'              => 'Picture gallery',
        'title'                => 'Title',
        'picture'              => 'Picture',
        'is_published'         => 'Published?',
        'is_published_comment' => 'Unpublished pictures can only be viewed by administrators.',
    ],
    'files'    => [
        'label'                => 'Attached files',
        'title'                => 'Title',
        'file'                 => 'File',
        'is_published'         => 'Published?',
        'is_published_comment' => 'Unpublished files can only be viewed by administrators.',
    ],
];
