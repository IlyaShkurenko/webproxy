<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\Miscellaneous;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class EmailTplMergeFields extends AbstractHookListener
{
    const KEY = 'EmailTplMergeFields';
    protected $code = self::KEY;
}