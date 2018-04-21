<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\Miscellaneous;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class LinkTracker extends AbstractHookListener
{
    const KEY = 'LinkTracker';
    protected $code = self::KEY;
}