<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\Client;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class AdminAreaClientSummaryPage extends AbstractHookListener
{
    const KEY = 'AdminAreaClientSummaryPage';
    protected $code = self::KEY;
}