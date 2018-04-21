<?php

namespace Proxy\Assignment\Port\IPv4;

use Axelarge\ArrayTools\Arr;
use Proxy\Assignment\Port\AbstractPackage;
use Proxy\Assignment\Port\PortInterface;
use Proxy\Assignment\RotationAdviser\IPv4\RotationAdviser;
use Symfony\Component\Process\Exception\RuntimeException;

class Port extends AbstractPackage implements PortInterface
{

    // User type
    const TYPE_RESELLER = 'RS';
    const TYPE_CLIENT = 'BL';
    const TYPE_INTERNAL = 'IN';

    // Country
    const COUNTRY_US = 'us';
    const COUNTRY_GERMANY = 'de';
    const COUNTRY_BRAZIL = 'br';
    const COUNTRY_INTERNATIONAL = 'intl';
    const COUNTRY_GB = 'gb';
    const COUNTRY_FRANCE = 'fr';

    // Category
    const CATEGORY_DEDICATED = 'dedicated';
    const CATEGORY_SEMI_DEDICATED = 'semi-3';
    const CATEGORY_ROTATING = 'rotating';
    const CATEGORY_SNEAKER = 'sneaker';
    const CATEGORY_SUPREME = 'supreme';
    const CATEGORY_MAPPLE = 'mapple';
    const CATEGORY_KUSHANG = 'kushang';
    const CATEGORY_GOOGLE = 'google';
    const CATEGORY_BLOCK = 'block';

    // Category dict
    const DICT_OLD_CATEGORY = [
        self::CATEGORY_DEDICATED => 'static',
        self::CATEGORY_ROTATING  => 'rotate',
        // 'semi-dedicated' => static::CATEGORY_SEMI_DEDICATED
    ];

    // Server
    const DEFAULT_SERVER_ID = 2;

    // Internet protocol
    const INTERNET_PROTOCOL = '4';

    // Types
    const PACKAGE_TYPE_SINGLE = 'single'; // ipv4 IPs per package

    // Port attributes

    protected $type = self::PACKAGE_TYPE_SINGLE;
    protected $userType = '';
    protected $regionId = 0;
    protected $sneakerLocation = false;
    protected $serverId = self::DEFAULT_SERVER_ID;
    protected $proxyId;

    // Relations
    /**
     * @var RotationAdviser
     */
    protected $rotationAdviser;

    // --- Constructors

    public static function fromArray($data)
    {
        $object = new static();
        $object
            ->setId($data[ 'id' ])
            ->setUserType($data[ 'user_type' ])
            ->setUserId($data[ 'user_id' ])
            ->setCountry($data[ 'country' ])
            ->setCategory($data[ 'category' ]);

        if (!empty($data[ 'region_id' ])) {
            $object->setRegionId($data[ 'region_id' ]);
        }
        if (!empty($data[ 'status' ])) {
            $object->setStatus($data[ 'status' ]);
        }

        if (!empty($data[ 'sneaker_location' ])) {
            $object->setSneakerLocation($data[ 'sneaker_location' ]);
        }

        if (!empty($data['package_type'])) {
            $object->setType($data['package_type']);
        }

        foreach (['proxy_id', 'proxy_ip'] as $field) {
            if (isset($data[ $field ])) {
                $object->setProxyId($data[ $field ]);
                break;
            }
        }

        return $object;
    }

    public function toArray()
    {
        return Arr::except(parent::toArray(), ['rotationAdviser']);
    }

    public static function construct(
        $userId = null,
        $userType = null,
        $country = null,
        $category = null,
        $regionId = null,
        $id = null
    )
    {
        $object = new static();

        if ($userId) {
            $object->setUserId($userId);
        }

        if ($userType) {
            $object->setUserType($userType);
        }

        if ($country) {
            $object->setCountry($country);
        }

        if ($category) {
            $object->setCategory($category);
        }

        if ($regionId) {
            $object->setRegionId($regionId);
        }

        if ($id) {
            $object->setId($id);
        }

        return $object;
    }

    // --- Property helpers

    public static function getValidCountries()
    {
        return [
            static::COUNTRY_US,
            static::COUNTRY_GERMANY,
            static::COUNTRY_BRAZIL,
            static::COUNTRY_GB,
            static::COUNTRY_FRANCE,
        ];
    }

    public static function getValidCategories()
    {
        return [
            // Reseller's
            static::CATEGORY_DEDICATED,
            static::CATEGORY_SEMI_DEDICATED,
            static::CATEGORY_ROTATING,
            static::CATEGORY_SNEAKER,
            static::CATEGORY_SUPREME,

            // Other's
            static::CATEGORY_KUSHANG,
            static::CATEGORY_MAPPLE,
            static::CATEGORY_GOOGLE,
            static::CATEGORY_BLOCK
        ];
    }

    public static function getValidCategoriesCountries()
    {
        $schemeAvailableOnlyIn = [
            static::CATEGORY_SNEAKER => [static::COUNTRY_US, static::COUNTRY_GB],
            static::CATEGORY_SUPREME => [static::COUNTRY_US],
            static::CATEGORY_BLOCK   => [static::COUNTRY_US],
        ];

        $availableCategories = static::getValidCategories();
        $availableCountries  = static::getValidCountries();

        $return = [];

        foreach ($availableCategories as $category) {
            // Usual proxies are available in every country
            if (empty($schemeAvailableOnlyIn[ $category ])) {
                foreach ($availableCountries as $country) {
                    $return[ $category ][ $country ] = $country;
                }
            } // Other proxy categories only available in defined countries
            else {
                foreach ($schemeAvailableOnlyIn[ $category ] as $country) {
                    // Double test is country really available
                    if (in_array($country, $availableCountries)) {
                        $return[$category][$country] = $country;
                    }
                }
            }
        }

        return $return;
    }

    public static function getValidCategoriesCountriesAvailable()
    {
        $schemeDisabled = [
            self::CATEGORY_SUPREME => [self::COUNTRY_US]
        ];

        $return = [];

        foreach (static::getValidCategoriesCountries() as $category => $data) {
            foreach ($data as $country) {
                // Disabled means not available
                if (isset($schemeDisabled[ $category ]) and in_array($country, $schemeDisabled[ $category ])) {
                    continue;
                }

                $return[ $category ][ $country ] = $country;
            }
        }

        return $return;
    }

    public static function isCountryValid($country)
    {
        return in_array($country, static::getValidCountries());
    }

    public static function isCategoryValid($category)
    {
        return in_array(static::toNewCategory($category), static::getValidCategories());
    }

    public static function isCategoryCountryAvailable($category, $country)
    {
        $scheme = static::getValidCategoriesCountriesAvailable();

        return !empty($scheme[ $category ][ $country ]);
    }

    // --- Accessors

    /**
     * Get userType
     *
     * @return mixed
     */
    public function getUserType()
    {
        return $this->userType;
    }

    /**
     * Set userType
     *
     * @param mixed $userType
     * @return $this
     * @throws \ErrorException
     */
    public function setUserType($userType)
    {
        if (!$userType) {
            throw new \ErrorException("User type is empty!");
        }

        $this->userType = $userType;

        return $this;
    }



    /**
     * Get regionId
     *
     * @return mixed
     */
    public function getRegionId()
    {
        return $this->regionId;
    }

    /**
     * Set regionId
     *
     * @param mixed $regionId
     * @return $this
     */
    public function setRegionId($regionId)
    {
        $this->regionId = $regionId;

        return $this;
    }

    /**
     * Get sneakerLocation
     *
     * @return mixed
     */
    public function getSneakerLocation()
    {
        // Sneaker location workaround
        if (!$this->sneakerLocation) {
            if (13 == $this->getRegionId()) {
                $this->setSneakerLocation('LA');
            } elseif (2 == $this->getRegionId()) {
                $this->setSneakerLocation('NY');
            }
        }

        return $this->sneakerLocation;
    }

    /**
     * Set sneakerLocation
     *
     * @param mixed $sneakerLocation
     * @return $this
     */
    public function setSneakerLocation($sneakerLocation)
    {
        $this->sneakerLocation = $sneakerLocation;

        return $this;
    }

    /**
     * Get serverId
     *
     * @return int
     */
    public function getServerId()
    {
        return $this->serverId;
    }

    /**
     * Set serverId
     *
     * @param int $serverId
     * @return $this
     */
    public function setServerId($serverId)
    {
        $this->serverId = $serverId;

        return $this;
    }

    /**
     * Get proxyId
     *
     * @return int
     */
    public function getProxyId()
    {
        return $this->proxyId;
    }

    /**
     * Set proxyId
     *
     * @param int $proxyId
     * @return $this
     */
    public function setProxyId($proxyId)
    {
        $this->proxyId = (int) $proxyId;

        return $this;
    }

    // Methods

    /**
     * @param RotationAdviser $rotationAdviser
     * @return $this
     */
    public function setRotationAdviser($rotationAdviser)
    {
        $this->_setRotationAdviser($rotationAdviser);

        return $this;
    }

    protected function _setRotationAdviser(RotationAdviser $rotationAdviser)
    {
        $this->rotationAdviser = $rotationAdviser;
    }

    /**
     * Don't forget to setRotationAdviser() before use
     *
     * @return bool|int false if not found, int if found
     */
    public function adviseNewProxyId()
    {
        if (!$this->rotationAdviser) {
            throw new RuntimeException('Rotation rotationAdviser is not set!');
        }

        $specialAdviser = $this->rotationAdviser->getSpecialCustomerAdviser();
        if ($specialAdviser->isAbleToHandle($this)) {
            $result = $specialAdviser->findRandomProxy($this);

            if ($result) {
                return $result;
            }
            elseif(!$specialAdviser->shouldContinueIfFail($this)) {
                return false;
            }
        }

        switch ($this->getCategory()) {
            case static::CATEGORY_DEDICATED:
                return $this->rotationAdviser->findRandomDedicatedProxy($this);
            case static::CATEGORY_SNEAKER:
                return $this->rotationAdviser->findRandomSneakerProxy($this);
            case static::CATEGORY_ROTATING:
                return $this->rotationAdviser->findRandomRotatingProxy($this);
            case static::CATEGORY_SEMI_DEDICATED:
                return $this->rotationAdviser->findRandomSemiDedicatedProxy($this);
            case static::CATEGORY_MAPPLE:
                return $this->rotationAdviser->findRandomMappleProxy($this);
            case static::CATEGORY_SUPREME:
                return $this->rotationAdviser->findRandomSupremeProxy($this);
            case static::CATEGORY_KUSHANG:
                return $this->rotationAdviser->findRandomKushangProxy($this);
            default:
                return false;
        }
    }
}