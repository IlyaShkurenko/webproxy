<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\Authentication;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class ClientLogout extends AbstractHookListener
{
    const KEY = 'ClientLogout';
    protected $code = self::KEY;
}