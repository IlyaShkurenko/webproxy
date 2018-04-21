<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\Invoice;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class QuoteCreated extends AbstractHookListener
{
    const KEY = 'QuoteCreated';
    protected $code = self::KEY;
}