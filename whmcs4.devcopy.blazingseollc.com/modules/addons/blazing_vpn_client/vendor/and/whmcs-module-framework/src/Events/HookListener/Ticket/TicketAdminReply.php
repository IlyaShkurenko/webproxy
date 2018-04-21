<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\Ticket;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class TicketAdminReply extends AbstractHookListener
{
    const KEY = 'TicketAdminReply';
    protected $code = self::KEY;
}