<?php namespace NumenCode\BlogExtension\models;

use Model;

class Settings extends Model
{
    public $implement = ['System.Behaviors.SettingsModel'];

    public $settingsCode = 'numencode_blogextension_settings';

    public $settingsFields = 'fields.yaml';

    protected $cache = [];
}
