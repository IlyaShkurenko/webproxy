<?php

namespace Migration\Mapping;

use Doctrine\ORM\Mapping as ORM;

/**
 * ProxyServersIpv6
 *
 * @ORM\Table(name="proxy_servers_ipv6", uniqueConstraints={@ORM\UniqueConstraint(name="ip", columns={"ip"}), @ORM\UniqueConstraint(name="name", columns={"name"})})
 * @ORM\Entity
 */
class ProxyServersIpv6
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
   * @ORM\Column(name="ip", type="string", length=15, nullable=false)
   */
  private $ip;

  /**
   * @var string
   *
   * @ORM\Column(name="name", type="string", length=64, nullable=false)
   */
  private $name;

  /**
   * @var \DateTime
   *
   * @ORM\Column(name="created_at", type="datetime", nullable=false)
   */
  private $createdAt;

  /**
   * @var \DateTime
   *
   * @ORM\Column(name="updated_at", type="datetime", nullable=false)
   */
  private $updatedAt;


}

