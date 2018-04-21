<?php

namespace WHMCS\Module\Blazing\Proxy\Seller\Events;

use ErrorException;
use WHMCS\Module\Blazing\Proxy\Seller\Logger;
use WHMCS\Module\Blazing\Proxy\Seller\Seller;
use WHMCS\Module\Blazing\Proxy\Seller\UserService;

class ServiceUnsuspend extends AbstractModuleListener
{

    protected $name = 'UnsuspendAccount';

    protected function execute(array $args = null)
    {
        $serviceId = $args[ 'serviceid' ];
        $userId = $args[ 'userid' ];
        Logger::bindUserId($userId);

        Logger::debug('Package unsuspend', ['serviceId' => $serviceId, 'userId' => $userId]);

        $service = UserService::findByCustomerServiceId($serviceId);
        if (!$service) {
            Logger::err(sprintf('Service "%s" is not found', $serviceId));

            return sprintf('Service "%s" is not found in db', $serviceId);
        }
        $this->onExceptionVars['service'] = $service;

        if (UserService::STATUS_SUSPENDED == $service->getStatus()) {
            (new Seller())->unsuspendPackage($service);
        }
        else {
            throw new ErrorException('Package status should be suspended to unsuspend it');
        }

        return true;
    }
}
