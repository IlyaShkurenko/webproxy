<?php

namespace Proxy\Assignment\Port;

trait AggregatedPortsTrait
{
    protected $totalPortCount = 0;
    protected $actualPortCount = 0;
    protected $packageId;

    /**
     * Get totalPortCount
     *
     * @return int
     */
    public function getTotalPortsCount()
    {
        return $this->totalPortCount;
    }

    /**
     * Set totalPortCount
     *
     * @param int $totalPortCount
     * @return $this
     */
    public function setTotalPortCount($totalPortCount)
    {
        $this->totalPortCount = $totalPortCount;

        return $this;
    }

    /**
     * Get actualPortCount
     *
     * @return int
     */
    public function getActualPortCount()
    {
        return $this->actualPortCount;
    }

    /**
     * Set actualPortCount
     *
     * @param int $actualPortCount
     * @return $this
     */
    public function setActualPortCount($actualPortCount)
    {
        $this->actualPortCount = $actualPortCount;

        return $this;
    }

    /**
     * Get package id
     *
     * @return int
     */
    public function getPackageId()
    {
        return (int) $this->packageId;
    }

    /**
     * Set package id
     *
     * @param $packageId
     * @return static
     */
    public function setPackageId($packageId)
    {
        $this->packageId = (int) $packageId;

        return $this;
    }
}