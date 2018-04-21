<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\Miscellaneous;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class CCUpdate extends AbstractHookListener
{
    const KEY = 'CCUpdate';
    protected $code = self::KEY;
}