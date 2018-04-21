<?php

namespace WHMCS\Module\Blazing\Proxy\Seller\Events;

use ErrorException;
use WHMCS\Module\Blazing\Proxy\Seller\Logger;
use WHMCS\Module\Blazing\Proxy\Seller\Seller;
use WHMCS\Module\Blazing\Proxy\Seller\UserService;

class ServiceTerminate extends AbstractModuleListener
{

    protected $name = 'TerminateAccount';

    protected function execute(array $args = null)
    {
        $serviceId = $args[ 'serviceid' ];
        $userId = $args[ 'userid' ];
        Logger::bindUserId($userId);

        Logger::debug('Package termination', ['serviceId' => $serviceId, 'userId' => $userId]);

        $service = UserService::findByCustomerServiceId($serviceId);
        if (!$service) {
            throw new ErrorException(sprintf('Service "%s" is not found', $serviceId));
        }

        (new Seller())->cancelPackage($service);

        return true;
    }
}
