<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\Authentication;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class ClientLoginShare extends AbstractHookListener
{
    const KEY = 'ClientLoginShare';
    protected $code = self::KEY;
}