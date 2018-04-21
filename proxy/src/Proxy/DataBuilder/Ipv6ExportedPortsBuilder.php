<?php

namespace Proxy\DataBuilder;

use Proxy\Model\ExportedPort;

class Ipv6ExportedPortsBuilder
{

    /**
     * @var ExportedPort[]
     */
    protected $ports = [];

    // Keys
    protected $userId;
    protected $packageId;
    protected $serverId;

    public function addExportedPort(ExportedPort $port)
    {
        if (!$port->isFulfilled()) {
            throw new \ErrorException('Port data is not fulfilled!');
        }

        if (!$this->ports) {
            $this->userId = $port->getUserId();
            $this->packageId = $port->getPackageId();
            $this->serverId = $port->getServerId();
        }

        // Do not add if it contains different keys
        if ($this->userId != $port->getUserId() or $this->packageId != $port->getPackageId() or $this->serverId != $port->getServerId()) {
            return false;
        }

        $this->ports[] = $port;

        // Was added
        return true;
    }

    public function build()
    {

    }
}
