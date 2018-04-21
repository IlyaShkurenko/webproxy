<?php

namespace Blazing\Vpn\Client\Events;

use Blazing\Vpn\Client\Container;
use Blazing\Vpn\Client\VpnApi;
use ErrorException;

class ServiceTerminate extends AbstractFunction
{
    protected $name = 'TerminateAccount';

    /**
     * @param array $args
     * @return mixed
     * @throws ErrorException
     */
    protected function execute(array $args = null)
    {
        $serviceId = $args[ 'serviceid' ];
        $userId = $args[ 'userid' ];

        Container::getInstance()->getVpnApi()->updateVpnService($userId, $serviceId, VpnApi::STATUS_CANCELLED);

        return true;
    }
}
