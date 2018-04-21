<?php

namespace Blazing\Vpn\Client\Vendor\Blazing\Logger\Formatter;

use Blazing\Vpn\Client\Vendor\Monolog\Formatter\LineFormatter as BaseLineFormatter;
class RequestIdLineFormatter extends BaseLineFormatter
{
    const SIMPLE_FORMAT = "[%datetime%] %extra.uid%.%level_name%: %message% %context% %extra%\n";
    const SIMPLE_DATE = "Y-m-d H:i:s.u";
}