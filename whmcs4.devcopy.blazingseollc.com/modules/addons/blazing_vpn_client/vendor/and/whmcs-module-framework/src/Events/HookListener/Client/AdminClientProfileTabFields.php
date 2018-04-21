<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\Client;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class AdminClientProfileTabFields extends AbstractHookListener
{
    const KEY = 'AdminClientProfileTabFields';
    protected $code = self::KEY;
}