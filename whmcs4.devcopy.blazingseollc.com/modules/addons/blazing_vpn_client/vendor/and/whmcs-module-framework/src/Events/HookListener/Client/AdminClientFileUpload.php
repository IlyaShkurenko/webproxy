<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\Client;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class AdminClientFileUpload extends AbstractHookListener
{
    const KEY = 'AdminClientFileUpload';
    protected $code = self::KEY;
}