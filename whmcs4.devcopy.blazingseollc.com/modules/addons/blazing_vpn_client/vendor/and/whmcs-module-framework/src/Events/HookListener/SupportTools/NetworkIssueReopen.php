<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\SupportTools;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class NetworkIssueReopen extends AbstractHookListener
{
    const KEY = 'NetworkIssueReopen';
    protected $code = self::KEY;
}