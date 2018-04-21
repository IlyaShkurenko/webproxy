<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\Client;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class AdminAreaClientSummaryActionLinks extends AbstractHookListener
{
    const KEY = 'AdminAreaClientSummaryActionLinks';
    protected $code = self::KEY;
}