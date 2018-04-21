<?php

namespace Migration\Mapping;

use Doctrine\ORM\Mapping as ORM;

/**
 * UserPortsIpv6
 *
 * @ORM\Table(name="user_ports_ipv6")
 * @ORM\Entity
 */
class UserPortsIpv6
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
   * @var integer
   *
   * @ORM\Column(name="package_id", type="integer", nullable=false)
   */
  private $packageId;

  /**
   * @var integer
   *
   * @ORM\Column(name="block_id", type="integer", nullable=true)
   */
  private $blockId;

  /**
   * @var integer
   *
   * @ORM\Column(name="user_id", type="integer", nullable=false)
   */
  private $userId;

  /**
   * @var \DateTime
   *
   * @ORM\Column(name="created_at", type="datetime", nullable=false)
   */
  private $createdAt;

  /**
   * @var \DateTime
   *
   * @ORM\Column(name="assigned_at", type="datetime", nullable=true)
   */
  private $assignedAt;

  /**
   * @var \DateTime
   *
   * @ORM\Column(name="updated_at", type="datetime", nullable=false)
   */
  private $updatedAt;


}

