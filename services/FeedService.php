<?php namespace VojtaSvoboda\ShopaholicFeeds\Services;

use Config;
use Exception;
use Request;
use VojtaSvoboda\ShopaholicFeeds\Builders\BaseBuilder;
use VojtaSvoboda\ShopaholicFeeds\Models\Feed;
use VojtaSvoboda\ShopaholicFeeds\Models\Log;

class FeedService
{
    /** @var Feed $feeds */
    private $feeds;

    /**
     * @param Feed $feeds
     */
    public function __construct(Feed $feeds)
    {
        $this->feeds = $feeds;
    }

    /**
     * Get feed headers and output depends on slug and format.
     *
     * @param string $slug
     * @param string $format
     * @return array|null
     * @throws Exception
     */
    public function getFeed($slug, $format)
    {
        /** @var Feed $feed Try to find the feed. */
        $feed = $this->feeds->isEnabled()->searchBySlug($slug)->first();
        if ($feed == null) {
            return null;
        }

        // check if access is allowed
        $allowed = $feed->isAllowed(Request::ip());

        // create log if logging enabled
        $log = $this->createLog($feed, $allowed);

        // if access is not allowed, returns forbidden exception
        if ($allowed !== true) {
            return $this->getNotAllowedOutput();
        }

        // create feed builder
        $builder = $this->createBuilder($feed);

        // get feed data
        $headers = $builder->getHeaders($format);
        $output = $builder->getOutput($format);

        // mark export time
        if ($log !== null) {
            $log->logTime();
        }

        return compact('headers', 'output');
    }

    /**
     * @return array
     */
    private function getNotAllowedOutput()
    {
        $ip_addr = Request::ip();

        return [
            'headers' => [
                'HTTP/1.0 403 Forbidden',
            ],
            'output' => "We're sorry, but your IP address $ip_addr is not permitted to see this feed.",
        ];
    }

    /**
     * @param Feed $feed
     * @param bool $allowed
     * @return Log|null
     */
    private function createLog($feed, $allowed)
    {
        // if logging is disabled, returns null
        if (empty($feed->log_enabled)) {
            return null;
        }

        return $feed->logs()->create([
            'allowed' => $allowed,
        ]);
    }

    /**
     * @param Feed $feed
     * @return BaseBuilder
     * @throws Exception
     */
    private function createBuilder($feed)
    {
        $class = $this->getBuilderClass($feed->format);
        $builder = app($class);
        $builder->setFeed($feed);

        return $builder;
    }

    /**
     * @return string
     * @throws Exception
     */
    private function getBuilderClass($feedFormat)
    {
        $builders = Config::get('vojtasvoboda.shopaholicfeeds::config.builders', []);

        if (!isset($builders[$feedFormat])) {
            throw new Exception("Feed format $feedFormat is not defined.");
        }

        return $builders[$feedFormat]['class'];
    }
}
