<?php

namespace Migration\Mapping;

use Doctrine\ORM\Mapping as ORM;

/**
 * ProxiesIpv6Sources
 *
 * @ORM\Table(name="proxies_ipv6_sources", uniqueConstraints={@ORM\UniqueConstraint(name="block", columns={"block"})})
 * @ORM\Entity
 */
class ProxiesIpv6Sources
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
   * @ORM\Column(name="block", type="string", length=39, nullable=false)
   */
  private $block;

  /**
   * @var boolean
   *
   * @ORM\Column(name="subnet", type="boolean", nullable=false)
   */
  private $subnet = '32';

  /**
   * @var integer
   *
   * @ORM\Column(name="server_id", type="integer", nullable=false)
   */
  private $serverId;

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

