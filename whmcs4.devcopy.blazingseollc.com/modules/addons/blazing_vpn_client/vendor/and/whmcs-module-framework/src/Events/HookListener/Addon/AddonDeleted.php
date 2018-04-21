<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\Addon;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class AddonDeleted extends AbstractHookListener
{
    const KEY = 'AddonDeleted';
    protected $code = self::KEY;
}