<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\Contact;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class ContactAdd extends AbstractHookListener
{
    const KEY = 'ContactAdd';
    protected $code = self::KEY;
}