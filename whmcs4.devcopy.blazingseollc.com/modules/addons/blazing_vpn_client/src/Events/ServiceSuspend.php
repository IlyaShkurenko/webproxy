<?php

namespace Blazing\Vpn\Client\Events;

use Blazing\Vpn\Client\Container;
use Blazing\Vpn\Client\VpnApi;
use ErrorException;

class ServiceSuspend extends AbstractFunction
{
    protected $name = 'SuspendAccount';

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
        if (VpnApi::STATUS_ACTIVE != $responce['data']['status']) {
            throw new ErrorException('Service should be active to suspend it');
        }

        $api->updateVpnService($userId, $serviceId, VpnApi::STATUS_SUSPENDED);

        return true;
    }
}
