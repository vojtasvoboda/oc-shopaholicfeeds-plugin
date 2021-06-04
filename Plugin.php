<?php namespace VojtaSvoboda\ShopaholicFeeds;

use Backend;
use Backend\Widgets\Form;
use Backend\Widgets\Lists;
use Event;
use File;
use System\Classes\PluginBase;
use System\Classes\PluginManager;
use VojtaSvoboda\ShopaholicFeeds\Controllers\Feeds;
use VojtaSvoboda\ShopaholicFeeds\Models\Feed;
use Yaml;

/**
 * ShopaholicFeeds Plugin Information File.
 */
class Plugin extends PluginBase
{
    /** @var string[] $require Required plugins. */
    public $require = [
        'Lovata.OrdersShopaholic',
    ];

    /**
     * Boot method, called right before the request route.
     */
    public function boot()
    {
        // check if RainLab.Translate installed
        $translatable = PluginManager::instance()->hasPlugin('RainLab.Translate');
        if ($translatable === false) {
            // remove locale column from the list when RainLab.Translate not installed
            Event::listen('backend.list.extendColumns', function (Lists $widget) {
                // only for Feeds controller
                if (!$widget->getController() instanceof Feeds) {
                    return;
                }

                // only for Feed model
                if (!$widget->model instanceof Feed) {
                    return;
                }

                $widget->removeColumn('locale');
            });

            return;
        }

        // extend VojtaSvoboda.ShopaholicFeeds Feed model
        Feed::extend(function ($model) {
            $model->belongsTo['locale'] = ['RainLab\Translate\Models\Locale'];
        });

        // extend VojtaSvoboda.ShopaholicFeeds Feed form
        Event::listen('backend.form.extendFieldsBefore', function (Form $form) {
            // apply only to Feed model
            if (!$form->model instanceof Feed) {
                return;
            }

            // only when fields are defined
            if (empty($form->tabs['fields'])) {
                return;
            }

            // add new field into the existing form
            $configFile = __DIR__ . '/config/feed_with_locale_fields.yaml';
            $config = Yaml::parse(File::get($configFile));
            $firstPart = array_merge(array_slice($form->tabs['fields'], 0, 1), $config);
            $form->tabs['fields'] = array_merge($firstPart, array_slice($form->tabs['fields'], 1));
        });
    }

    /**
     * @return array
     */
    public function registerSettings()
    {
        return [
            'vojtasvoboda-shopaholic-feeds-settings' => [
                'label' => 'XML feeds',
                'description' => 'Manage XML feeds',
                'category' => 'lovata.shopaholic::lang.tab.settings',
                'icon' => 'icon-file-code-o',
                'url' => Backend::url('vojtasvoboda/shopaholicfeeds/feeds'),
                'order' => 7900,
                'permissions' => [
                    'vojtasvoboda.shopaholic_feeds.settings',
                ],
            ],
        ];
    }

    /**
     * Registers any back-end permissions used by this plugin.
     *
     * @return array
     */
    public function registerPermissions()
    {
        return [
            'vojtasvoboda.shopaholic_feeds.settings' => [
                'tab' => 'Feeds for Shopaholic',
                'label' => 'Manage feeds',
            ],
        ];
    }
}
