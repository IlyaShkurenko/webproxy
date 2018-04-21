<?php

namespace Proxy;

use Proxy\FeedBox;

class FeedBoxFactory
{

    /**
     * Construct and return Bridge Box
     *
     * @return FeedBox\AbstractFeedBox|FeedBox\AbstractFeedBoxPartials
     */
    public static function build()
    {
        return new FeedBox\FileFeedBox();
    }
}
