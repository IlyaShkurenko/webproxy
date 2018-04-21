<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\SupportTools;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class NetworkIssueClose extends AbstractHookListener
{
    const KEY = 'NetworkIssueClose';
    protected $code = self::KEY;
}