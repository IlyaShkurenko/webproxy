<?php

namespace Proxy\Assignment\Port;

use Proxy\Assignment\PackageDict;

class AbstractPackage implements AbstractPackageInterface
{
    const INTERNET_PROTOCOL = '4';

    // Category dict
    const DICT_OLD_CATEGORY = [];

    protected $id;
    protected $userId = 0;
    protected $ipV = self::INTERNET_PROTOCOL;
    protected $type = '';
    protected $country = '';
    protected $category = '';
    protected $ext = '';
    protected $status = PackageDict::STATUS_ACTIVE;
    /** @var CommonPackageContext */
    protected $context;

    public function toArray()
    {
        return get_object_vars($this);
    }

    /**
     * Get id
     *
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set id
     *
     * @param mixed $id
     * @return static
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

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
     * @return static
     * @throws \ErrorException
     */
    public function setUserId($userId)
    {
        if (!$userId) {
            throw new \ErrorException("User id is empty!");
        }
        $this->userId = $userId;

        return $this;
    }

    /**
     * Get ipV
     *
     * @return string
     */
    public function getIpV()
    {
        return $this->ipV;
    }

    /**
     * Get type
     *
     * @return string
     * @throws \ErrorException
     */
    public function getType()
    {
        if (!$this->type) {
            throw new \ErrorException("type is empty!");
        }

        return $this->type;
    }

    /**
     * Set type
     *
     * @param string $type
     * @return static
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get country
     *
     * @return mixed
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * Set country
     *
     * @param mixed $country
     * @return static
     * @throws \ErrorException
     */
    public function setCountry($country)
    {
        if (!$country) {
            throw new \ErrorException("Country is empty!");
        }
        $this->country = $country;

        return $this;
    }

    /**
     * Get category
     *
     * @return mixed
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * Set category
     *
     * @param mixed $category
     * @return static
     * @throws \ErrorException
     */
    public function setCategory($category)
    {
        if (!$category) {
            throw new \ErrorException("Category is empty!");
        }

        $this->category = static::toNewCategory($category);

        return $this;
    }

    /**
     * Get ext
     *
     * @return string
     * @throws \ErrorException
     */
    public function getExt()
    {
        if (!$this->ext) {
            throw new \ErrorException("ext is empty!");
        }

        return $this->ext;
    }

    /**
     * Get ext
     *
     * @return array
     */
    public function getParsedExt()
    {
        return $this->ext ? json_decode($this->ext, true) : [];
    }

    /**
     * Set ext
     *
     * @param string $ext
     * @return static
     */
    public function setExt($ext)
    {
        $this->ext = $ext;

        return $this;
    }

    /**
     * Set ext that was previously parsed as array
     *
     * @param array $ext
     * @return $this
     */
    public function setParsedExt(array $ext)
    {
        if ($ext) {
            $this->ext = json_encode($ext);
        }

        return $this;
    }

    /**
     * Get status
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set status
     *
     * @param string $status
     * @return static
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get context
     *
     * @return CommonPackageContext
     */
    public function getContext()
    {
        if (!$this->context) {
            $this->context = new CommonPackageContext();
        }

        return $this->context;
    }

    /**
     * Set context
     *
     * @param CommonPackageContext $context
     * @return $this
     */
    public function setContext(CommonPackageContext $context)
    {
        $this->context = $context;

        return $this;
    }

    // --- Converters

    public static function toOldCategory($category)
    {
        $dict = static::DICT_OLD_CATEGORY;

        if (!empty($dict[ $category ])) {
            return $dict[ $category ];
        }

        // The same
        return $category;
    }

    public static function toNewCategory($category)
    {
        $dict = array_flip(static::DICT_OLD_CATEGORY);
        if (!empty($dict[ $category ])) {
            return $dict[ $category ];
        }

        // The same
        return $category;
    }
}
