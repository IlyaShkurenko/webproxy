<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\Miscellaneous;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class EmailPreSend extends AbstractHookListener
{
    const KEY = 'EmailPreSend';
    protected $code = self::KEY;
}