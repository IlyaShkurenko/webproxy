<?php

namespace WHMCS\Module\Blazing\Proxy\Seller\Events;

use Axelarge\ArrayTools\Arr;
use WHMCS\Module\Blazing\Proxy\Seller\Pricing\PricingStorage;
use WHMCS\Module\Blazing\Proxy\Seller\Product;
use WHMCS\Module\Blazing\Proxy\Seller\Seller\WhmcsWorkarounds;
use WHMCS\Module\Framework\Events\AbstractHookListener;

/** @noinspection PhpInconsistentReturnPointsInspection */
class ManualOrderPrice extends AbstractHookListener
{
    protected $name = 'OrderProductPricingOverride';

    protected function execute(array $args = null)
    {
        $productId = $args['pid'];
        $quantityFieldValue = false;

        $product = Product::findById($productId);

        if (!$product or !$product->getPricing()) {
            return;
        }

        // Get custom field value
        foreach (Arr::getNested($args, 'proddata.customfields', []) as $data) {
            // Field found
            if ($product->getCustomFieldQuantityId() == $data['id']) {
                $quantityFieldValue = $data['value'];

                break;
            }
        }

        // Attach listener on change
        if ($product->getCustomFieldQuantityId()) {
            echo $this->view('manual_order/recalculate_on_custom_field_update.js.tpl', ['id' => $product->getCustomFieldQuantityId()]);
        }

        if ($quantityFieldValue and $quantityFieldValue > 0) {
            $price = $product->getPricing()->getPriceForQuantity($quantityFieldValue);
            $total = $price * (int) $quantityFieldValue;

            $workarounds = new WhmcsWorkarounds();
            if (!empty($_REQUEST['userid'])) {
                $total = $workarounds->emulatePricingOverrideOnNewOrderHook($_REQUEST['userid'], $productId, $total, 'Monthly');
            }

            if (false !== $price) {
                return ['recurring' => $total];
            }
            else {
                return ['recurring' => -1];
            }
        }
    }
}
