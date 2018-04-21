<?php

namespace WHMCS\Module\Blazing\Proxy\Seller\Pricing;

class PricingHelper {
    protected $pricingStorage;

    protected $cache;

    protected $lastPrice;

    public static function getInstance() {
        static $instance;
        if (!$instance) {
            $instance = new static();
        }

        return $instance;
    }

    protected function __construct() {
        $this->pricingStorage = new PricingStorage();
    }

    protected function getPricingForProduct($productId) {
        if (isset($this->cache[$productId])) {
            return $this->cache[$productId];
        }

        return $this->cache[$productId] = $this->pricingStorage->getPricingForProduct($productId);
    }

    public function getDefaultProductPriceForQuantity($productId, $quantity) {
        $pricingTable = $this->getPricingForProduct($productId);
        if (!$pricingTable) {
            throw new ErrorException("No price is configured for product \"$productId\"");
        }

        $price = $pricingTable->getPriceForQuantity($quantity);
        if (false === $price) {
            throw new ErrorException("No price is configured for product \"$productId\"");
        }
        $this->lastPrice = $lastPrice;

        return $price * $quantity;
    }

    public function getLastProductPrice() {
        return $this->lastPrice;
    }
}
