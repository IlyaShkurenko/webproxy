<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\Addon;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class LicensingAddonVerify extends AbstractHookListener
{
    const KEY = 'LicensingAddonVerify';
    protected $code = self::KEY;
}