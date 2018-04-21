<?php

namespace WHMCS\Module\Blazing\Proxy\Seller\EmitterEvents;

use WHMCS\Module\Blazing\Proxy\Seller\UserService;

class BeforeUpgradeEvent extends AbstractEvent
{

    /**
     * @var UserService
     */
    private $service;

    public function __construct(UserService $service)
    {

        $this->service = $service;
    }

    /**
     * Get service
     *
     * @return UserService
     */
    public function getService()
    {
        return $this->service;
    }
}
