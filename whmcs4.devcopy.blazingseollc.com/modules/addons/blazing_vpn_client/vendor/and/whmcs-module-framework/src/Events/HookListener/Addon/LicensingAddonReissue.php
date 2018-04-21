<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\Addon;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class LicensingAddonReissue extends AbstractHookListener
{
    const KEY = 'LicensingAddonReissue';
    protected $code = self::KEY;
}