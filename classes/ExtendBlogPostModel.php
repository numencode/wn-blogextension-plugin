<?php namespace NumenCode\BlogExtension\Classes;

use Winter\Blog\Models\Post;
use NumenCode\BlogExtension\Models\Tag;
use NumenCode\BlogExtension\Models\File;
use NumenCode\BlogExtension\Models\Picture;

class ExtendBlogPostModel
{
    public function init()
    {
        Post::extend(function ($post) {
            $post->implement[] = 'Winter.Storm.Database.Behaviors.Purgeable';
            $post->implement[] = 'NumenCode.Fundamentals.Behaviors.RelationableModel';

            $post->belongsToMany['tags'] = [Tag::class, 'table' => 'numencode_blogextension_posts_tags'];

            $post->hasMany = [
                'pictures' => [Picture::class, 'table' => 'numencode_blogextension_pictures'],
                'files'    => [File::class, 'table' => 'numencode_blogextension_files'],
            ];

            $post->addDynamicProperty('relationable', [
                'pictures_list' => 'pictures',
                'files_list'    => 'files',
            ]);

            $post->addDynamicMethod('primaryPicture', function () use ($post) {
                if (!$post->pictures_list) {
                    return null;
                }

                return $post->pictures_list[0];
            });
        });
    }
}
