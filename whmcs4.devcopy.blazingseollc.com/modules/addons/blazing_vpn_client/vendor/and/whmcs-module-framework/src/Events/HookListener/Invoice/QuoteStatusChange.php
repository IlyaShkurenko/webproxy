<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\Invoice;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class QuoteStatusChange extends AbstractHookListener
{
    const KEY = 'QuoteStatusChange';
    protected $code = self::KEY;
}