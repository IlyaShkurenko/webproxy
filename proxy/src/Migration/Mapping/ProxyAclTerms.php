<?php

namespace Migration\Mapping;

use Doctrine\ORM\Mapping as ORM;

/**
 * ProxyAclTerms
 *
 * @ORM\Table(name="proxy_acl_terms")
 * @ORM\Entity
 */
class ProxyAclTerms
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
   * @var \DateTime
   *
   * @ORM\Column(name="from_datetime", type="datetime", nullable=false)
   */
  private $fromDatetime;

  /**
   * @var \DateTime
   *
   * @ORM\Column(name="to_datetime", type="datetime", nullable=false)
   */
  private $toDatetime;

  /**
   * @var string
   *
   * @ORM\Column(name="timezone", type="string", length=16, nullable=false)
   */
  private $timezone = 'CST';

  /**
   * @var boolean
   *
   * @ORM\Column(name="active", type="boolean", nullable=false)
   */
  private $active = '1';


}

