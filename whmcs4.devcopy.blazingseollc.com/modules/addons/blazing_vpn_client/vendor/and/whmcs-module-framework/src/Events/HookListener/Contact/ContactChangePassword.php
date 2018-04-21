<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\Contact;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class ContactChangePassword extends AbstractHookListener
{
    const KEY = 'ContactChangePassword';
    protected $code = self::KEY;
}