<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\Client;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class AdminClientProfileTabFieldsSave extends AbstractHookListener
{
    const KEY = 'AdminClientProfileTabFieldsSave';
    protected $code = self::KEY;
}