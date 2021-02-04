<?php namespace NumenCode\BlogExtension\Models;

use File;
use Yaml;
use Model;
use Storage;

class Settings extends Model
{
    public $implement = ['System.Behaviors.SettingsModel'];

    public $settingsCode = 'numencode_blogextension_settings';

    public $settingsFields = 'fields.yaml';

    protected $cache = [];

    public function getRssFeedLanguageOptions(): array
    {
        $languagesFile = plugins_path('NumenCode\BlogExtension\config\languages.yaml');

        if (!File::exists($languagesFile)) {
            return [];
        }

        $languages = Yaml::parseFile($languagesFile);

        return is_array($languages) ? array_flip($languages) : [];
    }

    public function getFakerMediaOptions(): array
    {
        $directories = array_filter(Storage::allDirectories(), function ($directory) {
            return basename($directory) != !stristr($directory, 'media/') && !stristr($directory, '/thumb');
        });

        $directories = array_merge(['media/'], $directories);

        return array_combine($directories, $directories);
    }
}
