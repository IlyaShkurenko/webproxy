<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\Client;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class ClientEdit extends AbstractHookListener
{
    const KEY = 'ClientEdit';
    protected $code = self::KEY;
}