<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\Ticket;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class TransliterateTicketText extends AbstractHookListener
{
    const KEY = 'TransliterateTicketText';
    protected $code = self::KEY;
}