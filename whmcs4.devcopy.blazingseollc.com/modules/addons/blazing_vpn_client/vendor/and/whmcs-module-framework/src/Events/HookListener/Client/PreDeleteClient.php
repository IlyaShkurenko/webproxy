<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\Client;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class PreDeleteClient extends AbstractHookListener
{
    const KEY = 'PreDeleteClient';
    protected $code = self::KEY;
}