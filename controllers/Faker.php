<?php

declare(strict_types=1);

namespace NumenCode\BlogExtension\Controllers;

use Backend\Classes\Controller;
use BackendAuth;
use BackendMenu;
use Flash;
use Lang;
use NumenCode\BlogExtension\Classes\FakerGenerator;

/**
 * Development tool (Blog → Faker): generates demo categories, tags, posts and pictures.
 *
 * Only reachable while app.debug is on and fakerphp/faker is installed; otherwise every request gets a 404.
 */
class Faker extends Controller
{
    protected $requiredPermissions = ['numencode.blogextension.access_faker'];

    public function __construct()
    {
        parent::__construct();

        BackendMenu::setContext('Winter.Blog', 'blog', 'faker');

        // Before pages and AJAX handlers (after the login and permission checks)
        $this->bindEvent('page.beforeDisplay', function (): void {
            if (!FakerGenerator::isAvailable()) {
                abort(404);
            }
        });
    }

    public function index(): void
    {
        $this->pageTitle = 'numencode.blogextension::lang.faker.title';
        $this->vars['defaults'] = FakerGenerator::normalizeOptions(['posts' => 10, 'pictures' => true]);
        $this->vars['limits'] = [
            'categories' => FakerGenerator::MAX_CATEGORIES,
            'tags'       => FakerGenerator::MAX_TAGS,
            'posts'      => FakerGenerator::MAX_POSTS,
            'content'    => [FakerGenerator::MIN_CONTENT, FakerGenerator::MAX_CONTENT],
        ];
        $this->vars['mediaFolders'] = FakerGenerator::listMediaFolders();
    }

    /**
     * AJAX handler of the "Generate" button.
     */
    public function onGenerate(): void
    {
        $result = (new FakerGenerator())->generate((array) post('Faker', []), BackendAuth::getUser());

        Flash::success(Lang::get('numencode.blogextension::lang.faker.generated', $result));
    }
}
