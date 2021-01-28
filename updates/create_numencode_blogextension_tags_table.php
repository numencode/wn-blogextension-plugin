<?php namespace NumenCode\BlogExtension\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class CreateNumencodeBlogextensionTagsTable extends Migration
{
    public function up()
    {
        Schema::create('numencode_blogextension_tags', function ($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('name', 25)->nullable();
            $table->string('slug', 27)->nullable()->index();
            $table->timestamps();
        });
        
        Schema::create('numencode_blogextension_posts_tags', function ($table)
        {
            $table->engine = 'InnoDB';
            $table->integer('tag_id')->unsigned();
            $table->integer('post_id')->unsigned();
            $table->index(['tag_id', 'post_id']);
            $table->foreign('tag_id')->references('id')->on('numencode_blogextension_tags')->onDelete('cascade');
            $table->foreign('post_id')->references('id')->on('rainlab_blog_posts')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::drop('numencode_blogextension_tags');
        Schema::drop('numencode_blogextension_posts_tags');
    }
}