<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\SupportTools;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class NetworkIssueAdd extends AbstractHookListener
{
    const KEY = 'NetworkIssueAdd';
    protected $code = self::KEY;
}