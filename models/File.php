<?php namespace NumenCode\BlogExtension\Models;

use Model;
use October\Rain\Database\Traits\Sortable;
use October\Rain\Database\Traits\Validation;

class File extends Model
{
    use Sortable, Validation;

    public $table = 'numencode_blogextension_files';

    public $implement = ['@RainLab.Translate.Behaviors.TranslatableModel'];

    public $rules = [];

    protected $fillable = [
        'title',
        'file',
        'is_published',
        'sort_order',
    ];

    public $translatable = [
        'title',
    ];
}
