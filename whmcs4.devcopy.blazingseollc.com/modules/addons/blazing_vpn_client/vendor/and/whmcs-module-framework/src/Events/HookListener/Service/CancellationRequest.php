<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\Service;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class CancellationRequest extends AbstractHookListener
{
    const KEY = 'CancellationRequest';
    protected $code = self::KEY;
}