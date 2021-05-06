<?php namespace NumenCode\BlogExtension\Updates;

use Schema;
use Winter\Storm\Database\Updates\Migration;

class CreateNumencodeBlogextensionPicturesTable extends Migration
{
    public function up()
    {
        Schema::create('numencode_blogextension_pictures', function ($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->integer('post_id')->unsigned()->index();
            $table->string('title')->nullable();
            $table->string('picture')->nullable();
            $table->boolean('is_published');
            $table->integer('sort_order');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::drop('numencode_blogextension_pictures');
    }
}
