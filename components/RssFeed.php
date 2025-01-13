<?php namespace NumenCode\BlogExtension\Components;

use Carbon\Carbon;
use Illuminate\Http\Response as HttpResponse;
use Winter\Blog\Components\RssFeed as WinterRssFeed;
use NumenCode\BlogExtension\Models\Settings as BlogSettings;

class RssFeed extends WinterRssFeed
{
    public $channel;

    public function componentDetails(): array
    {
        return [
            'name'        => 'numencode.blogextension::lang.rss_feed.name',
            'description' => 'numencode.blogextension::lang.rss_feed.description',
        ];
    }

    public function onRun(): HttpResponse
    {
        if (plugin_exists('Winter.Translate')) {
            $translator = \Winter\Translate\Classes\Translator::instance();
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

        return HttpResponse::make($xmlFeed, '200')->header('Content-Type', 'text/xml');
    }
}
