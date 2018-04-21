<?php

namespace Migration\Mapping;

use Doctrine\ORM\Mapping as ORM;

/**
 * ProxyAcl
 *
 * @ORM\Table(name="proxy_acl")
 * @ORM\Entity
 */
class ProxyAcl
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
   * @ORM\Column(name="list", type="string", length=32, nullable=false)
   */
  private $list;

  /**
   * @var string
   *
   * @ORM\Column(name="domain", type="string", length=128, nullable=false)
   */
  private $domain;


}

