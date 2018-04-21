<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\Miscellaneous;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class CalcAffiliateCommission extends AbstractHookListener
{
    const KEY = 'CalcAffiliateCommission';
    protected $code = self::KEY;
}