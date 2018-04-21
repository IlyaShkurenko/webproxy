<?php

namespace Blazing\Vpn\Client\Vendor\Blazing\Logger\Processor;

use Blazing\Vpn\Client\Vendor\Blazing\Logger\Logger;
class MasterRequestUidProcessor
{
    const REQUEST_PARAMTETER = '_log_mru';
    protected $requestParameter;
    protected $uid;
    /**
     * @var Logger
     */
    private $logger;
    public function __construct(Logger $logger, $requestParameter = self::REQUEST_PARAMTETER)
    {
        $this->requestParameter = $requestParameter;
        $this->logger = $logger;
    }
    public function __invoke(array $record)
    {
        $record['extra']['muid'] = $this->getUid();
        return $record;
    }
    /**
     * @return string
     */
    public function getUid()
    {
        if (!$this->uid) {
            if (!empty($_REQUEST[$this->requestParameter])) {
                $this->uid = $_REQUEST[$this->requestParameter];
            } else {
                $this->uid = $this->logger->getRequestUid();
            }
        }
        return $this->uid;
    }
    public function setUid($uid)
    {
        $this->uid = $uid;
    }
    public function prepareRequestParameter()
    {
        return [$this->requestParameter => $this->getUid()];
    }
}