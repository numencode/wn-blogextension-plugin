<?php namespace NumenCode\BlogExtension\Controllers;

use Storage;
use Carbon\Carbon;
use Faker\Factory;
use RainLab\Blog\Models\Post;
use Backend\Classes\Controller;
use Backend\Facades\BackendAuth;
use RainLab\Blog\Models\Category;
use NumenCode\BlogExtension\Models\Picture;
use NumenCode\BlogExtension\Models\Settings;

class Faker extends Controller
{
    public $implement = [];

    protected $faker;
    protected $categories;
    protected $posts;
    protected $content;
    protected $paragraphs;
    protected $media;
    protected $pictures;
    protected $files;

    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        $this->prepareVars();
        $this->generateCategories();
        $this->generatePosts();
    }

    protected function prepareVars()
    {
        $this->faker = Factory::create();
        $this->categories = Settings::get('faker_categories') ?: 0;
        $this->posts = Settings::get('faker_posts') ?: 0;
        $this->content = Settings::get('faker_content') ?: 1000;
        $this->paragraphs = (bool)Settings::get('faker_paragraphs');
        $this->media = Settings::get('faker_media') ?: 'media/';
        $this->pictures = (bool)Settings::get('faker_pictures');
        $this->files = $this->prepareFiles();
    }

    protected function generateCategories()
    {
        if (!$this->categories) {
            return;
        }

        for ($i = 0; $i < $this->categories; $i++) {
            $name = $this->faker->streetName;

            Category::create([
                'name'        => $name,
                'slug'        => str_slug($name),
                'description' => $this->faker->realText(100),
            ]);
        }
    }

    protected function generatePosts()
    {
        if (!$this->posts) {
            return;
        }

        $blogCategories = Category::pluck('id')->toArray();

        for ($i = 0; $i < $this->posts; $i++) {
            $title = rtrim($this->faker->realText(40), '.');
            $content = $this->faker->realText($this->content);

            $post = Post::create([
                'user_id'      => BackendAuth::getUser()->id,
                'title'        => $title,
                'slug'         => str_slug($title),
                'excerpt'      => $this->faker->realText(100),
                'content'      => $this->paragraphs ? $this->prepareHtml($content) : $content,
                'published_at' => Carbon::now(),
                'published'    => true,
            ]);

            $post->categories()->attach($blogCategories[array_rand($blogCategories)]);

            if (!$this->pictures || empty($this->files)) {
                continue;
            }

            $post->pictures()->save(new Picture([
                'title'        => $this->faker->company,
                'picture'      => $this->files[array_rand($this->files)],
                'is_published' => true,
                'sort_order'   => 1,
            ]));
        }
    }

    protected function prepareHtml($content): string
    {
        $paragraphs = wordwrap($content, 500, "%%%");
        $chunks = explode("%%%", $paragraphs);

        foreach ($chunks as &$paragraph) {
            $paragraph = ucfirst($paragraph) . '.';
            $paragraph = str_replace(['* ', '..', ',.'], ['', '.', '.'], $paragraph);
            $paragraph = '<p>' . $paragraph . '</p>';
        }

        return implode('', $chunks);
    }

    protected function prepareFiles(): array
    {
        if (!$this->pictures) {
            return [];
        }

        $files = array_filter(Storage::allFiles(), function ($file) {
            return starts_with($file, $this->media) &&
                !str_contains($file, 'thumb') &&
                (str_ends_with($file, '.jpg') || str_ends_with($file, '.png'));
        });

        return array_map(function ($file) {
            return ltrim($file, 'media');
        }, $files);
    }
}
