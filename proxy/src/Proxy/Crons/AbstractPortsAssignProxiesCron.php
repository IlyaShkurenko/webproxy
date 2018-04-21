<?php

namespace Proxy\Crons;

use Proxy\Assignment\RotationAdviser\IPv4;
use Proxy\Assignment\RotationAdviser\IPv6;
use Proxy\Assignment\Port\PortInterface;
use Proxy\Assignment\PortAssigner;

abstract class AbstractPortsAssignProxiesCron extends AbstractDefaultSettingsCron
{
    protected $adviser;
    protected $assigner;

    /**
     * Fully automated method

     * @param Port $port
     * @param bool $updateRotated
     */
    protected function adviseAndAssignNewProxy(PortInterface $port, $updateRotated = true)
    {
        try {
            $port->setRotationAdviser($this->getRotationAdviser());
            $proxyId = $port->adviseNewProxyId();
        }
        catch (\Exception $e) {
            $this->output('RotationAdviser exception: ' . $e->getMessage());
        }

        // If any found
        $previousProxyId = $port->getProxyId();
        if (!empty($proxyId)) {
            if (!$this->getSetting('dryRun')) {
                $this->assignNewProxy($port, $proxyId, $updateRotated);
            }
        }
        else {
            $this->notAssignedNewProxy($port);

            // Set last assignment attempt
            if ($port->getId()) {
                $this->getConn()->update('user_ports', ['time_assignment_attempt' => date('Y-m-d H:i:s')], ['id' => $port->getId()]);
            }
        }

        $this->log($this->getProxyInfoLog($port, !empty($proxyId) ? $proxyId : false, $previousProxyId), [
            'previousProxyId' => $previousProxyId,
            'newProxyId' => !empty($proxyId) ? $proxyId : false,
            'port' => $port->toArray()
        ], ['userId' => $port->getUserId()]);
    }

    /**
     * Can be extended. Output info in either cases port found or not
     *
     * @param PortInterface $port
     * @param int|bool $newProxyId False if not found
     * @param int $previousProxyId
     * @return string
     */
    abstract protected function getProxyInfoLog(PortInterface $port, $newProxyId, $previousProxyId);

    /**
     * Can be extended. Assigns new proxy if found
     *
     * @param PortInterface $port
     * @param $proxyId
     * @param bool $updateRotated
     */
    protected function assignNewProxy(PortInterface $port, $proxyId, $updateRotated = true)
    {
        $this->getPortAssigner()->assignPortProxy($port, $proxyId, $updateRotated);
    }

    /**
     * Can be extended. Called if new proxy has not found

     * @param PortInterface $port
     */
    protected function notAssignedNewProxy(PortInterface $port)
    {

    }

    /**
     * Get rotation adviser
     *
     * @return IPv4\RotationAdviser|IPv6\RotationAdviser
     */
    protected function getRotationAdviser()
    {
        if (!$this->adviser) {
            $this->adviser = $this->buildRotationAdviser();
        }

        return $this->adviser;
    }

    /**
     * @return IPv4\RotationAdviser|IPv6\RotationAdviser
     */
    abstract protected function buildRotationAdviser();

    /**
     * Get rotation adviser
     *
     * @return PortAssigner
     */
    protected function getPortAssigner()
    {
        if (!$this->assigner) {
            $this->assigner = new PortAssigner($this->getConn());
        }

        return $this->assigner;
    }
}
