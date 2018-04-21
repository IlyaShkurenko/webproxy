<?php

namespace WHMCS\Module\Blazing\Proxy\Seller\Pricing;

use Axelarge\ArrayTools\Arr;
use WHMCS\Module\Framework\Helper;

class PricingStorage
{

    protected $pricings = [];
    protected $pricingsLoaded = false;

    /**
     * @return PricingTable[]
     */
    public function getAllPricings()
    {
        return array_values($this->loadPricings());
    }

    /**
     * @param $productId
     * @return bool|PricingTable
     */
    public function getPricingForProduct($productId)
    {
        $this->loadPricings();

        return !empty($this->pricings[ $productId ]) ? $this->pricings[ $productId ] : false;
    }

    public function savePricing(PricingTable $pricingTable)
    {
        if (!$pricingTable->getProductId()) {
            throw new \ErrorException('Product id is not defined!');
        }

        // Remove previous pricing tiers
        Helper::conn()->delete('DELETE FROM mod_blazing_proxy_seller_pricing WHERE product_id = ?',
            [$pricingTable->getProductId()]);

        foreach ($pricingTable->getTiers() as $tier) {
            try {
                Helper::conn()->insert(
                    'INSERT INTO mod_blazing_proxy_seller_pricing 
                  (product_id, quantity_from, price, ip_v, `type`, country, category, ext, sort_order, `data`) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                    [
                        $pricingTable->getProductId(),
                        $tier->getFromQuantity(),
                        $tier->getPrice(),
                        $pricingTable->getMeta('ipVersion'),
                        $pricingTable->getMeta('type'),
                        $pricingTable->getMeta('country'),
                        $pricingTable->getMeta('category'),
                        $pricingTable->getMeta('ext') ? json_encode($pricingTable->getMeta('ext')) : null,
                        $pricingTable->getMeta('sortOrder'),
                        json_encode($tier->getMeta())
                    ]
                );
            }
            catch (\Exception $e) {
                die($e->getMessage());
            }
        }

        $this->pricings[ $pricingTable->getProductId() ] = $pricingTable;
    }

    protected function loadPricings()
    {
        if (!$this->pricingsLoaded) {
            /** @var PricingTable[] $pricings */
            $pricings = [];

            foreach (Helper::conn()->select('SELECT * FROM mod_blazing_proxy_seller_pricing ORDER BY sort_order') as $row) {
                // Initialize table
                if (empty($pricings[ $row[ 'product_id' ] ])) {
                    $pricings[ $row[ 'product_id' ] ] = new PricingTable();
                    $pricings[ $row[ 'product_id' ] ]->setProductId($row[ 'product_id' ]);

                    $pricings[ $row[ 'product_id' ] ]
                        ->setMeta('ipVersion', $row[ 'ip_v' ])
                        ->setMeta('type', $row[ 'type' ])
                        ->setMeta('country', $row[ 'country' ])
                        ->setMeta('category', $row[ 'category' ])
                        ->setMeta('ext', $row[ 'ext' ] ? json_decode($row[ 'ext' ], true) : null)
                        ->setMeta('sortOrder', $row[ 'sort_order' ]);
                }

                // Add tier to table
                $meta = [];
                if (!empty($row[ 'data' ])) {
                    try {
                        $meta = json_decode($row[ 'data' ], true);
                    }
                    catch (\Exception $e) {}
                }
                $pricings[ $row[ 'product_id' ] ]->addTier($row[ 'quantity_from' ], $row[ 'price' ], $meta);
            }

            $this->pricings = $pricings;
            $this->pricingsLoaded = true;
        }

        return $this->pricings;
    }
}
