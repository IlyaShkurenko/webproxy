<?php

namespace Proxy\Events;

use Common\Events\Events\AbstractEventWithResult;
use Proxy\Assignment\Port\PortInterface;

class CheckPortsAssignment extends AbstractEventWithResult
{

    protected $result = true;
    /**
     * @var Port
     */
    protected $port;
    /**
     * @var int
     */
    protected $resellerId;

    function __construct(PortInterface $port, $resellerId)
    {
        $this->port = $port;
        $this->resellerId = $resellerId;
    }

    /**
     * Get port
     *
     * @return PortInterface
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * Get resellerId
     *
     * @return int
     */
    public function getResellerId()
    {
        return $this->resellerId;
    }
}
