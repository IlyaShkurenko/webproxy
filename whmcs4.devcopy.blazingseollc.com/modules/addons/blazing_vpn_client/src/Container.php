<?php

namespace Blazing\Vpn\Client;

use Blazing\Vpn\Client\Vendor\Blazing\Logger\Logger;
use Blazing\Vpn\Client\Vendor\Pimple\Container as BaseContainer;
use ErrorException;

final class Container extends BaseContainer
{

    protected static $instance;

    /**
     * @return static
     * @throws ErrorException
     */
    public static function getInstance()
    {
        if (!self::$instance) {
            throw new ErrorException('Instance is not registered');
        }

        return self::$instance;
    }

    public static function registerInstance()
    {
        if (self::$instance) {
            throw new ErrorException('Instance is already registered');
        }

        return self::$instance = new static();
    }

    // Props

    /**
     * @return Logger
     */
    public function getLogger()
    {
        return $this['logger'];
    }

    public function setLoggerBuilder(callable $builder)
    {
        $this['logger'] = $builder;
    }

    /**
     * @return VpnApi
     */
    public function getVpnApi()
    {
        return $this['vpn_api'];
    }

    public function setVpnApiBuilder(callable $builder)
    {
        $this['vpn_api'] = $builder;
    }
}
