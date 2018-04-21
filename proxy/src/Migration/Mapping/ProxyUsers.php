<?php

namespace Migration\Mapping;

use Doctrine\ORM\Mapping as ORM;

/**
 * ProxyUsers
 *
 * @ORM\Table(name="proxy_users", uniqueConstraints={@ORM\UniqueConstraint(name="email", columns={"email", "reseller_id"}), @ORM\UniqueConstraint(name="whmcs_id", columns={"whmcs_id", "reseller_id"}), @ORM\UniqueConstraint(name="amember_id", columns={"amember_id", "reseller_id"})}, indexes={@ORM\Index(name="location", columns={"preferred_location"}), @ORM\Index(name="format", columns={"id", "preferred_format"})})
 * @ORM\Entity
 */
class ProxyUsers
{
  /**
   * @var integer
   *
   * @ORM\Column(name="id", type="integer", nullable=false)
   * @ORM\Id
   * @ORM\GeneratedValue(strategy="NONE")
   */
  private $id;

  /**
   * @var boolean
   *
   * @ORM\Column(name="admin", type="boolean", nullable=false)
   * @ORM\Id
   * @ORM\GeneratedValue(strategy="NONE")
   */
  private $admin = '0';

  /**
   * @var string
   *
   * @ORM\Column(name="email", type="string", length=255, nullable=true)
   */
  private $email;

  /**
   * @var string
   *
   * @ORM\Column(name="login", type="string", length=128, nullable=true)
   */
  private $login;

  /**
   * @var integer
   *
   * @ORM\Column(name="whmcs_id", type="integer", nullable=true)
   */
  private $whmcsId;

  /**
   * @var integer
   *
   * @ORM\Column(name="amember_id", type="integer", nullable=true)
   */
  private $amemberId;

  /**
   * @var \DateTime
   *
   * @ORM\Column(name="date_created", type="datetime", nullable=true)
   */
  private $dateCreated;

  /**
   * @var integer
   *
   * @ORM\Column(name="active", type="integer", nullable=false)
   */
  private $active = '1';

  /**
   * @var integer
   *
   * @ORM\Column(name="confirmed", type="integer", nullable=false)
   */
  private $confirmed = '1';

  /**
   * @var boolean
   *
   * @ORM\Column(name="rotate_30", type="boolean", nullable=true)
   */
  private $rotate30 = '0';

  /**
   * @var boolean
   *
   * @ORM\Column(name="rotate_ever", type="boolean", nullable=true)
   */
  private $rotateEver = '1';

  /**
   * @var \DateTime
   *
   * @ORM\Column(name="rotate_limit", type="datetime", nullable=true)
   */
  private $rotateLimit;

  /**
   * @var string
   *
   * @ORM\Column(name="rotation_type", type="string", length=5, nullable=false)
   */
  private $rotationType = 'HTTP';

  /**
   * @var string
   *
   * @ORM\Column(name="preferred_location", type="string", length=50, nullable=true)
   */
  private $preferredLocation = '0';

  /**
   * @var string
   *
   * @ORM\Column(name="preferred_format", type="string", length=2, nullable=false)
   */
  private $preferredFormat = 'IP';

  /**
   * @var boolean
   *
   * @ORM\Column(name="preferred_ip_auth_type", type="boolean", nullable=false)
   */
  private $preferredIpAuthType = '0';

  /**
   * @var \DateTime
   *
   * @ORM\Column(name="preferred_format_update", type="datetime", nullable=true)
   */
  private $preferredFormatUpdate;

  /**
   * @var string
   *
   * @ORM\Column(name="api_key", type="string", length=50, nullable=true)
   */
  private $apiKey;

  /**
   * @var string
   *
   * @ORM\Column(name="sneaker_location", type="string", length=2, nullable=true)
   */
  private $sneakerLocation;

  /**
   * @var integer
   *
   * @ORM\Column(name="reseller_id", type="integer", nullable=false)
   */
  private $resellerId = '1';


}

