<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\SupportTools;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class FileDownload extends AbstractHookListener
{
    const KEY = 'FileDownload';
    protected $code = self::KEY;
}