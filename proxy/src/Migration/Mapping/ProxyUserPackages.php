<?php

namespace Migration\Mapping;

use Doctrine\ORM\Mapping as ORM;

/**
 * ProxyUserPackages
 *
 * @ORM\Table(name="proxy_user_packages", indexes={@ORM\Index(name="ip_v", columns={"ip_v"}), @ORM\Index(name="type_country_category", columns={"type", "country", "category"}), @ORM\Index(name="ipv_type_country_category", columns={"ip_v", "type", "country", "category"}), @ORM\Index(name="status", columns={"status"}), @ORM\Index(name="user_id", columns={"user_id"})})
 * @ORM\Entity
 */
class ProxyUserPackages
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
   * @ORM\Column(name="user_id", type="integer", nullable=false)
   */
  private $userId;

  /**
   * @var integer
   *
   * @ORM\Column(name="package_id", type="integer", nullable=false)
   */
  private $packageId;

  /**
   * @var integer
   *
   * @ORM\Column(name="ports", type="integer", nullable=true)
   */
  private $ports;

  /**
   * @var array
   *
   * @ORM\Column(name="ip_v", type="simple_array", nullable=false)
   */
  private $ipV = '4';

  /**
   * @var string
   *
   * @ORM\Column(name="type", type="string", length=32, nullable=false)
   */
  private $type = 'single';

  /**
   * @var array
   *
   * @ORM\Column(name="source", type="simple_array", nullable=false)
   */
  private $source = 'unknown';

  /**
   * @var string
   *
   * @ORM\Column(name="country", type="string", length=4, nullable=true)
   */
  private $country;

  /**
   * @var string
   *
   * @ORM\Column(name="category", type="string", length=50, nullable=true)
   */
  private $category;

  /**
   * @var string
   *
   * @ORM\Column(name="ext", type="string", length=128, nullable=true)
   */
  private $ext;

  /**
   * @var array
   *
   * @ORM\Column(name="status", type="simple_array", nullable=false)
   */
  private $status = 'active';

  /**
   * @var integer
   *
   * @ORM\Column(name="replacements", type="integer", nullable=false)
   */
  private $replacements = '0';

  /**
   * @var \DateTime
   *
   * @ORM\Column(name="rotation", type="datetime", nullable=true)
   */
  private $rotation;

  /**
   * @var \DateTime
   *
   * @ORM\Column(name="created", type="datetime", nullable=false)
   */
  private $created = 'CURRENT_TIMESTAMP';


}

