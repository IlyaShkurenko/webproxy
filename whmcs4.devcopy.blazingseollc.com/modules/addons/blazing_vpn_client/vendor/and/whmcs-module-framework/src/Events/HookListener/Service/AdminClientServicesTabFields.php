<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\Service;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class AdminClientServicesTabFields extends AbstractHookListener
{
    const KEY = 'AdminClientServicesTabFields';
    protected $code = self::KEY;
}