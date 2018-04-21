<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\Client;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class AfterClientMerge extends AbstractHookListener
{
    const KEY = 'AfterClientMerge';
    protected $code = self::KEY;
}