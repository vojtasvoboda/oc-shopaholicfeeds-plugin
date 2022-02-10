<?php namespace VojtaSvoboda\ShopaholicFeeds\Classes;

use Lovata\Shopaholic\Models\Offer;
use Lovata\Shopaholic\Classes\Collection\OfferCollection;

/**
 * Class ExtendOfferCollection
 * @package Lovata\BaseCode\Classes\Event\Offer
 */
class ExtendOfferCollection
{
    public function subscribe()
    {
        OfferCollection::extend(function ($obOfferList) {
            $this->addCustomOfferMethod($obOfferList);
        });
    }

    /**
     * Add getOfferByCode method
     * @param OfferCollection $obOfferList
     */
    protected function addCustomOfferMethod($obOfferList)
    {
        $obOfferList->addDynamicMethod('getOfferByCode', function ($codes) use ($obOfferList) {

            $arResultIDList = (array) Offer::whereIn('code', $codes)->lists('id');

            return $obOfferList->intersect($arResultIDList);
        });
    }
}