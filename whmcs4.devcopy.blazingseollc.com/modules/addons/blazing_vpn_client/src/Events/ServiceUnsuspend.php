<?php

namespace Blazing\Vpn\Client\Events;

use Blazing\Vpn\Client\Container;
use Blazing\Vpn\Client\VpnApi;
use ErrorException;

class ServiceUnsuspend extends AbstractFunction
{
    protected $name = 'UnsuspendAccount';

    /**
     * @param array $args
     * @return mixed
     * @throws ErrorException
     */
    protected function execute(array $args = null)
    {
        $serviceId = $args[ 'serviceid' ];
        $userId = $args[ 'userid' ];
        $api = Container::getInstance()->getVpnApi();

        $responce = $api->getServiceData($serviceId);
        if (VpnApi::STATUS_SUSPENDED != $responce['data']['status']) {
            throw new ErrorException('Service should be suspended to unsuspend it');
        }

        $api->updateVpnService($userId, $serviceId, VpnApi::STATUS_ACTIVE);

        return true;
    }
}
