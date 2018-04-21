<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\Client;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class ClientAreaPrimarySidebar extends AbstractHookListener
{
    const KEY = 'ClientAreaPrimarySidebar';
    protected $code = self::KEY;
}