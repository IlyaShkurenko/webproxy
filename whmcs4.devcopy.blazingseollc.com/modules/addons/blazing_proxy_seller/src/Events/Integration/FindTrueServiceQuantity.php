<?php

namespace WHMCS\Module\Blazing\Proxy\Seller\Events\Integration;

use WHMCS\Module\Blazing\Proxy\Seller\UserService;
use WHMCS\Module\Framework\Events\AbstractHookListener;

class FindTrueServiceQuantity extends AbstractHookListener
{
    protected $name = 'FindServiceQuantity';

    /**
     * @param array
     * @return mixed
     */
    protected function execute(array $args = [])
    {
        if (!empty($args['serviceId'])) {
            $serviceId = $args['serviceId'];
            $service = UserService::findByCustomerServiceId($serviceId);
            if ($service) {
                return [
                    'status' => 'ok',
                    'quantity' => $service->getQuantity()
                ];
            }
        }

        return [
            'status' => 'error',
            'error' => 'Not found',
            'args' => $args
        ];
    }
}
