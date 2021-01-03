<?php namespace NumenCode\BlogExtension\Models;

use Model;
use October\Rain\Database\Traits\Sortable;
use October\Rain\Database\Traits\Validation;

class Picture extends Model
{
    use Sortable, Validation;

    public $table = 'numencode_blogextension_pictures';

    public $implement = ['@RainLab.Translate.Behaviors.TranslatableModel'];

    public $rules = [];

    protected $fillable = [
        'title',
        'picture',
        'is_published',
        'sort_order',
    ];

    public $translatable = [
        'title',
    ];
}
