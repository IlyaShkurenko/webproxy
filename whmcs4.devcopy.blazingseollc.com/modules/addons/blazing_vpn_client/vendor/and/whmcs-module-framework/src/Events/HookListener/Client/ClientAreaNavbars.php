<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\Client;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class ClientAreaNavbars extends AbstractHookListener
{
    const KEY = 'ClientAreaNavbars';
    protected $code = self::KEY;
}