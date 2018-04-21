<?php

namespace WHMCS\Module\Blazing\Proxy\Seller\Pricing;

use Axelarge\ArrayTools\Arr;

class PricingTier
{

    /**
     * @var int
     */
    protected $fromQuantity;

    /**
     * @var int
     */
    protected $toQuantity;

    /**
     * @var float
     */
    protected $price;

    /** @var array */
    protected $meta = [];

    /**
     * PricingTier constructor.
     *
     * @param int $fromQuantity
     * @param float $price
     * @param array $meta
     */
    public function __construct($fromQuantity = null, $price = null, array $meta = null)
    {
        if ($fromQuantity) {
            $this->setFromQuantity($fromQuantity);
        }
        if ($price) {
            $this->setPrice($price);
        }
        if ($meta) {
            foreach ($meta as $key => $value) {
                $this->setMeta($key, $value);
            }
        }
    }


    /**
     * Get fromQuantity
     *
     * @return int
     */
    public function getFromQuantity()
    {
        return $this->fromQuantity;
    }

    /**
     * Set fromQuantity
     *
     * @param int $fromQuantity
     * @return $this
     */
    public function setFromQuantity($fromQuantity)
    {
        $this->fromQuantity = (int) $fromQuantity;

        return $this;
    }

    /**
     * Get toQuantity
     *
     * @return int
     */
    public function getToQuantity()
    {
        return $this->toQuantity;
    }

    /**
     * Set toQuantity
     *
     * @param int $toQuantity
     * @return $this
     */
    public function setToQuantity($toQuantity)
    {
        $this->toQuantity = (int) $toQuantity;

        return $this;
    }

    /**
     * Get price
     *
     * @return float
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * Set price
     *
     * @param float $price
     * @return $this
     */
    public function setPrice($price)
    {
        $this->price = (float) $price;

        return $this;
    }

    public function getMeta($key = null)
    {
        return !$key ? $this->meta : Arr::getNested($this->meta, $key);
    }

    public function setMeta($key, $value)
    {
        $this->meta[$key] = $value;

        return $this;
    }
}
