<?php

namespace Proxy\Model;

class ExportedPort
{
    // User
    protected $userId;
    protected $login;
    protected $apiKey;

    // Port
    protected $portId;

    // Package
    protected $packageId;
    protected $ext;

    // IP
    protected $block;
    protected $subnet;
    protected $serverId;
    protected $serverIp;

    public function isFulfilled()
    {
        return $this->userId and $this->login and $this->apiKey
            and $this->portId
            and $this->packageId and $this->ext
            and $this->block and $this->subnet and $this->serverId and $this->serverIp;
    }

    // Accessors

    /**
     * Get userId
     *
     * @return mixed
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Set userId
     *
     * @param mixed $userId
     * @return $this
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * Get login
     *
     * @return mixed
     */
    public function getLogin()
    {
        return $this->login;
    }

    /**
     * Set login
     *
     * @param mixed $login
     * @return $this
     */
    public function setLogin($login)
    {
        $this->login = $login;

        return $this;
    }

    /**
     * Get apiKey
     *
     * @return mixed
     */
    public function getApiKey()
    {
        return $this->apiKey;
    }

    /**
     * Set apiKey
     *
     * @param mixed $apiKey
     * @return $this
     */
    public function setApiKey($apiKey)
    {
        $this->apiKey = $apiKey;

        return $this;
    }

    /**
     * Get portId
     *
     * @return mixed
     */
    public function getPortId()
    {
        return $this->portId;
    }

    /**
     * Set portId
     *
     * @param mixed $portId
     * @return $this
     */
    public function setPortId($portId)
    {
        $this->portId = $portId;

        return $this;
    }

    /**
     * Get packageId
     *
     * @return mixed
     */
    public function getPackageId()
    {
        return $this->packageId;
    }

    /**
     * Set packageId
     *
     * @param mixed $packageId
     * @return $this
     */
    public function setPackageId($packageId)
    {
        $this->packageId = $packageId;

        return $this;
    }

    /**
     * Get ext
     *
     * @return mixed
     */
    public function getExt()
    {
        return $this->ext;
    }

    /**
     * Set ext
     *
     * @param mixed $ext
     * @return $this
     */
    public function setExt($ext)
    {
        $this->ext = $ext;

        return $this;
    }

    /**
     * Get block
     *
     * @return mixed
     */
    public function getBlock()
    {
        return $this->block;
    }

    /**
     * Set block
     *
     * @param mixed $block
     * @return $this
     */
    public function setBlock($block)
    {
        $this->block = $block;

        return $this;
    }

    /**
     * Get subnet
     *
     * @return mixed
     */
    public function getSubnet()
    {
        return $this->subnet;
    }

    /**
     * Set subnet
     *
     * @param mixed $subnet
     * @return $this
     */
    public function setSubnet($subnet)
    {
        $this->subnet = $subnet;

        return $this;
    }

    /**
     * Get serverId
     *
     * @return mixed
     */
    public function getServerId()
    {
        return $this->serverId;
    }

    /**
     * Set serverId
     *
     * @param mixed $serverId
     * @return $this
     */
    public function setServerId($serverId)
    {
        $this->serverId = $serverId;

        return $this;
    }

    /**
     * Get serverIp
     *
     * @return mixed
     */
    public function getServerIp()
    {
        return $this->serverIp;
    }

    /**
     * Set serverIp
     *
     * @param mixed $serverIp
     * @return $this
     */
    public function setServerIp($serverIp)
    {
        $this->serverIp = $serverIp;

        return $this;
    }
}
