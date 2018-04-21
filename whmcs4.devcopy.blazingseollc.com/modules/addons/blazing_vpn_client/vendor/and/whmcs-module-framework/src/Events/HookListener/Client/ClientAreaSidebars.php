<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\Client;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class ClientAreaSidebars extends AbstractHookListener
{
    const KEY = 'ClientAreaSidebars';
    protected $code = self::KEY;
}