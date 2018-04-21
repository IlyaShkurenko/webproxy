<?php

namespace Reseller\Helper;

use Proxy\Assignment\Port\IPv4\Port;
use Silex\Application;

class PricingHelper
{
    protected $specialPricing = [
        // portal.proxiesinc.com (clint@clintbutler.net)
        '10' => [
            Port::COUNTRY_US => [
                Port::CATEGORY_DEDICATED  => [
                    ['min' => 1, 'max' => 1000000, 'price' => 1]
                ],
                Port::CATEGORY_SNEAKER => [
                    ['min' => 1, 'max' => 1000000, 'price' => 1]
                ],
                Port::CATEGORY_SUPREME => [
                    ['min' => 1, 'max' => 1000000, 'price' => 1.75]
                ]
            ]
        ],
        // proxies.easyatc.com (contacteasyatc@gmail.com)
        '15' => [
            Port::COUNTRY_US => [
                Port::CATEGORY_DEDICATED  => [
                    ['min' => 1, 'max' => 1000000, 'price' => 1]
                ],
                Port::CATEGORY_SNEAKER => [
                    ['min' => 1, 'max' => 1000000, 'price' => 1]
                ],
                Port::CATEGORY_SUPREME => [
                    ['min' => 1, 'max' => 1000000, 'price' => 1.75]
                ]
            ]
        ],
        // Yeezy servers (a.r.j.kabbara@gmail.com)
        '20' => [
            Port::COUNTRY_US => [
                Port::CATEGORY_SNEAKER => [
                    ['min' => 1, 'max' => 1000000, 'price' => 1.1]
                ]
            ]
        ]
    ];

    protected $dbDefaultTiers = [];

    protected $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function getAllResellerPricing($resellerId = null)
    {
        if (!$this->dbDefaultTiers) {
            $this->dbDefaultTiers = $this->app[ 'dbs' ][ 'proxy' ]->fetchAll(
                'SELECT country, category, min, max, price FROM reseller_pricing ORDER BY min'
            );
        }

        $tiers = [];

        foreach ($this->dbDefaultTiers as $tier) {
            $tier[ 'category' ] = Port::toNewCategory($tier[ 'category' ]);

            // Apply special pricing
            if ($resellerId and !empty($this->specialPricing[ $resellerId ][ $tier[ 'country' ] ][ $tier[ 'category' ] ])) {
                $specialTiers = $this->specialPricing[ $resellerId ][ $tier[ 'country' ] ][ $tier[ 'category' ] ];

                foreach ($specialTiers as $specialTier) {
                    if ($tier[ 'min' ] >= $specialTier[ 'min' ] and $tier[ 'max' ] <= $specialTier[ 'max' ]) {
                        $tier[ 'price' ] = $specialTier[ 'price' ];
                    }
                }
            }

            // Change hierarchy as it in special pricing (the same format)
            $tiers[ $tier[ 'category' ] ][ $tier[ 'country' ] ][] = [
                'min'   => (int) $tier[ 'min' ],
                'max'   => (int) $tier[ 'max' ],
                'price' => (float) $tier[ 'price' ],
            ];
        }

        return $tiers;
    }

    public function getResellerPricing($country, $category, $count, $resellerId = null)
    {
        $tiers = $this->getAllResellerPricing($resellerId);
        // To keep it consistent
        $category = Port::toNewCategory($category);

        // Exceptional case
        if (empty($tiers[ $category ][ $country ])) {
            return 0;
        }

        // Usual way
        foreach ($tiers[ $category ][ $country ] as $tier) {
            if ($count >= $tier['min'] and $count <= $tier['max']) {
                return $tier['price'];
            }
        }

        // Last resort: return price for maximal tier (should be the last one)
        return !empty($tier['price']) ? $tier['price'] : 0;
    }
}