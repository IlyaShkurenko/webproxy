<?php

namespace Migration\Mapping;

use Doctrine\ORM\Mapping as ORM;

/**
 * UserMtaExclude
 *
 * @ORM\Table(name="user_mta_exclude", uniqueConstraints={@ORM\UniqueConstraint(name="user_id", columns={"user_id"})})
 * @ORM\Entity
 */
class UserMtaExclude
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
   * @var \DateTime
   *
   * @ORM\Column(name="date_created", type="datetime", nullable=false)
   */
  private $dateCreated;


}

