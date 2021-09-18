<?php namespace VojtaSvoboda\ShopaholicFeeds\Builders;

use Lovata\Shopaholic\Models\Measure;
use Lovata\Shopaholic\Models\Settings;
use VojtaSvoboda\ShopaholicFeeds\Models\Feed;

abstract class BaseBuilder
{
    /** @var Feed $feed */
    protected $feed;

    /**
     * @param Feed $feed
     * @return self
     */
    public function setFeed($feed)
    {
        $this->feed = $feed;

        return $this;
    }

    /**
     * @param string $format
     * @return array
     */
    abstract public function getHeaders($format);

    /**
     * @param string $format
     * @return string
     */
    abstract public function getOutput($format);

	/**
	 * Get weight measure code
	 * @return string|null
	 */
	public function getWeightMeasureCode()
	{
		$iMeasureID = Settings::getValue('weight_measure');
		if (empty($iMeasureID)) {
			return null;
		}

		$obMeasure = Measure::find($iMeasureID);
		if (empty($obMeasure)) {
			return null;
		}

		return $obMeasure->code;
	}
}
