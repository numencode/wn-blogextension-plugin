<?php

declare(strict_types=1);

namespace NumenCode\BlogExtension\Updates;

use Db;
use Schema;
use Winter\Storm\Database\Schema\Blueprint;
use Winter\Storm\Database\Updates\Migration;
use Winter\Storm\Support\Str;

/**
 * 2.0.0:
 * - tags: name and slug up to 255 characters, slugs are required and unique (empty slugs are generated from the
 *   name, duplicates get the tag ID appended);
 * - pictures and files: published by default; rows of deleted posts are removed, and a foreign key deletes them
 *   together with their post from now on;
 * - the "WYSIWYG content" setting is replaced by Winter.Blog's own "use rich editor" setting (a stored "on" is
 *   copied there).
 */
class V200UpgradeTables extends Migration
{
    protected const TAGS = 'numencode_blogextension_tags';

    protected const ATTACHMENTS = ['numencode_blogextension_pictures', 'numencode_blogextension_files'];

    protected const SETTINGS = 'numencode_blogextension_settings';

    protected const BLOG_SETTINGS = 'winter_blog_settings';

    public function up(): void
    {
        $this->moveContentEditorSetting();
        $this->fixTagSlugs();

        Schema::table(self::TAGS, function (Blueprint $table) {
            $table->dropIndex(['slug']);
        });

        Schema::table(self::TAGS, function (Blueprint $table) {
            $table->string('name', 255)->change();
            $table->string('slug', 255)->change();
            $table->unique('slug');
        });

        foreach (self::ATTACHMENTS as $tableName) {
            Db::table($tableName)
                ->whereNotIn('post_id', Db::table('winter_blog_posts')->select('id'))
                ->delete();

            Schema::table($tableName, function (Blueprint $table) {
                $table->boolean('is_published')->default(true)->change();
                $table->foreign('post_id')->references('id')->on('winter_blog_posts')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        if (Db::getDriverName() !== 'sqlite') {
            foreach (self::ATTACHMENTS as $tableName) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropForeign(['post_id']);
                });
            }
        }

        Schema::table(self::TAGS, function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->index('slug');
        });
    }

    /**
     * Removes the "editor_content_wysiwyg" setting; when it was on, Winter.Blog's "use_rich_editor" is switched on.
     */
    protected function moveContentEditorSetting(): void
    {
        $settings = $this->readSettings(self::SETTINGS);

        if (!array_key_exists('editor_content_wysiwyg', $settings)) {
            return;
        }

        if ($settings['editor_content_wysiwyg']) {
            $this->writeSettings(self::BLOG_SETTINGS, ['use_rich_editor' => true] + $this->readSettings(self::BLOG_SETTINGS));
        }

        unset($settings['editor_content_wysiwyg']);
        $this->writeSettings(self::SETTINGS, $settings);
    }

    /**
     * @return array<string, mixed>
     */
    protected function readSettings(string $item): array
    {
        $value = Db::table('system_settings')->where('item', $item)->value('value');
        $data = is_string($value) ? json_decode($value, true) : null;

        return is_array($data) ? $data : [];
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function writeSettings(string $item, array $data): void
    {
        Db::table('system_settings')->updateOrInsert(['item' => $item], ['value' => json_encode($data)]);
    }

    /**
     * Gives every tag a unique, non-empty slug (and a name) before the unique index is added.
     */
    protected function fixTagSlugs(): void
    {
        $used = [];

        foreach (Db::table(self::TAGS)->orderBy('id')->get() as $tag) {
            $name = trim((string) $tag->name);
            $slug = trim((string) $tag->slug) ?: Str::slug($name);

            if ($slug === '' || isset($used[strtolower($slug)])) {
                $slug = trim(($slug !== '' ? $slug : 'tag') . '-' . $tag->id, '-');
            }

            $used[strtolower($slug)] = true;

            if ($slug !== $tag->slug || $name === '') {
                Db::table(self::TAGS)->where('id', $tag->id)->update([
                    'name' => $name !== '' ? $name : $slug,
                    'slug' => $slug,
                ]);
            }
        }
    }
}
