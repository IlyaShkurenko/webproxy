<?php

namespace WHMCS\Module\Blazing\Proxy\Seller\Events;

use ErrorException;
use WHMCS\Module\Blazing\Proxy\Seller\Logger;
use WHMCS\Module\Blazing\Proxy\Seller\Seller;
use WHMCS\Module\Blazing\Proxy\Seller\UserService;

class ServiceSuspend extends AbstractModuleListener
{

    protected $name = 'SuspendAccount';

    protected function execute(array $args = null)
    {
        $serviceId = $args[ 'serviceid' ];
        $userId = $args[ 'userid' ];
        Logger::bindUserId($userId);

        Logger::debug('Package suspend', ['serviceId' => $serviceId, 'userId' => $userId]);

        $service = UserService::findByCustomerServiceId($serviceId);
        if (!$service) {
            throw new ErrorException(sprintf('Service "%s" is not found', $serviceId));
        }
        $this->onExceptionVars['service'] = $service;

        if (in_array($service->getStatus(), [
            UserService::STATUS_ACTIVE,
            UserService::STATUS_ACTIVE_UPGRADING,
            UserService::STATUS_ACTIVE_UPGRADED
        ])) {
            (new Seller())->suspendPackage($service);
        }
        else {
            throw new ErrorException('Package status should be active to suspend it it');
        }

        return true;
    }
}