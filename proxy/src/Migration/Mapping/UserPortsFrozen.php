<?php

namespace Migration\Mapping;

use Doctrine\ORM\Mapping as ORM;

/**
 * UserPortsFrozen
 *
 * @ORM\Table(name="user_ports_frozen", indexes={@ORM\Index(name="proxy_id", columns={"proxy_id"}), @ORM\Index(name="user_id", columns={"user_id", "user_type", "proxy_id"}), @ORM\Index(name="user_id_2", columns={"user_id", "user_type"})})
 * @ORM\Entity
 */
class UserPortsFrozen
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
   * @ORM\Column(name="proxy_id", type="integer", nullable=false)
   */
  private $proxyId;

  /**
   * @var integer
   *
   * @ORM\Column(name="user_id", type="integer", nullable=false)
   */
  private $userId;

  /**
   * @var string
   *
   * @ORM\Column(name="user_type", type="string", length=3, nullable=false)
   */
  private $userType;

  /**
   * @var string
   *
   * @ORM\Column(name="port_data", type="text", length=16777215, nullable=false)
   */
  private $portData;


}

