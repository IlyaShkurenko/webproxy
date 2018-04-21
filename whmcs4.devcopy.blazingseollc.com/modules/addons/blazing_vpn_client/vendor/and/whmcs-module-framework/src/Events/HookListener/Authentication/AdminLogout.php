<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\Authentication;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class AdminLogout extends AbstractHookListener
{
    const KEY = 'AdminLogout';
    protected $code = self::KEY;
}