<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\Service;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class AdminServiceEdit extends AbstractHookListener
{
    const KEY = 'AdminServiceEdit';
    protected $code = self::KEY;
}