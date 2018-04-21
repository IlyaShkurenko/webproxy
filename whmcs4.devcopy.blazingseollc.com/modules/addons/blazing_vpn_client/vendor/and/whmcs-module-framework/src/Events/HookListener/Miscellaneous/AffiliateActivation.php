<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\Miscellaneous;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class AffiliateActivation extends AbstractHookListener
{
    const KEY = 'AffiliateActivation';
    protected $code = self::KEY;
}