<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\Client;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class ClientAreaPrimaryNavbar extends AbstractHookListener
{
    const KEY = 'ClientAreaPrimaryNavbar';
    protected $code = self::KEY;
}