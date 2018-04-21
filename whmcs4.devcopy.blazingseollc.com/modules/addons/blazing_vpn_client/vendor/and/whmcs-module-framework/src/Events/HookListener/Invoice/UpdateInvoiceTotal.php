<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\Invoice;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class UpdateInvoiceTotal extends AbstractHookListener
{
    const KEY = 'UpdateInvoiceTotal';
    protected $code = self::KEY;
}