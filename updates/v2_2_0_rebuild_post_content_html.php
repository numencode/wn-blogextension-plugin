<?php

declare(strict_types=1);

namespace NumenCode\BlogExtension\Updates;

use Db;
use Schema;
use Winter\Blog\Models\Post;
use Winter\Storm\Database\Updates\Migration;

/**
 * 2.2.0: rebuilds the rendered content ("content_html") of blog posts from their content, in the default language and
 * in every translation. With Winter.Translate 2.4, saving a translated post could store another language's
 * content_html as the default language's, and translations had no content_html of their own.
 */
class V220RebuildPostContentHtml extends Migration
{
    protected const POSTS = 'winter_blog_posts';

    protected const TRANSLATIONS = 'winter_translate_attributes';

    public function up(): void
    {
        if (!Schema::hasTable(self::POSTS) || !class_exists(Post::class)) {
            return;
        }

        foreach (Db::table(self::POSTS)->select(['id', 'content', 'content_html'])->orderBy('id')->cursor() as $row) {
            $html = trim(Post::formatHtml((string) $row->content));

            if ($html !== $row->content_html) {
                Db::table(self::POSTS)->where('id', $row->id)->update(['content_html' => $html]);
            }
        }

        if (!Schema::hasTable(self::TRANSLATIONS)) {
            return;
        }

        $rows = Db::table(self::TRANSLATIONS)
            ->where('model_type', Post::class)
            ->select(['id', 'attribute_data'])
            ->orderBy('id')
            ->get();

        foreach ($rows as $row) {
            $data = json_decode((string) $row->attribute_data, true);

            if (!is_array($data)) {
                continue;
            }

            $content = trim((string) ($data['content'] ?? ''));
            $html = $content !== '' ? trim(Post::formatHtml((string) $data['content'])) : '';

            if (($data['content_html'] ?? '') === $html) {
                continue;
            }

            // Without its own content, a translation shows the default language's content_html
            $data['content_html'] = $html;

            Db::table(self::TRANSLATIONS)->where('id', $row->id)->update([
                'attribute_data' => json_encode($data, JSON_UNESCAPED_UNICODE),
            ]);
        }
    }

    public function down(): void
    {
        // The rebuilt HTML is correct; nothing to undo
    }
}
