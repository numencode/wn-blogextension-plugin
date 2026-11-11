<?php

declare(strict_types=1);

namespace NumenCode\BlogExtension\Classes;

use Backend\Models\User;
use Carbon\Carbon;
use Faker\Factory;
use Faker\Generator;
use Illuminate\Support\Facades\Config;
use NumenCode\BlogExtension\Models\Picture;
use NumenCode\BlogExtension\Models\Tag;
use System\Classes\MediaLibrary;
use System\Classes\MediaLibraryItem;
use Winter\Blog\Models\Category;
use Winter\Blog\Models\Post;
use Winter\Storm\Support\Str;

/**
 * Generates demo blog data (categories, tags, posts and gallery pictures) with fakerphp/faker.
 *
 * Only available while app.debug is on and fakerphp/faker is installed (it's a development tool).
 */
class FakerGenerator
{
    /**
     * Upper limits of the amounts per run.
     */
    public const MAX_CATEGORIES = 50;

    public const MAX_TAGS = 50;

    public const MAX_POSTS = 200;

    public const MIN_CONTENT = 100;

    public const MAX_CONTENT = 20000;

    protected Generator $faker;

    public function __construct(?Generator $faker = null)
    {
        $this->faker = $faker ?? Factory::create();
    }

    public static function isAvailable(): bool
    {
        return (bool) Config::get('app.debug') && class_exists(Factory::class);
    }

    /**
     * Generates the data and returns the number of created records per type.
     *
     * Options: categories, tags, posts (amounts), content (characters per post), paragraphs (bool),
     * pictures (bool), media_folder (media library folder of the pictures).
     *
     * @param array<string, mixed> $options
     * @return array{categories: int, tags: int, posts: int, pictures: int}
     */
    public function generate(array $options, ?User $author = null): array
    {
        $options = static::normalizeOptions($options);

        $categories = $this->generateCategories($options['categories']);
        $tags = $this->generateTags($options['tags']);
        $pictures = $options['pictures'] ? static::listImages($options['media_folder']) : [];

        $result = ['categories' => count($categories), 'tags' => count($tags), 'posts' => 0, 'pictures' => 0];

        $categoryIds = Category::pluck('id')->all();
        $tagIds = Tag::pluck('id')->all();

        for ($i = 0; $i < $options['posts']; $i++) {
            $post = $this->createPost($options, $author);
            $result['posts']++;

            if ($categoryIds) {
                $post->categories()->attach($this->faker->randomElement($categoryIds));
            }

            if ($tagIds) {
                $post->tags()->attach($this->faker->randomElements($tagIds, min(count($tagIds), $this->faker->numberBetween(1, 3))));
            }

            if ($pictures) {
                foreach ($this->faker->randomElements($pictures, min(count($pictures), $this->faker->numberBetween(1, 3))) as $path) {
                    $post->pictures()->save(new Picture(['title' => $this->faker->sentence(3), 'picture' => $path]));
                    $result['pictures']++;
                }
            }
        }

        return $result;
    }

    /**
     * Clamps the amounts to their limits and fills in defaults.
     *
     * @param array<string, mixed> $options
     * @return array{categories: int, tags: int, posts: int, content: int, paragraphs: bool, pictures: bool, media_folder: string}
     */
    public static function normalizeOptions(array $options): array
    {
        $int = fn (string $key, int $min, int $max, int $default): int => max($min, min($max, (int) ($options[$key] ?? $default)));

        return [
            'categories'   => $int('categories', 0, static::MAX_CATEGORIES, 0),
            'tags'         => $int('tags', 0, static::MAX_TAGS, 0),
            'posts'        => $int('posts', 0, static::MAX_POSTS, 0),
            'content'      => $int('content', static::MIN_CONTENT, static::MAX_CONTENT, 1000),
            'paragraphs'   => !empty($options['paragraphs']),
            'pictures'     => !empty($options['pictures']),
            'media_folder' => '/' . trim((string) ($options['media_folder'] ?? '/'), '/'),
        ];
    }

    /**
     * Media library folders for the pictures dropdown.
     *
     * @return array<string, string>
     */
    public static function listMediaFolders(): array
    {
        $folders = array_merge(['/'], MediaLibrary::instance()->listAllDirectories());

        return array_combine($folders, $folders);
    }

    /**
     * Paths of the images in a media library folder (not recursive).
     *
     * @return array<int, string>
     */
    public static function listImages(string $folder): array
    {
        $items = MediaLibrary::instance()->listFolderContents($folder, 'title', MediaLibraryItem::FILE_TYPE_IMAGE, true);

        return array_values(array_map(fn (MediaLibraryItem $item): string => $item->path, $items));
    }

    /**
     * @return array<int, Category>
     */
    protected function generateCategories(int $amount): array
    {
        $categories = [];

        for ($i = 0; $i < $amount; $i++) {
            $name = $this->uniqueValue(fn (): string => Str::title($this->faker->words(2, true)), Category::class, 'name');

            $categories[] = Category::create([
                'name'        => $name,
                'slug'        => Str::slug($name),
                'description' => $this->faker->realText(100),
            ]);
        }

        return $categories;
    }

    /**
     * @return array<int, Tag>
     */
    protected function generateTags(int $amount): array
    {
        $tags = [];

        for ($i = 0; $i < $amount; $i++) {
            $tags[] = Tag::create([
                'name' => $this->uniqueValue(fn (): string => $this->faker->word(), Tag::class, 'name'),
            ]);
        }

        return $tags;
    }

    /**
     * @param array{content: int, paragraphs: bool} $options
     */
    protected function createPost(array $options, ?User $author): Post
    {
        $title = rtrim($this->faker->realText(50), '.');
        $content = $this->faker->realText($options['content']);

        $post = new Post();
        $post->title = $title;
        $post->slug = $this->uniqueValue(fn (int $attempt): string => Str::slug($title) . ($attempt ? '-' . ($attempt + 1) : ''), Post::class, 'slug');
        $post->excerpt = $this->faker->realText(150);
        $post->content = $options['paragraphs'] ? static::toParagraphs($content) : $content;
        $post->published = true;
        $post->published_at = Carbon::now()->subMinutes($this->faker->numberBetween(1, 60 * 24 * 365));

        if ($author) {
            $post->user_id = $author->id;
        }

        $post->save();

        return $post;
    }

    /**
     * Splits text into paragraphs of about 500 characters, as HTML.
     */
    public static function toParagraphs(string $text): string
    {
        $paragraphs = array_filter(array_map('trim', explode("\n", wordwrap($text, 500, "\n"))));

        return implode("\n", array_map(fn (string $paragraph): string => '<p>' . htmlspecialchars($paragraph, ENT_NOQUOTES) . '</p>', $paragraphs));
    }

    /**
     * Returns a value from the generator (called with the attempt number) that doesn't exist yet in the column.
     */
    protected function uniqueValue(callable $generator, string $modelClass, string $column): string
    {
        for ($attempt = 0; $attempt < 20; $attempt++) {
            $value = $generator($attempt);

            if (!$modelClass::where($column, $value)->exists()) {
                return $value;
            }
        }

        return $value . '-' . Str::lower(Str::random(6));
    }
}
