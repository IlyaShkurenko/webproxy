<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\Contact;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class ContactEdit extends AbstractHookListener
{
    const KEY = 'ContactEdit';
    protected $code = self::KEY;
}