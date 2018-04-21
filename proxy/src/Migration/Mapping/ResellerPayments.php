<?php

namespace Migration\Mapping;

use Doctrine\ORM\Mapping as ORM;

/**
 * ResellerPayments
 *
 * @ORM\Table(name="reseller_payments", uniqueConstraints={@ORM\UniqueConstraint(name="payment_id_UNIQUE", columns={"payment_id"})})
 * @ORM\Entity
 */
class ResellerPayments
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
   * @ORM\Column(name="payment_id", type="integer", nullable=false)
   */
  private $paymentId;

  /**
   * @var string
   *
   * @ORM\Column(name="amount", type="decimal", precision=10, scale=2, nullable=false)
   */
  private $amount;

  /**
   * @var integer
   *
   * @ORM\Column(name="reseller_id", type="integer", nullable=false)
   */
  private $resellerId;

  /**
   * @var \DateTime
   *
   * @ORM\Column(name="date_added", type="datetime", nullable=false)
   */
  private $dateAdded;


}

