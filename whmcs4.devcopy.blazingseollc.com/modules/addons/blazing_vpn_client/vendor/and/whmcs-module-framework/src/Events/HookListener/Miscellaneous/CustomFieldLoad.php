<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\Miscellaneous;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class CustomFieldLoad extends AbstractHookListener
{
    const KEY = 'CustomFieldLoad';
    protected $code = self::KEY;
}