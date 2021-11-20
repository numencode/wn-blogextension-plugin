<?php namespace NumenCode\BlogExtension\Updates;

use Schema;
use Winter\Storm\Database\Updates\Migration;

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
    }

    public function down()
    {
        Schema::drop('numencode_blogextension_tags');
    }
}
