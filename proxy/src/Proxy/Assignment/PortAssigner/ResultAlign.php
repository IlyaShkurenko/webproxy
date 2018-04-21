<?php

namespace Proxy\Assignment\PortAssigner;

use Proxy\Assignment\Port\AbstractPackage;

class ResultAlign
{

    protected $changedCount = 0;

    protected $removedPorts = [];

    protected $addedPorts = [];

    public function mergeWith(self $result)
    {
        $this->removedPorts = array_merge($result->getRemovedPorts());
        $this->addedPorts = array_merge($result->getAddedPorts());
    }

    /**
     * Set ports
     *
     * @param array $ports
     * @return $this
     */
    public function setRemovedPorts(array $ports)
    {
        $this->removedPorts = $ports;

        return $this;
    }

    /**
     * Add port to added list

*
*@param \Proxy\Assignment\Port\IPv4\Port $port
     * @return $this
     */
    public function addRemovedPort(AbstractPackage $port)
    {
        $this->removedPorts[] = $port;

        return $this;
    }

    /**
     * Get ports

     *
*@return AbstractPackage[]
     */
    public function getRemovedPorts()
    {
        return $this->removedPorts;
    }

    /**
     * Set ports
     *
*@param AbstractPackage[] $ports
     * @return $this
     */
    public function setAddedPorts(array $ports)
    {
        $this->addedPorts = $ports;

        return $this;
    }

    /**
     * Add port to added list

     *
*@param AbstractPackage $port
     * @return $this
     */
    public function addAddedPort(AbstractPackage $port)
    {
        $this->addedPorts[] = $port;

        return $this;
    }

    /**
     * Get ports

     *
*@return AbstractPackage[]
     */
    public function getAddedPorts()
    {
        return $this->addedPorts;
    }

    /**
     * Get changed ports count. Value is positive
     *
     * @return int
     */
    public function getChangedCount()
    {
        return count($this->addedPorts) + count($this->removedPorts);
    }

    public function isIncremented()
    {
        return count($this->addedPorts) > 0;
    }

    public function isDecremented()
    {
        return count($this->removedPorts) > 0;
    }
}
