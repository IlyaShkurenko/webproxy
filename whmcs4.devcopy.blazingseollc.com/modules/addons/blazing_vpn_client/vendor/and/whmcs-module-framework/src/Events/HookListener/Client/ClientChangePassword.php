<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\Client;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class ClientChangePassword extends AbstractHookListener
{
    const KEY = 'ClientChangePassword';
    protected $code = self::KEY;
}