<?php

declare(strict_types=1);

namespace NumenCode\BlogExtension\Controllers;

use Backend\Classes\Controller;
use BackendMenu;

/**
 * Blog post tags (Blog → Tags).
 */
class Tags extends Controller
{
    public $implement = [
        \Backend\Behaviors\FormController::class,
        \Backend\Behaviors\ListController::class,
    ];

    public $formConfig = 'config_form.yaml';

    public $listConfig = 'config_list.yaml';

    protected $requiredPermissions = ['numencode.blogextension.access_tags'];

    public function __construct()
    {
        parent::__construct();

        BackendMenu::setContext('Winter.Blog', 'blog', 'tags');
    }
}
