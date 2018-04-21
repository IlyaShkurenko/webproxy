<?php

namespace Proxy\Assignment\Port;

use Proxy\Assignment\RotationAdviser\IPv4;
use Proxy\Assignment\RotationAdviser\IPv6;

interface PortInterface extends AbstractPackageInterface
{

    /**
     * Get proxy id
     *
     * @return int
     */
    public function getProxyId();

    /**
     * Set proxy id
     *
     * @param $proxyId
     * @return static
     */
    public function setProxyId($proxyId);

    /**
     * @param IPv4\RotationAdviser|IPv6\RotationAdviser $rotationAdviser
     * @return $this
     */
    public function setRotationAdviser($rotationAdviser);

    /**
     * Don't forget to setRotationAdviser() before use
     *
     * @return bool|int false if not found, int if found
     */
    public function adviseNewProxyId();
}