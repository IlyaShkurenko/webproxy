<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\Client;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class ClientAreaRegister extends AbstractHookListener
{
    const KEY = 'ClientAreaRegister';
    protected $code = self::KEY;
}