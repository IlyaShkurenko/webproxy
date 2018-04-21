<?php

namespace Migration\Mapping;

use Doctrine\ORM\Mapping as ORM;

/**
 * ProxiesIpv6
 *
 * @ORM\Table(name="proxies_ipv6", uniqueConstraints={@ORM\UniqueConstraint(name="block", columns={"block"})})
 * @ORM\Entity
 */
class ProxiesIpv6
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
   * @ORM\Column(name="source_id", type="integer", nullable=false)
   */
  private $sourceId;

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
  private $subnet = '48';

  /**
   * @var integer
   *
   * @ORM\Column(name="location_id", type="integer", nullable=false)
   */
  private $locationId;

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

