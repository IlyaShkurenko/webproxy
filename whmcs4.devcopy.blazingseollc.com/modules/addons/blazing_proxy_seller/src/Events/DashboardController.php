<?php

namespace WHMCS\Module\Blazing\Proxy\Seller\Events;

use Axelarge\ArrayTools\Arr;
use Smarty;
use WHMCS\Module\Blazing\Proxy\Seller\Pricing\PricingStorage;
use WHMCS\Module\Blazing\Proxy\Seller\Pricing\PricingTable;
use WHMCS\Module\Blazing\Proxy\Seller\Product;
use WHMCS\Module\Blazing\Proxy\Seller\ProxyTypes;
use WHMCS\Module\Blazing\Proxy\Seller\Settings;
use WHMCS\Module\Framework\Api\APIFactory;

class DashboardController extends AbstractModuleListener
{

    protected $name = 'output';

    protected function execute(array $args = null)
    {
        $storage = new PricingStorage();
        $availableProxyTypes = ProxyTypes::expandAvailable();
        $availablePaymentMethods = Arr::pluck(APIFactory::system()->getPaymentMethods(), 'displayName', 'module');
        $saved = false;

        if (!empty($_POST[ 'pricing' ])) {
            $pricings = [];

            foreach ($_POST[ 'pricing' ] as $pricingData) {
                if (empty($pricingData[ 'productId' ])) {
                    continue;
                }

                $data = ProxyTypes::getItemByUid($pricingData[ 'type' ]);
                if (!$data) {
                    throw new \ErrorException('Form is corrupted');
                }

                $pricingTable = new PricingTable();
                $pricingTable
                    ->setProductId($pricingData[ 'productId' ])
                    ->mergeMeta($data)
                    ->setMeta('sortOrder', $pricingData['sortOrder']);

                foreach ($pricingData[ 'tiers' ] as $pricingTierData) {
                    if ($pricingTierData[ 'from' ] > 0 and $pricingTierData[ 'price' ] > 0) {
                        $pricingTable->addTier($pricingTierData[ 'from' ], $pricingTierData[ 'price' ], [
                            'unpublished' => !$pricingTierData[ 'published' ]
                        ]);
                    }
                }

                $pricings[] = $pricingTable;

                // At least one
                $saved = true;
            }

            // Adjust sorting orders
            usort($pricings, function(PricingTable $pricingTable1, PricingTable $pricingTable2) {
                return $pricingTable1->getMeta('sortOrder') > $pricingTable2->getMeta('sortOrder') ? 1 : -1;
            });
            foreach ($pricings as $i => $pricingTable) {
                /** @var PricingTable $pricingTable */
                $pricingTable->setMeta('sortOrder', $i + 1);
            }

            foreach ($pricings as $pricingTable) {
                /** @var PricingTable $pricingTable */
                $storage->savePricing($pricingTable);

                // Apply product requirements
                Product::findById($pricingTable->getProductId())->getNormalizer()->normalize();
            }
        }

        if (!empty($_POST['settings'])) {
            $settings = Settings::getInstance();

            foreach ($_POST['settings'] as $key => $value) {
                $settings->set($key, $value);
            }

            $settings->persist();
            $saved = true;
        }

        $products = $this->db()->select('
            SELECT p.id, p.name, g.name as group_name
            FROM `tblproducts` p
            INNER JOIN `tblproductgroups` g ON g.id = p.gid
            ORDER BY g.order, p.order
        ');
        $products = Arr::indexBy($products, 'id');
        $productsGrouped = Arr::groupBy($products, 'group_name');
        $pricing = [];

        foreach ($storage->getAllPricings() as $pricingTable) {
            foreach ($pricingTable->getTiers() as $pricingTier) {
                $pricing[ $pricingTable->getProductId() ][] = array_merge($pricingTable->getMeta(), [
                    'from'  => $pricingTier->getFromQuantity(),
                    'to'    => $pricingTier->getToQuantity(),
                    'price' => $pricingTier->getPrice(),
                    'type' => ($item = ProxyTypes::findSingleItemByCriterias(
                        $pricingTable->getMeta('ipVersion'),
                        $pricingTable->getMeta('type'),
                        $pricingTable->getMeta('country'),
                        $pricingTable->getMeta('category'),
                        $pricingTable->getMeta('ext')
                    )) ? $item['uid'] : false,
                    'published' => !$pricingTier->getMeta('unpublished')
                ]);
            }
        }

        echo $this->view('dashboard.tpl', [
            'saved'           => $saved,
            'pricing'         => $pricing,
            'products'        => $products,
            'productsGrouped' => $productsGrouped,
            'availableTypes'  => Arr::wrap($availableProxyTypes)->map(function ($item) {
                $dict = ProxyTypes::HUMAN_DICT;
                $ipVersion = $item['ipVersion'];
                $type = $item['type'];
                $country = $item['country'];
                $category = $item['category'];
                $ext = $item['ext'];

                if (!empty($type) and !empty($dict[ 'type' ][ $type ])) {
                    $type = $dict[ 'type' ][ $type ];
                }
                if (!empty($country) and !empty($dict[ 'country' ][ $country ])) {
                    $country = $dict[ 'country' ][ $country ];
                }
                if (!empty($category) and !empty($dict[ 'category' ][ $category ])) {
                    $category = $dict[ 'category' ][ $category ];
                }


                $title = '';
                if (!empty($country) and !empty($category)) {
                    $title .= strtoupper("$country-$category");
                    $title .= " (IPv$ipVersion, $type)";
                }
                else {
                    $title .= "IPv$ipVersion - $type";
                    if (!empty($ext)) {
                        $data = [];
                        foreach ($ext as $key => $value) {
                            $data[] = "$key = $value";
                        }
                        $title .= ' (' . join(', ', $data) . ')';
                    }
                }

                return [
                    'ipVersion' => $ipVersion,
                    'title' => $title,
                    'value' => $item['uid']
                ];
            })->mapToAssoc(function($data) {
                return [$data['value'], $data['title']];
            }),
            'availablePaymentMethods' => $availablePaymentMethods,
            'settings' => Settings::getInstance()->getAll()
        ]);
    }

    protected function defaultAction()
    {

    }
}