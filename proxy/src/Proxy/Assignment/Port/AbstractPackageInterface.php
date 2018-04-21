<?php
/**
 * User: User
 * Date: 13-Oct-17
 * Time: 15:39
 */

namespace Proxy\Assignment\Port;

interface AbstractPackageInterface
{

    public function toArray();

    /**
     * Get id
     *
     * @return mixed
     */
    public function getId();

    /**
     * Set id
     *
     * @param mixed $id
     * @return static
     */
    public function setId($id);

    /**
     * Get userId
     *
     * @return mixed
     */
    public function getUserId();

    /**
     * Set userId
     *
     * @param mixed $userId
     * @return static
     * @throws \ErrorException
     */
    public function setUserId($userId);

    /**
     * Get ipV
     *
     * @return string
     */
    public function getIpV();

    /**
     * Get type
     *
     * @return string
     */
    public function getType();

    /**
     * Set type
     *
     * @param string $type
     * @return static
     */
    public function setType($type);

    /**
     * Get country
     *
     * @return mixed
     */
    public function getCountry();

    /**
     * Set country
     *
     * @param mixed $country
     * @return static
     * @throws \ErrorException
     */
    public function setCountry($country);

    /**
     * Get category
     *
     * @return mixed
     */
    public function getCategory();

    /**
     * Set category
     *
     * @param mixed $category
     * @return static
     * @throws \ErrorException
     */
    public function setCategory($category);

    /**
     * Get ext
     *
     * @return string
     */
    public function getExt();

    /**
     * Get ext
     *
     * @return array
     */
    public function getParsedExt();

    /**
     * Set ext
     *
     * @param array $ext
     * @return static
     */
    public function setExt($ext);

    /**
     * Set ext that was previously parsed as array
     *
     * @param array $ext
     * @return $this
     */
    public function setParsedExt(array $ext);

    /**
     * Get status
     *
     * @return string
     */
    public function getStatus();

    /**
     * Set status
     *
     * @param string $status
     * @return static
     */
    public function setStatus($status);
}