<?php

use VojtaSvoboda\ShopaholicFeeds\Services\FeedService;

Route::get('/feeds/{name}.{format}', function ($name, $format) {
    /** @var FeedService $service */
    $service = app(FeedService::class);
    $feed = $service->getFeed($name, $format);
    if ($feed === null) {
        header("HTTP/1.0 404 Not Found");
        exit;
    }

    // write headers
    $headers = $feed['headers'];
    foreach ($headers as $header) {
        header($header);
    }

    // write output
    echo $feed['output'];
    exit;
});
