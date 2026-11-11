<?php

declare(strict_types=1);

namespace NumenCode\BlogExtension\Tests;

use Backend\Facades\BackendAuth;
use Backend\Models\User;
use Backend\Models\UserGroup;
use Cms\Classes\Controller;
use Cms\Classes\Theme;
use NumenCode\BlogExtension\Models\Tag;
use System\Tests\Bootstrap\PluginTestCase;
use Winter\Blog\Models\Category;
use Winter\Blog\Models\Post;

/**
 * Base test case: helpers for posts, tags, backend users and the fixture theme.
 */
abstract class BlogExtensionTestCase extends PluginTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        // The models soft-implement Translate's behavior; when the plugin is installed, its tables are needed
        if (class_exists(\Winter\Translate\Plugin::class)) {
            $this->instantiatePlugin('Winter.Translate');
        }
    }

    public function tearDown(): void
    {
        BackendAuth::logout();
        \System\Behaviors\SettingsModel::clearInternalCache();

        parent::tearDown();
    }

    /**
     * Creates a published post (or as given) with pictures, files, tags and categories.
     *
     * @param array<string, mixed> $attributes
     * @param array<int, array<string, mixed>> $pictures
     * @param array<int, Tag> $tags
     * @param array<int, Category> $categories
     */
    protected function makePost(
        string $title,
        array $attributes = [],
        array $pictures = [],
        array $tags = [],
        array $categories = []
    ): Post {
        $post = new Post();
        $post->title = $title;
        $post->slug = str_slug($title);
        $post->content = $attributes['content'] ?? 'Content of ' . $title;
        $post->published = $attributes['published'] ?? true;
        $post->published_at = $attributes['published_at'] ?? now()->subDay();
        $post->pictures_list = $pictures;
        $post->save();

        foreach ($tags as $tag) {
            $post->tags()->attach($tag->id);
        }

        foreach ($categories as $category) {
            $post->categories()->attach($category->id);
        }

        return Post::find($post->id);
    }

    protected function makeTag(string $name): Tag
    {
        return Tag::create(['name' => $name]);
    }

    protected function makeCategory(string $name, ?Category $parent = null): Category
    {
        $category = Category::create(['name' => $name, 'slug' => str_slug($name)]);

        if ($parent) {
            $category->makeChildOf($parent);
        }

        return Category::find($category->id);
    }

    /**
     * Logs in a backend user with the given permissions (or a superuser).
     *
     * @param array<string, int> $permissions
     */
    protected function loginBackendUser(array $permissions = [], bool $superUser = false): User
    {
        $group = UserGroup::firstOrCreate(['code' => 'testers'], ['name' => 'Testers']);

        $user = User::create([
            'first_name'            => 'Test',
            'last_name'             => 'User',
            'login'                 => 'tester',
            'email'                 => 'tester@example.com',
            'password'              => 'BlogExtension-Test-Password-1',
            'password_confirmation' => 'BlogExtension-Test-Password-1',
            'is_activated'          => true,
        ]);
        $user->permissions = $permissions;
        $user->is_superuser = $superUser;
        $user->forceSave();
        $user->groups()->attach($group);

        BackendAuth::setUser($user->fresh());

        return $user;
    }

    /**
     * Uses the fixture theme (tests/fixtures/themes/blog) as the active theme.
     */
    protected function useFixtureTheme(): void
    {
        $this->app->setThemesPath(plugins_path('numencode/blogextension/tests/fixtures/themes'));
        config(['cms.activeTheme' => 'blog']);
        Theme::resetCache();
    }

    /**
     * Runs a CMS page of the fixture theme and returns the response.
     */
    protected function runPage(string $url)
    {
        return (new Controller(Theme::getActiveTheme()))->run($url);
    }

    /**
     * Splits rendered output in the form NAME[content] into an array.
     *
     * @return array<string, string>
     */
    protected static function sections(string $html): array
    {
        preg_match_all('/([A-Z]+)\[(.*?)\]\n/s', $html . "\n", $matches, PREG_SET_ORDER);

        $sections = [];
        foreach ($matches as [, $name, $content]) {
            $sections[$name] = $content;
        }

        return $sections;
    }
}
