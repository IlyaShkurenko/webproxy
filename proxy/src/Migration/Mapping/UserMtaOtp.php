<?php

namespace Migration\Mapping;

use Doctrine\ORM\Mapping as ORM;

/**
 * UserMtaOtp
 *
 * @ORM\Table(name="user_mta_otp")
 * @ORM\Entity
 */
class UserMtaOtp
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
   * @ORM\Column(name="type", type="string", length=32, nullable=false)
   */
  private $type;

  /**
   * @var integer
   *
   * @ORM\Column(name="user_id", type="integer", nullable=true)
   */
  private $userId;

  /**
   * @var string
   *
   * @ORM\Column(name="user_key", type="string", length=64, nullable=true)
   */
  private $userKey;

  /**
   * @var integer
   *
   * @ORM\Column(name="reseller_id", type="integer", nullable=false)
   */
  private $resellerId;

  /**
   * @var string
   *
   * @ORM\Column(name="code", type="string", length=32, nullable=false)
   */
  private $code;

  /**
   * @var integer
   *
   * @ORM\Column(name="attempts", type="smallint", nullable=false)
   */
  private $attempts;

  /**
   * @var \DateTime
   *
   * @ORM\Column(name="expiration", type="datetime", nullable=false)
   */
  private $expiration;


}

