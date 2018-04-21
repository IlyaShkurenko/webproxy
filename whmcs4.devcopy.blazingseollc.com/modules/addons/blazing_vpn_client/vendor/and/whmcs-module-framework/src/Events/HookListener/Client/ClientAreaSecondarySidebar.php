<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\Client;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class ClientAreaSecondarySidebar extends AbstractHookListener
{
    const KEY = 'ClientAreaSecondarySidebar';
    protected $code = self::KEY;
}