<?php

namespace Proxy\Assignment\Port\IPv6;

use Proxy\Assignment\Port\AggregatedPortsInterface;
use Proxy\Assignment\Port\AggregatedPortsTrait;

class AggregatedPorts extends Package implements AggregatedPortsInterface
{
    use AggregatedPortsTrait;

    public static function fromArray($data)
    {
        /** @var static $object */
        $object = parent::fromArray($data);

        if ($object->getPorts()) {
            $object->setTotalPortCount($object->getPorts());
        }
        if (!empty($data['package_id'])) {
            $object->setPackageId($data['package_id']);
        }

        return $object;
    }

    public static function convertFrom(Package $from)
    {
        $data = $from->toArray();

        if (Package::class === get_class($from)) {
            $data['package_id'] = $from->getId();
        }

        return static::fromArray($data);
    }
}
