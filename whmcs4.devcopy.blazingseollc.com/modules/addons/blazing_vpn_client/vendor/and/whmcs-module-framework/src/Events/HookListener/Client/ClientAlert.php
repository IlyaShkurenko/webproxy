<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\Client;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class ClientAlert extends AbstractHookListener
{
    const KEY = 'ClientAlert';
    protected $code = self::KEY;
}