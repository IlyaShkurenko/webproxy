<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\Service;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class AdminClientServicesTabFieldsSave extends AbstractHookListener
{
    const KEY = 'AdminClientServicesTabFieldsSave';
    protected $code = self::KEY;
}