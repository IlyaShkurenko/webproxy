<?php

namespace Proxy\Assignment\Port\IPv6;

use Proxy\Assignment\Port\AbstractPackage;

class Package extends AbstractPackage
{
    const INTERNET_PROTOCOL = '6';

    const TYPE_BLOCK_N_PER_BLOCK = 'block_n_per_block';

    protected $ipV = self::INTERNET_PROTOCOL;
    protected $ports = 0;

    public static function fromArray($data)
    {
        $object = new static();

        $object->setId($data['id']);
        $object->setUserId($data['user_id']);

        if (!empty($data['type'])) {
            $object->setType($data['type']);
        }
        if (!empty($data['ext'])) {
            $object->setExt($data['ext']);
        }
        if (!empty($data['ports'])) {
            $object->setPorts($data['ports']);
        }

        return $object;
    }

    public function toArray()
    {
        return [
            'id' => $this->getId(),
            'user_id' => $this->getUserId(),
            'type' => $this->getType(),
            'ext' => $this->getExt(),
            'ports' => $this->getPorts()
        ];
    }

    /**
     * @return static
     */
    public static function construct()
    {
        $object = new static();

        return $object;
    }

    public static function convertFrom(Package $from)
    {
        return static::fromArray($from->toArray());
    }

    /**
     * Get ports
     *
     * @return int
     */
    public function getPorts()
    {
        return $this->ports;
    }

    /**
     * Set ports
     *
     * @param int $ports
     * @return $this
     */
    public function setPorts($ports)
    {
        $this->ports = $ports;

        return $this;
    }
}
