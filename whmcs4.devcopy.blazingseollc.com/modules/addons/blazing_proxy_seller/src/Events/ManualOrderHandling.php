<?php

namespace WHMCS\Module\Blazing\Proxy\Seller\Events;

use Axelarge\ArrayTools\Arr;
use WHMCS\Module\Blazing\Proxy\Seller\Logger;
use WHMCS\Module\Blazing\Proxy\Seller\Product;
use WHMCS\Module\Blazing\Proxy\Seller\Seller;
use WHMCS\Module\Framework\Events\AbstractHookListener;

class ManualOrderHandling extends AbstractHookListener
{

    protected $name = 'PreShoppingCartCheckout';

    protected function execute(array $args = null)
    {
        if (empty($args[ 'products' ])) {
            return;
        }

        $userId = Arr::getOrElse($_REQUEST, 'userid', 0);
        $products = [];

        // Something went wrong, skip it
        if (!$userId) {
            return;
        }

        foreach ($args[ 'products' ] as $data) {

            $product = Product::findById($data[ 'pid' ]);
            $priceOverride = isset($data['priceoverride']) ? $data['priceoverride'] : null;
            if ('' === $priceOverride) {
                $priceOverride = null;
            }

            if (!$product->getPricing()) {
                continue;
            }

            if (empty($data[ 'customfields' ][ $product->getCustomFieldQuantityId() ])) {
                die('No valid quantity found for product ' . $product->getName());
            }

            $quantity = $data[ 'customfields' ][ $product->getCustomFieldQuantityId() ];
            $price = $product->getPricing()->getPriceForQuantity($quantity);

            if (!$price) {
                die('No valid price found for product ' . $product->getName());
            }

            $products[] = array_merge(
                ['product' => $product, 'quantity' => $quantity],
                !is_null($priceOverride) ? ['price' => $priceOverride] : []
            );
        }

        // No proxies submitted at all
        if (!$products) {
            return;
        }

        // Restrictions
        if (count($products) != count($args[ 'products' ])) {
            die('It is not allowed to mess proxy products with other products');
        }

        if (count($products) > 1) {
            die('Only one proxy per order is allowed to submit at the time');
        }

        $seller = new Seller();
        $data = $products[ 0 ];

        Logger::bindUserId($userId);
        Logger::info('### Manual service create');

        $service = $seller->createPackage(
            $userId,
            $data[ 'product' ]->getId(),
            $data[ 'quantity' ],
            false,
            !empty($args['promo']) ? $args['promo'] : false,
            isset($data[ 'price' ]) ? $data[ 'price' ] : false
        );
        header('Location: orders.php?' . http_build_query([
                'action' => 'view',
                'id'     => $service->getInitialOrderId()
            ]), true, 302
        );
    }
}