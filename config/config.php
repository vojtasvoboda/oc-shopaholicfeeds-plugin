<?php

return [

    /**
     * List of available builders.
     *
     * To add new builder, copy this file to /config/vojtasvoboda/shopaholicfeeds/config.php and add new builder below.
     */
    'builders' => [
        'google-merchant-rss20' => [
            'name' => 'Google Merchant RSS 2.0 for Products',
            'class' => 'VojtaSvoboda\ShopaholicFeeds\Builders\GoogleMerchantRss2',
        ],
        'google-merchant-offers-rss20' => [
            'name' => 'Google Merchant RSS 2.0 for Offers',
            'class' => 'VojtaSvoboda\ShopaholicFeeds\Builders\GoogleMerchantOffersRss2',
        ],
    ],
];
