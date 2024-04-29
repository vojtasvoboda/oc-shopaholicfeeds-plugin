<?php namespace VojtaSvoboda\ShopaholicFeeds\Builders;

use Cms\Classes\Page;
use Cms\Classes\Theme;
use DOMDocument;
use DOMElement;
use Lovata\Shopaholic\Classes\Item\ProductItem;
use Lovata\Shopaholic\Models\Offer;
use Lovata\Shopaholic\Models\Product;
use October\Rain\Router\Router;
use October\Rain\Support\Collection;
use System\Classes\PluginManager;

/**
 * Builder for Google Merchant RSS 2.0 format.
 * - https://support.google.com/merchants/answer/7052112
 * - https://support.google.com/merchants/answer/160589
 *
 * @package VojtaSvoboda\ShopaholicFeeds\Builders
 */
class GoogleMerchantRss2 extends BaseBuilder
{
    /**
     * @param string $format
     * @return array
     */
    public function getHeaders($format): array
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
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private function getProductsToExport(): \Illuminate\Database\Eloquent\Collection
    {
        return Product::active()->with(['brand', 'offer'])->get();
    }

    /**
     * @return DOMDocument
     */
    private function createXmlDocument(): DOMDocument
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
    private function createRssElement(DOMDocument $xml): DOMElement
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
    private function createChannelElement(DOMDocument $xml): DOMElement
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
    private function createItemsElements(DOMDocument $xml): Collection
    {
        // init
        $elements = collect();

        // feed settings
        $locale = $this->feed->locale;
        $translatable = $locale !== null && PluginManager::instance()->hasPlugin('RainLab.Translate');
        $currencyCode = $this->feed->currency !== null ? $this->feed->currency->code : null;
        $product_page = substr($this->feed->product_page, 0, -4);

        // product link translation prepare
        $router = null;
        $cmsPageUrl = null;
        if ($translatable === true) {
            $router = new Router();
            $cmsPage = Page::loadCached(Theme::getActiveTheme(), $product_page);
            $cmsPage->rewriteTranslatablePageUrl($locale->code);
            $cmsPageUrl = $cmsPage->url;
        }

        // prepare weight unit
        $weightUnit = $this->getWeightMeasureCode();

        /** @var Product $product Create element for each product. */
        foreach ($this->getProductsToExport() as $product) {
            /** @var Offer $offer */
            $offer = $product->offer->first();
            if ($offer === null) {
                continue;
            }

            // get product link
            $productItem = ProductItem::make($product->id);
            $productParams = $productItem->getPageParamList($product_page);
            $link = Page::url($product_page, $productParams);

            // set locale
            $brand = $product->brand;
            $category = $product->category;
            if ($translatable === true) {
                $product->translateContext($locale->code);
                if ($brand !== null) {
                    $brand->translateContext($locale->code);
                }
                if ($category !== null) {
                    $category->translateContext($locale->code);
                }
                $link = url($router->urlFromPattern($cmsPageUrl, $productParams));
            }

            // set currency
            if ($currencyCode !== null) {
                $offer->setActiveCurrency($currencyCode);
            }

            // availability
            $availability = $offer->quantity > 0 ? 'in stock' : 'out of stock';

            // create item element
            $item = $xml->createElement('item');
            $item->appendChild($xml->createElement('id', $product->code));
            $item->appendChild($xml->createElement('title', htmlspecialchars($product->name)));
            $item->appendChild($xml->createElement('g:description', $product->preview_text));
            $item->appendChild($xml->createElement('link', $link));
            if ($product->preview_image) {
                $item->appendChild($xml->createElement('g:image_link', $product->preview_image->path));
            }
            if ($product->images) {
                foreach ($product->images as $image) {
                    $item->appendChild($xml->createElement('g:additional_image_link', $image->path));
                }
            }
            if ($brand !== null) {
                $item->appendChild($xml->createElement('g:brand', $brand->name));
            }
            $item->appendChild($xml->createElement('availability', $availability));
            $item->appendChild($xml->createElement('gtin', $product->external_id));
            $item->appendChild($xml->createElement('condition', 'new'));
            $item->appendChild($xml->createElement('g:price', $offer->price_value));
            if (!empty($currencyCode)) {
                $item->appendChild($xml->createElement('currency', $currencyCode));
            }
            if ($category !== null) {
                $item->appendChild($xml->createElement('g:google_product_category', htmlspecialchars(($category->name)));
            }
            if ($offer->weight !== null) {
                $item->appendChild($xml->createElement('product_weight', trim($offer->weight . ' ' . $weightUnit)));
            }

            // add to the collection
            $elements->push($item);
        }

        return $elements;
    }
}
