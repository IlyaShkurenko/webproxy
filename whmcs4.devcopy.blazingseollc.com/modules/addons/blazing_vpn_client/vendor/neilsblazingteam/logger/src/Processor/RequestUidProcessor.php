<?php

namespace Blazing\Vpn\Client\Vendor\Blazing\Logger\Processor;

class RequestUidProcessor
{
    protected $uid;
    public function __construct($sharedParameter = '$requestId', $length = 7)
    {
        if (!empty($_REQUEST[$sharedParameter])) {
            $this->uid = $_REQUEST[$sharedParameter];
        } else {
            $this->uid = $this->generateUid($length);
        }
    }
    public function __invoke(array $record)
    {
        $record['extra']['uid'] = $this->uid;
        return $record;
    }
    /**
     * @return string
     */
    public function getUid()
    {
        return $this->uid;
    }
    public function setUid($uid)
    {
        $this->uid = $uid;
    }
    protected function generateUid($length)
    {
        if (!is_int($length) || $length > 32 || $length < 1) {
            throw new \InvalidArgumentException('The uid length must be an integer between 1 and 32');
        }
        return substr(hash('md5', uniqid('', true)), 0, $length);
    }
}