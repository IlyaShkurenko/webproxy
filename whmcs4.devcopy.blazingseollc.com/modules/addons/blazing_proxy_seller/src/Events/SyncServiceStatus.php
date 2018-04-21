<?php

namespace WHMCS\Module\Blazing\Proxy\Seller\Events;

use WHMCS\Module\Blazing\Proxy\Seller\Logger;
use WHMCS\Module\Blazing\Proxy\Seller\UserService;
use WHMCS\Module\Framework\Events\AbstractHookListener;
use WHMCS\Module\Framework\Helper;

class SyncServiceStatus extends AbstractHookListener
{

    protected $name = 'ServiceEdit';

    protected function execute(array $args = null)
    {
        $userId = $args[ 'userid' ];
        $serviceId = $args[ 'serviceid' ];

        Logger::bindUserId($userId);

        if ($service = UserService::findByCustomerServiceId($serviceId)) {
            $response = Helper::apiResponse('getClientsProducts', ['serviceId' => $serviceId], 'products.product.0');
            $serviceData = $response[ 'products' ][ 'product' ][ 0 ];

            // Active ~> Cancelled
            if ('Cancelled' == $serviceData[ 'status' ] and
                in_array($service->getStatus(), [
                    UserService::STATUS_ACTIVE,
                    UserService::STATUS_ACTIVE_UPGRADING,
                    UserService::STATUS_ACTIVE_UPGRADED,
                    UserService::STATUS_SUSPENDED
                ])
            ) {
                Logger::info(sprintf('Update user service status (%s ~> %s)',
                    $service->getStatus(), UserService::STATUS_CANCELLED), [$service]);

                $service->setStatus(UserService::STATUS_CANCELLED)->save();
                $service->getCallback()->call('serviceUpdate');
            }
            // Cancelled ~> Active
            elseif (in_array($serviceData[ 'status' ], ['Pending', 'Active']) and
                UserService::STATUS_CANCELLED == $service->getStatus()
            ) {
                Logger::info(sprintf('Update user service status (%s ~> %s)',
                    $service->getStatus(), UserService::STATUS_ACTIVE), [$service]);

                $service->setStatus(UserService::STATUS_ACTIVE)->save();
                $service->getCallback()->call('serviceUpdate');
            }
        }
    }
}
