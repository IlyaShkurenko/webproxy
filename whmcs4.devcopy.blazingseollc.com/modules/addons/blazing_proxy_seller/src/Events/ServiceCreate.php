<?php

namespace WHMCS\Module\Blazing\Proxy\Seller\Events;

use WHMCS\Module\Blazing\Proxy\Seller\Logger;
use WHMCS\Module\Blazing\Proxy\Seller\Seller;
use WHMCS\Module\Blazing\Proxy\Seller\UserService;

class ServiceCreate extends AbstractModuleListener
{
    protected $name = 'CreateAccount';

    protected function execute(array $args = null)
    {
        $serviceId = $args[ 'serviceid' ];
        $userId = $args[ 'userid' ];
        Logger::bindUserId($userId);

        $userService = UserService::findByCustomerServiceId($serviceId);

        if (!$userService) {
            Logger::debug("\"$this->name\" for unexistent package", ['serviceId' => $serviceId, 'userId' => $userId]);
            return 'Package not found, probably it has never been created!';
        }
        $status = $userService->getStatus();
        Logger::info("\"$this->name\" for \"$status\" package", ['serviceId' => $serviceId, 'userId' => $userId]);

        // New package
        if ($userService and UserService::STATUS_NEW == $userService->getStatus()) {
            (new Seller())->processNewPackage($userService);
        }

        // Upgrade package
        elseif ($userService and UserService::STATUS_ACTIVE_UPGRADING == $userService->getStatus()) {
            (new Seller())->processUpgrade($userService);
        }
        else {
            return 'Package has not been processed (probably already processed), status: "' . $userService->getStatus() . '"';
        }

        return true;
    }
}
