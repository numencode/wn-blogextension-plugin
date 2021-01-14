<?php namespace NumenCode\BlogExtension\models;

use File;
use Yaml;
use Model;

class Settings extends Model
{
    public $implement = ['System.Behaviors.SettingsModel'];

    public $settingsCode = 'numencode_blogextension_settings';

    public $settingsFields = 'fields.yaml';

    protected $cache = [];

    public function getRssFeedLanguageOptions()
    {
        $languagesFile = plugins_path('NumenCode\BlogExtension\config\languages.yaml');

        if (!File::exists($languagesFile)) {
            return [];
        }

        $languages = Yaml::parseFile($languagesFile);

        return is_array($languages) ? array_flip($languages) : [];
    }
}
