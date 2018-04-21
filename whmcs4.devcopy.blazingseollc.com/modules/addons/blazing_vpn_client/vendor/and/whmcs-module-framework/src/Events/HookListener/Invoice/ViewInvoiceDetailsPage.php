<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\Invoice;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class ViewInvoiceDetailsPage extends AbstractHookListener
{
    const KEY = 'ViewInvoiceDetailsPage';
    protected $code = self::KEY;
}