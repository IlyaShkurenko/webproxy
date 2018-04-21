<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\Service;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class ServiceDelete extends AbstractHookListener
{
    const KEY = 'ServiceDelete';
    protected $code = self::KEY;
}