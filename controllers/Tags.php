<?php namespace NumenCode\BlogExtension\Controllers;

use BackendMenu;
use Backend\Classes\Controller;

class Tags extends Controller
{
    public $implement = [
        'Backend.Behaviors.FormController',
        'Backend.Behaviors.ListController',
    ];

    public $formConfig = 'config_form.yaml';
    public $listConfig = 'config_list.yaml';

    public $bodyClass = 'compact-container';

    public $requiredPermissions = [
        'numencode.blogextension.access_tags',
    ];

    public function __construct()
    {
        parent::__construct();

        BackendMenu::setContext('RainLab.Blog', 'blog', 'tags');
    }
}
