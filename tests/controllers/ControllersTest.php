<?php

declare(strict_types=1);

namespace NumenCode\BlogExtension\Tests\Controllers;

use NumenCode\BlogExtension\Controllers\Faker;
use NumenCode\BlogExtension\Controllers\Tags;
use NumenCode\BlogExtension\Plugin;
use NumenCode\BlogExtension\Tests\BlogExtensionTestCase;
use ReflectionProperty;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ControllersTest extends BlogExtensionTestCase
{
    protected function requiredPermissions(object $controller): array
    {
        return (new ReflectionProperty($controller, 'requiredPermissions'))->getValue($controller);
    }

    public function testPermissions(): void
    {
        $this->loginBackendUser([], true);

        $this->assertSame(['numencode.blogextension.access_tags'], $this->requiredPermissions(new Tags()));
        $this->assertSame(['numencode.blogextension.access_faker'], $this->requiredPermissions(new Faker()));
    }

    public function testFakerIsNotFoundWithoutDebugMode(): void
    {
        $this->loginBackendUser([], true);
        config(['app.debug' => false]);

        try {
            (new Faker())->run('index');
            $this->fail('The Faker page must not be reachable without debug mode');
        } catch (HttpException $e) {
            $this->assertSame(404, $e->getStatusCode());
        }
    }

    public function testFakerDeniesUsersWithoutThePermission(): void
    {
        $this->loginBackendUser(['numencode.blogextension.access_tags' => 1]);
        config(['app.debug' => true]);

        try {
            (new Faker())->run('index');
            $this->fail('Users without access_faker must be denied');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }
    }

    public function testRegistration(): void
    {
        $plugin = new Plugin($this->app);

        $this->assertSame(['Winter.Blog', 'NumenCode.Fundamentals'], $plugin->require);
        $this->assertSame(
            ['blogBreadcrumbs', 'blogReadingTime', 'blogRssFeed', 'blogTagList', 'blogTagPost', 'blogTagFilter', 'blogTagRelated'],
            array_values($plugin->registerComponents())
        );
        $this->assertSame(['developer'], $plugin->registerPermissions()['numencode.blogextension.access_faker']['roles']);
    }
}
