<?php

namespace WHMCS\Module\Blazing\Proxy\Seller;

use WHMCS\Module\Blazing\Proxy\Seller\Pricing\PricingStorage;
use WHMCS\Module\Blazing\Proxy\Seller\Pricing\PricingTable;
use WHMCS\Module\Blazing\Proxy\Seller\Product\ProductNormalizer;
use WHMCS\Module\Framework\Api\APIFactory;

class Product
{

    // Data properties
    protected $id;
    protected $name;
    protected $type;
    protected $moduleName;

    // Lazy properties
    protected $pricing;
    protected $normalizer;

    // Internal data
    protected $pricingStorage;

    public static function findById($id)
    {
        $data = APIFactory::orders()->getProduct($id);

        if (!$data->isLoaded()) {
            return false;
        }

        $instance = new static();
        $instance->setId($id);
        $instance->setName($data[ 'name' ]);
        $instance->setType($data[ 'type' ]);
        $instance->setModuleName($data[ 'module' ]);

        return $instance;
    }

    // Accessors

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
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get name
     *
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set name
     *
     * @param mixed $name
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get type
     *
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set type
     *
     * @param mixed $type
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get module
     *
     * @return mixed
     */
    public function getModuleName()
    {
        return $this->moduleName;
    }

    /**
     * Set module
     *
     * @param mixed $module
     * @return $this
     */
    public function setModuleName($module)
    {
        $this->moduleName = $module;

        return $this;
    }

    // Lazy accessors

    /**
     * Get pricing
     *
     * @return PricingTable|false
     */
    public function getPricing()
    {
        if (!$this->pricing) {
            $this->pricing = $this->getPricingStorage()->getPricingForProduct($this->id);
        }

        return $this->pricing;
    }

    /**
     * Get customFieldQuantityId
     *
     * @return int|false
     */
    public function getCustomFieldQuantityId()
    {
        if (!$this->getNormalizer()) {
            return false;
        }

        return $this->getNormalizer()->isCustomFieldQuantityExists();
    }

    public function getNormalizer()
    {
        if (!$this->getPricing()) {
            return false;
        }

        if (!$this->normalizer) {
            $this->normalizer = new ProductNormalizer($this);
        }

        return $this->normalizer;
    }

    // Internal methods

    /**
     * Get pricingStorage
     *
     * @return PricingStorage
     */
    protected function getPricingStorage()
    {
        if (empty($this->pricingStorage)) {
            $this->pricingStorage = new PricingStorage();
        }

        return $this->pricingStorage;
    }
}
