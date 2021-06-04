<?php namespace VojtaSvoboda\ShopaholicFeeds\Controllers;

use BackendMenu;
use Backend\Classes\Controller;
use System\Classes\SettingsManager;
use VojtaSvoboda\ShopaholicFeeds\Models\Feed;

/**
 * Feeds Back-end Controller
 */
class Feeds extends Controller
{
    /**
     * @var array Behaviors that are implemented by this controller.
     */
    public $implement = [
        'Backend.Behaviors.FormController',
        'Backend.Behaviors.ListController',
        'Backend.Behaviors.RelationController',
    ];

    /**
     * @var string Configuration file for the `FormController` behavior.
     */
    public $formConfig = 'config_form.yaml';

    /**
     * @var string Configuration file for the `ListController` behavior.
     */
    public $listConfig = 'config_list.yaml';

    /**
     * @var string Configuration file for the `RelationController` behavior.
     */
    public $relationConfig = 'config_relation.yaml';

    public function __construct()
    {
        parent::__construct();

        BackendMenu::setContext('October.System', 'system', 'settings');
        SettingsManager::setContext('VojtaSvoboda.ShopaholicFeeds', 'vojtasvoboda-shopaholic-feeds-settings');
    }

    /**
     * Override displaying columns.
     *
     * @param  Feed $record
     * @param  string $columnName
     * @param  string|null $definition
     * @return string
     */
    public function listOverrideColumnValue($record, $columnName, $definition = null)
    {
        if ($columnName === 'created_at') {
            return $record->author->full_name . '<br>' . $record->created_at->format('d.m.Y H:i:s');
        }

        if ($columnName === 'updated_at') {
            return $record->editor->full_name . '<br>' . $record->updated_at->format('d.m.Y H:i:s');
        }
    }
}
