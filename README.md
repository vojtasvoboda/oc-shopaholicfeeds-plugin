# Feeds for Shopaholic plugin for OctoberCMS

This plugin allows you to define unlimited number of feeds (XML feeds, JSON feeds). For example you can create Google
Merchant XML feed for integrating Google Merchant and Google Ads.

Tested and developed with OctoberCMS v1.1.4 (Laravel 6.0).

Key features:

- define **unlimited** number of feeds
- every feed has own **format**, **locale** and **currency** for prices
- feeds could be available with **public/private URL**
- you can define **whitelist** for allowed IP addresses
- feeds have **access log**

Note: locale definition is available only with **RainLab.Translate** plugin.

## Requirements

Require Lovata.OrdersShopaholic plugin.

## Available formats:

- Google Merchant RSS 2.0

## Settings

- feed locale is optional: when not selected, default locale will be used
- currency is optional: when not selected, currency attribute will be hidden and prices will be in default currency

## Add your own format:

For adding new format, copy file `/plugins/vojtasvoboda/shopaholicfeeds/config/config.php` to
`/config/vojtasvoboda/shopaholicfeeds/config.php` and add new builder.

## Contributing

Please send Pull Request to the master branch.

Icon made by Vectors Market from www.flaticon.com.
