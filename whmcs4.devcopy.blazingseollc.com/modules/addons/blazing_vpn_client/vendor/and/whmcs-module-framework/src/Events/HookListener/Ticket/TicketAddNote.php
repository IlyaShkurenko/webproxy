<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\Ticket;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class TicketAddNote extends AbstractHookListener
{
    const KEY = 'TicketAddNote';
    protected $code = self::KEY;
}