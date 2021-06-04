<?php namespace VojtaSvoboda\ShopaholicFeeds\Builders;

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
}
