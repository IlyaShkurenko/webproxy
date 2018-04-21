<?php

namespace Blazing\Vpn\Client\Vendor\ApiRequestHandler;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\ConfigBuilder\AbstractConfigBuilder;
use ErrorException;

abstract class AbstractApi
{
    /**
     * @var AbstractConfigBuilder
     */
    protected $configuration;

    /**
     * @var GenericApiContext
     */
    protected $context;

    /**
     * @var RequestHandler
     */
    protected $requestHandler;

    // Classes map
    protected $map = [];

    protected $loaded = [];

    public function __construct(ApiConfiguration $configuration)
    {
        $this->configuration = $configuration;
        $this->context = new GenericApiContext();
    }

    protected function buildApi($type)
    {
        if (!empty($this->loaded[$type])) {
            return $this->loaded[$type];
        }

        if (empty($this->map[$type])) {
            throw new ErrorException("Api \"$type\" is not defined");
        }

        $class = $this->map[$type];

        return $this->loaded[$type] = new $class($this);
    }

    // Request handler

    /**
     * @return RequestHandler
     */
    public function request()
    {
        if (!$this->requestHandler) {
            $this->requestHandler = new RequestHandler($this->configuration);
        }

        return $this->requestHandler;
    }

    public function setRequestHandler(RequestHandler $requestHandler)
    {
        $this->requestHandler = $requestHandler;

        return $this;
    }

    public function getConfiguration()
    {
        return $this->configuration;
    }

    protected function setContext(GenericApiContext $context)
    {
        $this->context = $context;
    }

    protected function clearContext()
    {
        $this->context = null;
    }

    public function getContext()
    {
        return $this->context;
    }
}
