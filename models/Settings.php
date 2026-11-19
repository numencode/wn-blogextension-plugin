<?php

declare(strict_types=1);

namespace NumenCode\BlogExtension\Models;

use BackendAuth;
use Model;
use Winter\Storm\Support\Facades\Yaml;

/**
 * Blog extension settings: feature switches, the excerpt editor and the RSS feed channel.
 */
class Settings extends Model
{
    /**
     * Features of the post form: feature => permission. Each one can be switched off in the settings
     * ("extension_{feature}", on by default) and is only shown to backend users with its permission.
     */
    public const FEATURES = [
        'tags'     => 'numencode.blogextension.access_tags',
        'pictures' => 'numencode.blogextension.access_pictures',
        'files'    => 'numencode.blogextension.access_files',
        'bricks'   => 'numencode.blogextension.access_bricks',
    ];

    public $implement = ['System.Behaviors.SettingsModel'];

    public $settingsCode = 'numencode_blogextension_settings';

    public $settingsFields = 'fields.yaml';

    /**
     * Whether a feature (tags, pictures, files or bricks) is switched on. Features are on unless switched off;
     * bricks also need the NumenCode.Bricks plugin.
     */
    public static function isFeatureEnabled(string $feature): bool
    {
        return array_key_exists($feature, static::FEATURES)
            && ($feature !== 'bricks' || static::bricksInstalled())
            && (bool) static::get('extension_' . $feature, true);
    }

    /**
     * Whether NumenCode.Bricks is installed and enabled (bricks on blog posts).
     */
    public static function bricksInstalled(): bool
    {
        return \System\Classes\PluginManager::instance()->exists('NumenCode.Bricks');
    }

    /**
     * Whether a feature is switched on and the logged-in backend user may use it.
     */
    public static function canUseFeature(string $feature): bool
    {
        $user = BackendAuth::getUser();

        return static::isFeatureEnabled($feature) && $user && $user->hasAccess(static::FEATURES[$feature]);
    }

    /**
     * Options of the RSS feed language dropdown: language code => name.
     *
     * @return array<string, string>
     */
    public function getRssFeedLanguageOptions(): array
    {
        $languages = Yaml::parseFile(plugins_path('numencode/blogextension/config/languages.yaml'));

        return is_array($languages) ? array_flip($languages) : [];
    }
}
