<?php

namespace Proxy\FeedBox;

use Redis;

class RedisFeedBox extends AbstractFeedBox
{

    const NS = 'proxy:';

    /** @var  Redis */
    protected $redis;

    protected function connectRedis()
    {
        if (!$this->redis) {
            $this->redis = new Redis();
            $this->redis->connect('127.0.0.1');
        }
    }

    protected function doPush($key, $data)
    {
        $this->connectRedis();
        $this->redis->set(self::NS . $key, $data);
    }

    protected function doPull($key)
    {
        $this->connectRedis();

        return $this->redis->get(self::NS . $key);
    }
}
