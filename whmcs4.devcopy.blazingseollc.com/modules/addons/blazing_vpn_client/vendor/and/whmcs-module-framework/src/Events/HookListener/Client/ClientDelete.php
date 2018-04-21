<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\Client;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class ClientDelete extends AbstractHookListener
{
    const KEY = 'ClientDelete';
    protected $code = self::KEY;
}