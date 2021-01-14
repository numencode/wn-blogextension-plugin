<?php namespace NumenCode\BlogExtension\Components;

use Response;
use Carbon\Carbon;
use RainLab\Blog\Components\RssFeed as RainLabRssFeed;
use NumenCode\BlogExtension\Models\Settings as BlogSettings;

class RssFeed extends RainLabRssFeed
{
    public $channel;

    public function componentDetails(): array
    {
        return [
            'name'        => 'numencode.blogextension::lang.rss_feed.name',
            'description' => 'numencode.blogextension::lang.rss_feed.description',
        ];
    }

    public function onRun()
    {
        if (plugin_exists('RainLab.Translate')) {
            $translator = \RainLab\Translate\Classes\Translator::instance();
            $language = $translator->getLocale();
        }

        $this->channel = [
            'title'           => BlogSettings::get('rss_feed_title'),
            'description'     => BlogSettings::get('rss_feed_description'),
            'category'        => BlogSettings::get('rss_feed_category'),
            'copyright'       => BlogSettings::get('rss_feed_copyright'),
            'last_build_date' => Carbon::now()->toRfc2822String(),
            'language'        => $language ?? (BlogSettings::get('rss_feed_language') ?: 'en'),
            'generator'       => BlogSettings::get('rss_feed_generator'),
            'comments'        => BlogSettings::get('rss_feed_comments'),
            'content'         => BlogSettings::get('rss_feed_content'),
            'author'          => BlogSettings::get('rss_feed_author'),
        ];

        $this->prepareVars();

        $xmlFeed = $this->renderPartial('@default');

        return Response::make($xmlFeed, '200')->header('Content-Type', 'text/xml');
    }
}
