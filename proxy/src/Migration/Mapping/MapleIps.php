<?php

namespace Migration\Mapping;

use Doctrine\ORM\Mapping as ORM;

/**
 * MapleIps
 *
 * @ORM\Table(name="maple_ips")
 * @ORM\Entity
 */
class MapleIps
{
  /**
   * @var integer
   *
   * @ORM\Column(name="id", type="integer", nullable=false)
   * @ORM\Id
   * @ORM\GeneratedValue(strategy="IDENTITY")
   */
  private $id;

  /**
   * @var string
   *
   * @ORM\Column(name="ip", type="string", length=16, nullable=true)
   */
  private $ip;


}

