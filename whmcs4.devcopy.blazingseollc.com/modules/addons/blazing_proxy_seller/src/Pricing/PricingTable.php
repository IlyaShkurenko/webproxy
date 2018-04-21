<?php

namespace WHMCS\Module\Blazing\Proxy\Seller\Pricing;

use Axelarge\ArrayTools\Arr;

class PricingTable
{
    protected $productId;
    /**
     * @var PricingTier[]
     */
    protected $tiers = [];
    protected $ordered = false;
    protected $meta = [];

    /**
     * Get productId
     *
     * @return int
     */
    public function getProductId()
    {
        return $this->productId;
    }

    /**
     * Set productId
     *
     * @param mixed $productId
     * @return $this
     */
    public function setProductId($productId)
    {
        $this->productId = (int) $productId;

        return $this;
    }

    public function addTier($fromQuantity, $price, array $meta = [])
    {
        $this->ordered = false;
        $this->tiers[] = new PricingTier($fromQuantity, $price, $meta);

        return $this;
    }

    public function getTiers()
    {
        $this->reorderTiers();

        return $this->tiers;
    }

    /**
     * @param $quantity
     * @return false|float
     */
    public function getPriceForQuantity($quantity)
    {
        $this->reorderTiers();

        // Check minimal quantity
        if (!empty($this->tiers[0]) and $this->tiers[0]->getFromQuantity() > $quantity) {
            return false;
        }

        // Look over all tiers
        foreach ($this->tiers as $tier) {
            if ($tier->getFromQuantity() <= $quantity and $quantity <= $tier->getToQuantity()) {
                return $tier->getPrice();
            }
        }

        // Use latest pricing tier for big quantity
        if (isset($tier)) {
            return $tier->getPrice();
        }

        // Cannot find a right price
        return false;
    }

    public function getMeta($key = null, $default = null)
    {
        return !$key ? $this->meta : Arr::getNested($this->meta, $key, $default);
    }

    public function getAllMeta()
    {
        return $this->meta;
    }

    public function setMeta($key, $value)
    {
        $this->meta[$key] = $value;

        return $this;
    }

    public function mergeMeta(array $data)
    {
        foreach ($data as $key => $value) {
            $this->setMeta($key, $value);
        }

        return $this;
    }

    protected function reorderTiers()
    {
        if (!$this->ordered) {
            /** @var PricingTier[] $tiers */
            $tiers = Arr::sortBy($this->tiers, function(PricingTier $tier) { return $tier->getFromQuantity(); });

            // Set
            foreach ($tiers as $i => $tier) {
                // Before last element
                if ($i < count($tiers) - 1) {
                    $tier->setToQuantity($tiers[$i + 1]->getFromQuantity() - 1);
                }
                else {
                    // Means infinity
                    $tier->setToQuantity(0);
                }
            }

            $this->tiers = $tiers;
        }
    }
}
