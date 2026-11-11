<?php

declare(strict_types=1);

namespace NumenCode\BlogExtension\Tests\Updates;

use Db;
use NumenCode\BlogExtension\Tests\BlogExtensionTestCase;
use NumenCode\BlogExtension\Updates\V200UpgradeTables;

require_once __DIR__ . '/../../updates/v2_0_0_upgrade_tables.php';

class MigrationTest extends BlogExtensionTestCase
{
    public function testUpgradeFixesTagsAndMovesTheContentEditorSetting(): void
    {
        $migration = new V200UpgradeTables();
        $migration->down();

        Db::table('numencode_blogextension_tags')->insert([
            ['id' => 1, 'name' => 'Dup', 'slug' => 'dup'],
            ['id' => 2, 'name' => 'Dup', 'slug' => 'dup'],
            ['id' => 3, 'name' => 'No Slug', 'slug' => null],
            ['id' => 4, 'name' => '', 'slug' => ''],
        ]);

        $postId = Db::table('winter_blog_posts')->insertGetId(['title' => 'P', 'slug' => 'p', 'content' => 'x']);
        Db::table('numencode_blogextension_pictures')->insert([
            ['post_id' => $postId, 'title' => 'Kept', 'picture' => '/a.jpg', 'is_published' => true],
            ['post_id' => 99999, 'title' => 'Orphan', 'picture' => '/b.jpg', 'is_published' => true],
        ]);

        Db::table('system_settings')->insert([
            'item'  => 'numencode_blogextension_settings',
            'value' => json_encode(['editor_content_wysiwyg' => '1', 'extension_tags' => '1']),
        ]);

        $migration->up();

        $this->assertSame(
            [1 => 'dup', 2 => 'dup-2', 3 => 'no-slug', 4 => 'tag-4'],
            Db::table('numencode_blogextension_tags')->orderBy('id')->pluck('slug', 'id')->all()
        );
        $this->assertSame('tag-4', Db::table('numencode_blogextension_tags')->where('id', 4)->value('name'));

        $this->assertSame(['Kept'], Db::table('numencode_blogextension_pictures')->pluck('title')->all());

        $settings = json_decode(Db::table('system_settings')->where('item', 'numencode_blogextension_settings')->value('value'), true);
        $blog = json_decode(Db::table('system_settings')->where('item', 'winter_blog_settings')->value('value'), true);
        $this->assertSame(['extension_tags' => '1'], $settings);
        $this->assertTrue($blog['use_rich_editor']);
    }

    public function testNewAttachmentsArePublishedByDefault(): void
    {
        $postId = Db::table('winter_blog_posts')->insertGetId(['title' => 'P', 'slug' => 'p', 'content' => 'x']);
        Db::table('numencode_blogextension_files')->insert(['post_id' => $postId, 'title' => 'F', 'file' => '/f.pdf']);

        $this->assertSame(1, (int) Db::table('numencode_blogextension_files')->value('is_published'));
    }
}
