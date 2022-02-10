<?php namespace VojtaSvoboda\ShopaholicFeeds\Builders;

use Cms\Classes\Page;
use Cms\Classes\Theme;
use DOMDocument;
use DOMElement;
use Lovata\Shopaholic\Models\Offer;
use Lovata\Shopaholic\Models\Product;
use October\Rain\Support\Collection;
use System\Classes\PluginManager;
use Lovata\Shopaholic\Classes\Collection\OfferCollection;

/**
 * Builder for Google Merchant RSS 2.0 format.
 * - https://support.google.com/merchants/answer/7052112
 * - https://support.google.com/merchants/answer/160589
 *
 * @package VojtaSvoboda\ShopaholicFeeds\Builders
 */
class AlzaMarketplaceCustomFeed extends BaseBuilder
{
    /**
     * @param string $format
     * @return array
     */
    public function getHeaders($format)
    {
        if ($format === 'xml') {
            return [
                'Content-Type: application/xml; charset=utf-8',
            ];
        }

        return [
            'HTTP/1.0 400 Bad Request',
        ];
    }

    /**
     * @param string $format
     * @return string|null
     */
    public function getOutput($format)
    {
        // XML format
        if ($format === 'xml') {
            return $this->createXmlDocument()->saveXML();
        }

        return "Format " . strtoupper($format) . " is not supported yet.";
    }

    /**
     * @param array $codes
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getOffersToExport(array $codes)
    {
        return OfferCollection::make()->getOfferByCode($codes);
    }

    /**
     * @return DOMDocument
     */
    private function createXmlDocument()
    {
        // create new XML document
        $xml = new DOMDocument();
        $xml->xmlVersion = '1.0';
        $xml->encoding = 'UTF-8';

        // append RSS element
        $rss = $this->createRssElement($xml);
        $xml->appendChild($rss);

        return $xml;
    }

    /**
     * @param DOMDocument $xml
     * @return DOMElement
     */
    private function createRssElement($xml)
    {
        // create RSS element
        $rss = $xml->createElement('rss');
        $rss->setAttribute('version', '2.0');
        $rss->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:g', 'http://base.google.com/ns/1.0');
        // append channel element
        $channel = $this->createChannelElement($xml);
        $rss->appendChild($channel);

        return $rss;
    }

    /**
     * @param DOMDocument $xml
     * @return DOMElement
     */
    private function createChannelElement($xml)
    {
        // create channel element
        $channel = $xml->createElement('channel');
        $channel->appendChild($xml->createElement('title', $this->feed->name));
        $channel->appendChild($xml->createElement('description', $this->feed->name));

        // append item elements
        $items = $this->createItemsElements($xml);
        $items->each(function ($item) use ($channel) {
            $channel->appendChild($item);
        });

        return $channel;
    }

    /**
     * Create product elements.
     * - feed locale is optional, null locale = default locale / no translation
     * - feed currency is optional, null currency = no currency
     *
     * @param DOMDocument $xml
     * @return Collection
     */
    private function createItemsElements($xml)
    {
        // init
        $elements = collect();

        // feed settings
        $locale = $this->feed->locale;
        $translatable = $locale !== null && PluginManager::instance()->hasPlugin('RainLab.Translate');
        $product_page = substr($this->feed->product_page, 0, -4);
        $vat = 1.21;
        $weight = 0;
        $height = 0;
        $length = 0;
        $width = 0;
        $weightUnit = 'g';
        $fee = 0.0;

        // product link translation prepare
        if ($translatable === true) {
            $cmsPage = Page::loadCached(Theme::getActiveTheme(), $product_page);
            $cmsPage->rewriteTranslatablePageUrl($locale->code);
        }

        /** @var Product $product Create element for each product. */
        foreach ($this->getOffersToExport(['ALZA']) as $offer) {
            /** @var Offer $offer */
            $product = $offer->product;

            if ($offer === null || $offer->count() === 0) {
                continue;
            }

            if ($offer->name !== '') {
                $name = $offer->name;
            }
            else {
                $name = $product->name;
            }

            // set product weight
            if ($offer->weight !== null) {
                $weight = $offer->weight;
            }

            // set product height
            if ($offer->height !== null) {
                $height = $offer->height;
            }

            // set product length
            if ($offer->length !== null) {
                $length = $offer->length;
            }

            // set product width
            if ($offer->width !== null) {
                $width = $offer->width;
            }

            // create item element
            $item = $xml->createElement('item');
            $item->appendChild($xml->createElement('name', $name));
            $item->appendChild($xml->createElement('ean', $product->external_id));
            $item->appendChild($xml->createElement('quantity', $offer->quantity));
            $item->appendChild($xml->createElement('price', $offer->price_value));
            $item->appendChild($xml->createElement('priceWithFee', $offer->price_value+$fee));
            $item->appendChild($xml->createElement('fee', $fee));
            $item->appendChild($xml->createElement('vat', $vat));
            $item->appendChild($xml->createElement('size1', $height));
            $item->appendChild($xml->createElement('size2', $length));
            $item->appendChild($xml->createElement('size3', $width));
            $item->appendChild($xml->createElement('weight', $weight.$weightUnit));
            $item->appendChild($xml->createElement('code', $product->code));

            // add to the collection
            $elements->push($item);
        }

        return $elements;
    }
}
