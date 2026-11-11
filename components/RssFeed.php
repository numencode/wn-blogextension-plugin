<?php

declare(strict_types=1);

namespace NumenCode\BlogExtension\Components;

use Carbon\Carbon;
use Illuminate\Http\Response as HttpResponse;
use NumenCode\BlogExtension\Models\Settings;
use System\Classes\PluginManager;
use Winter\Blog\Components\RssFeed as BlogRssFeed;

/**
 * Winter.Blog's RSS feed with the channel details from the blog extension settings.
 */
class RssFeed extends BlogRssFeed
{
    /**
     * @var array<string, mixed>
     */
    public array $channel = [];

    public function componentDetails(): array
    {
        return [
            'name'        => 'numencode.blogextension::lang.rss_feed.name',
            'description' => 'numencode.blogextension::lang.rss_feed.description',
        ];
    }

    public function onRun(): HttpResponse
    {
        $this->channel = $this->page['channel'] = static::channel();

        return parent::onRun();
    }

    /**
     * The channel details: settings, the build date and the language (the active language with Winter.Translate).
     *
     * @return array<string, mixed>
     */
    public static function channel(): array
    {
        $language = PluginManager::instance()->exists('Winter.Translate')
            ? app()->getLocale()
            : (Settings::get('rss_feed_language') ?: app()->getLocale());

        return [
            'title'           => Settings::get('rss_feed_title'),
            'description'     => Settings::get('rss_feed_description'),
            'category'        => Settings::get('rss_feed_category'),
            'copyright'       => Settings::get('rss_feed_copyright'),
            'generator'       => Settings::get('rss_feed_generator'),
            'comments'        => Settings::get('rss_feed_comments'),
            'author'          => Settings::get('rss_feed_author'),
            'content'         => (bool) Settings::get('rss_feed_content'),
            'language'        => $language,
            'last_build_date' => Carbon::now()->toRfc2822String(),
        ];
    }
}
