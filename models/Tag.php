<?php namespace NumenCode\BlogExtension\Models;

use Model;
use Winter\Blog\Models\Post;
use Winter\Storm\Database\Traits\Sluggable;

class Tag extends Model
{
    use Sluggable;

    public $table = 'numencode_blogextension_tags';

    protected $guarded = ['*'];

    protected $fillable = ['name'];

    protected $slugs = ['slug' => 'name'];

    public $belongsToMany = [
        'posts' => [Post::class, 'table' => 'numencode_blogextension_posts_tags'],
    ];

    /**
     * Display a list of tags.
     *
     * @param $query
     * @param string $sortOrder
     *
     * @return mixed
     */
    public function scopeListTags($query, $sortOrder)
    {
        $sortOrder = explode(' ', $sortOrder);
        $sortedBy = $sortOrder[0];
        $direction = $sortOrder[1];

        return $query->orderBy($sortedBy, $direction);
    }

    /**
     * Sets the "url" attribute with a URL to this object.
     *
     * @param string $pageName
     * @param Cms\Classes\Controller $controller
     *
     * @return string
     */
    public function setUrl($pageName, $controller): string
    {
        $params = [
            'id'   => $this->id,
            'slug' => $this->slug,
        ];

        return $this->url = $controller->pageUrl($pageName, $params, false);
    }
}
