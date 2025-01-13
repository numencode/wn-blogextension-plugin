<?php namespace NumenCode\BlogExtension\Updates;

use Schema;
use Winter\Storm\Database\Updates\Migration;

class CreateNumencodeBlogextensionFilesTable extends Migration
{
    public function up()
    {
        Schema::create('numencode_blogextension_files', function ($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->integer('post_id')->unsigned()->index();
            $table->string('title')->nullable();
            $table->string('file')->nullable();
            $table->boolean('is_published');
            $table->integer('sort_order')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::drop('numencode_blogextension_files');
    }
}
