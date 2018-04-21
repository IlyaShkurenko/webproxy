<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\Client;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class ClientAreaHomepage extends AbstractHookListener
{
    const KEY = 'ClientAreaHomepage';
    protected $code = self::KEY;
}