<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\Invoice;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class acceptQuote extends AbstractHookListener
{
    const KEY = 'acceptQuote';
    protected $code = self::KEY;
}