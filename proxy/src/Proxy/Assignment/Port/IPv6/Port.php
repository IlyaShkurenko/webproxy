<?php

namespace Proxy\Assignment\Port\IPv6;

use Proxy\Assignment\Port\PortInterface;
use Proxy\Assignment\RotationAdviser\IPv6\RotationAdviser;
use RuntimeException;

class Port extends Package implements PortInterface
{

    protected $blockId;

    protected $packageId;

    protected $ports = 1;

    /**
     * @var RotationAdviser
     */
    protected $rotationAdviser;

    public static function fromArray($data)
    {
        /** @var static $object */
        $object = parent::fromArray($data);

        if (!empty($data[ 'block_id' ])) {
            $object->setBlockId($data[ 'block_id' ]);
        }
        if (!empty($data[ 'package_id' ])) {
            $object->setPackageId($data[ 'package_id' ]);
        }

        return $object;
    }

    /**
     * Get blockId
     *
     * @return mixed
     */
    public function getBlockId()
    {
        return $this->blockId;
    }

    /**
     * Set blockId
     *
     * @param mixed $blockId
     * @return $this
     */
    public function setBlockId($blockId)
    {
        $this->blockId = $blockId;

        return $this;
    }

    /**
     * Get packageId
     *
     * @return mixed
     */
    public function getPackageId()
    {
        return $this->packageId;
    }

    /**
     * Set packageId
     *
     * @param mixed $packageId
     * @return $this
     */
    public function setPackageId($packageId)
    {
        $this->packageId = $packageId;

        return $this;
    }

    /**
     * Get proxy id
     *
     * @return int
     */
    public function getProxyId()
    {
        return $this->getBlockId();
    }

    /**
     * Set proxy id
     *
     * @param $proxyId
     * @return static
     */
    public function setProxyId($proxyId)
    {
        return $this->setBlockId($proxyId);
    }

    /**
     * @param \Proxy\Assignment\RotationAdviser\IPv6\RotationAdviser $rotationAdviser
     * @return $this
     */
    public function setRotationAdviser($rotationAdviser)
    {
        $this->_setRotationAdviser($rotationAdviser);

        return $this;
    }


    protected function _setRotationAdviser(RotationAdviser $rotationAdviser)
    {
        $this->rotationAdviser = $rotationAdviser;
    }

    /**
     * Don't forget to setRotationAdviser() before use
     *
     * @return bool|int false if not found, int if found
     */
    public function adviseNewProxyId()
    {
        if (!$this->rotationAdviser) {
            throw new RuntimeException('Rotation rotationAdviser is not set!');
        }

        // One and only option currently
        $result = $this->rotationAdviser->findDedicatedBlockId($this);
        if ($result) {
            return $result;
        }

        return false;
    }
}
