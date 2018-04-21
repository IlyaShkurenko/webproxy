<?php

namespace Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\HookListener\Ticket;

use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Events\AbstractHookListener;
abstract class SubmitTicketAnswerSuggestions extends AbstractHookListener
{
    const KEY = 'SubmitTicketAnswerSuggestions';
    protected $code = self::KEY;
}