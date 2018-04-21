<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\Miscellaneous;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class CustomFieldSave extends AbstractHookListener
{
    const KEY = 'CustomFieldSave';
    protected $code = self::KEY;
}