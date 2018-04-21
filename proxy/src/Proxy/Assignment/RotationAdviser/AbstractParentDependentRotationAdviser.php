<?php

namespace Proxy\Assignment\RotationAdviser;

use Doctrine\DBAL\Connection;
use Proxy\Assignment\RotationAdviser\IPv4\RotationAdviser;

abstract class AbstractParentDependentRotationAdviser extends AbstractRotationAdviser
{
    /** @var AbstractRotationAdviser|RotationAdviser */
    protected $baseAdviser;

    public function __construct(Connection $conn, AbstractRotationAdviser $parent = null) {
        parent::__construct($conn, $parent->logger);
        $this->baseAdviser = $parent;
    }

    protected function getNameClassConfig()
    {
        return $this->baseAdviser->getNameClassConfig();
    }
}
