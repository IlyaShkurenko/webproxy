<?php

namespace Migration\Mapping;

use Doctrine\ORM\Mapping as ORM;

/**
 * ResellerTrackedPackages
 *
 * @ORM\Table(name="reseller_tracked_packages")
 * @ORM\Entity
 */
class ResellerTrackedPackages
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
   * @ORM\Column(name="reseller_id", type="integer", nullable=false)
   */
  private $resellerId;

  /**
   * @var integer
   *
   * @ORM\Column(name="user_id", type="integer", nullable=false)
   */
  private $userId;

  /**
   * @var string
   *
   * @ORM\Column(name="country", type="string", length=32, nullable=false)
   */
  private $country;

  /**
   * @var string
   *
   * @ORM\Column(name="category", type="string", length=32, nullable=false)
   */
  private $category;

  /**
   * @var integer
   *
   * @ORM\Column(name="ports", type="smallint", nullable=false)
   */
  private $ports;

  /**
   * @var \DateTime
   *
   * @ORM\Column(name="date_added", type="datetime", nullable=false)
   */
  private $dateAdded;

  /**
   * @var \DateTime
   *
   * @ORM\Column(name="date_charged", type="datetime", nullable=false)
   */
  private $dateCharged;

  /**
   * @var \DateTime
   *
   * @ORM\Column(name="date_charged_until", type="datetime", nullable=false)
   */
  private $dateChargedUntil;


}

