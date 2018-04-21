<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\SupportTools;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class NetworkIssueDelete extends AbstractHookListener
{
    const KEY = 'NetworkIssueDelete';
    protected $code = self::KEY;
}