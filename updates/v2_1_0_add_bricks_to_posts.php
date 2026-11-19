<?php

declare(strict_types=1);

namespace NumenCode\BlogExtension\Updates;

use Schema;
use Winter\Storm\Database\Schema\Blueprint;
use Winter\Storm\Database\Updates\Migration;

/**
 * 2.1.0: a "bricks" column on blog posts, for NumenCode.Bricks (a JSON list of bricks; unused without that plugin).
 */
class V210AddBricksToPosts extends Migration
{
    protected const POSTS = 'winter_blog_posts';

    public function up(): void
    {
        if (Schema::hasTable(self::POSTS) && !Schema::hasColumn(self::POSTS, 'bricks')) {
            Schema::table(self::POSTS, function (Blueprint $table) {
                $table->mediumText('bricks')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable(self::POSTS) && Schema::hasColumn(self::POSTS, 'bricks')) {
            Schema::table(self::POSTS, function (Blueprint $table) {
                $table->dropColumn('bricks');
            });
        }
    }
}
