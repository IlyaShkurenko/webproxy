<?php

namespace Proxy\Assignment\Port;

interface AggregatedPortsInterface extends AbstractPackageInterface
{
    /**
     * Get totalPortCount
     *
     * @return int
     */
    public function getTotalPortsCount();

    /**
     * Set totalPortCount
     *
     * @param int $totalPortCount
     * @return $this
     */
    public function setTotalPortCount($totalPortCount);

    /**
     * Get actualPortCount
     *
     * @return int
     */
    public function getActualPortCount();

    /**
     * Set actualPortCount
     *
     * @param int $actualPortCount
     * @return $this
     */
    public function setActualPortCount($actualPortCount);

    /**
     * Get package id
     * @return int
     */
    public function getPackageId();

    /**
     * Set package id
     * @param $packageId
     * @return static
     */
    public function setPackageId($packageId);
}
