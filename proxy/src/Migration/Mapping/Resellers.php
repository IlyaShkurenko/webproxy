<?php

namespace Migration\Mapping;

use Doctrine\ORM\Mapping as ORM;

/**
 * Resellers
 *
 * @ORM\Table(name="resellers", uniqueConstraints={@ORM\UniqueConstraint(name="api_key_UNIQUE", columns={"api_key"}), @ORM\UniqueConstraint(name="email_UNIQUE", columns={"email"})})
 * @ORM\Entity
 */
class Resellers
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
   * @ORM\Column(name="email", type="string", length=255, nullable=true)
   */
  private $email;

  /**
   * @var string
   *
   * @ORM\Column(name="api_key", type="string", length=50, nullable=true)
   */
  private $apiKey;

  /**
   * @var float
   *
   * @ORM\Column(name="credits", type="float", precision=10, scale=0, nullable=false)
   */
  private $credits = '0';


}

