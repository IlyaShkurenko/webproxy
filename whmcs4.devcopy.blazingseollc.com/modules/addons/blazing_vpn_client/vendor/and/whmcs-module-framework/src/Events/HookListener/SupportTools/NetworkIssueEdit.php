<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\SupportTools;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class NetworkIssueEdit extends AbstractHookListener
{
    const KEY = 'NetworkIssueEdit';
    protected $code = self::KEY;
}