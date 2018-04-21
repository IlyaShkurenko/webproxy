<?php

namespace Migration\Mapping;

use Doctrine\ORM\Mapping as ORM;

/**
 * AssignerIpv4KushangBlacklistIp
 *
 * @ORM\Table(name="assigner_ipv4_kushang_blacklist_ip", uniqueConstraints={@ORM\UniqueConstraint(name="ip", columns={"ip"})})
 * @ORM\Entity
 */
class AssignerIpv4KushangBlacklistIp
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
   * @ORM\Column(name="ip", type="string", length=32, nullable=false)
   */
  private $ip;


}

