<?php namespace NumenCode\BlogExtension\Models;

use Model;
use Winter\Storm\Database\Traits\Sortable;
use Winter\Storm\Database\Traits\Validation;

class Picture extends Model
{
    use Sortable, Validation;

    public $table = 'numencode_blogextension_pictures';

    public $implement = ['@Winter.Translate.Behaviors.TranslatableModel'];

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
