<?php

namespace Migration\Mapping;

use Doctrine\ORM\Mapping as ORM;

/**
 * UserMtaIps
 *
 * @ORM\Table(name="user_mta_ips")
 * @ORM\Entity
 */
class UserMtaIps
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
   * @var string
   *
   * @ORM\Column(name="ip", type="string", length=45, nullable=false)
   */
  private $ip;

  /**
   * @var \DateTime
   *
   * @ORM\Column(name="date_created", type="datetime", nullable=false)
   */
  private $dateCreated;

  /**
   * @var \DateTime
   *
   * @ORM\Column(name="date_checked", type="datetime", nullable=false)
   */
  private $dateChecked;


}

