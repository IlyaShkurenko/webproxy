<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\Authentication;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class ClientLogin extends AbstractHookListener
{
    const KEY = 'ClientLogin';
    protected $code = self::KEY;
}