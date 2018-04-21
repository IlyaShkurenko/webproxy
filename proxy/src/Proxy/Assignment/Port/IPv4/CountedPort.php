<?php

namespace Proxy\Assignment\Port\IPv4;

use Proxy\Assignment\Port\AggregatedPortsInterface;
use Proxy\Assignment\Port\AggregatedPortsTrait;

class CountedPort extends Port implements AggregatedPortsInterface
{
    use AggregatedPortsTrait;
}