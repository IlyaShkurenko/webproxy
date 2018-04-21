<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\Client;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class ClientAreaSecondaryNavbar extends AbstractHookListener
{
    const KEY = 'ClientAreaSecondaryNavbar';
    protected $code = self::KEY;
}