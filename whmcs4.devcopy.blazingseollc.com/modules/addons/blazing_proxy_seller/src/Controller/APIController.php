<?php

namespace WHMCS\Module\Blazing\Proxy\Seller\Controller;

use Axelarge\ArrayTools\Arr;
use ErrorException;
use Symfony\Component\HttpFoundation\Request;
use WHMCS\Module\Blazing\Proxy\Seller\Exception\UserFriendlyException;
use WHMCS\Module\Blazing\Proxy\Seller\Logger;
use WHMCS\Module\Blazing\Proxy\Seller\Pricing\PricingStorage;
use WHMCS\Module\Blazing\Proxy\Seller\Pricing\PricingHelper;
use WHMCS\Module\Blazing\Proxy\Seller\Seller;
use WHMCS\Module\Blazing\Proxy\Seller\UserService;
use WHMCS\Module\Framework\Addon;
use WHMCS\Module\Framework\Helper;

class APIController extends AbstractSelfDrivenController
{

    public function updatePackageAction($userId, $productId, $quantity, $url = [], Request $request,
        $affiliateId = null, $promocode = null)
    {
        $seller = new Seller();
        Logger::bindUserId($userId);
        Logger::shareIndex('affiliateId', $affiliateId);
        Logger::shareIndex('promocode', $promocode);
        Logger::shareIndex('productId', $productId);

        $service = UserService::findByUserProduct($userId, $productId);
        if($service !== false) {
            // set status unpaid for the last invoice
            // it will be set to Cancelled again
            // but for a case when something will go wrong
            // we will leave it
            $service->removeCancelRequestAndReactivateInvoice();
        }
        $invoiceId = false;

        // Process request
        if (!$service or in_array($service->getStatus(), [UserService::STATUS_NEW, UserService::STATUS_CANCELLED])
        ) {
            $service = $seller->createPackage((int) $userId, (int) $productId, $quantity, (int) $affiliateId, $promocode);
            $invoiceId = $service->getInitialInvoiceId();
        }
        elseif (UserService::STATUS_SUSPENDED == $service->getStatus()) {
            // Redirect customer to unpaid invoice

            // Look for the last unpaid invoice for
            $product = Helper::apiResponse('getClientsProducts', ['serviceId' => $service->getServiceId()],
                'products.product.0')['products']['product'][0];
            $invoices = Helper::api('getInvoices', ['userId' => $service->getUserId(), 'status' => 'Overdue']);
            $invoices = Arr::getNested($invoices, 'invoices.invoice', []);
            $invoices = array_values(array_filter($invoices, function($invoice) use($service, $product) {
                return 'Unpaid' == $invoice['status'] and $product['nextduedate'] == $invoice['duedate'];
            }));

            Logger::info(sprintf('### Customer tries to buy a suspended package, found %s invoice(s) to redirect him to',
                count($invoices)), [$invoices]);

            // Something went wrong :) Probably service was changed manually
            if (!$invoices) {
                throw new UserFriendlyException('This package is Suspended');
            }
            // Ideal variant, 1 service - 1 unpaid overdue invoice
            elseif (1 == count($invoices)) {
                $invoiceId = $invoices[0]['id'];
            }
            // Many invoice, determine which one is better
            else {
                // Latest may match
                usort($invoices, function($invoice1, $invoice2) {
                    return ($invoice1['id'] > $invoice2['id']) ? -1 : 1;
                });
                $invoiceId = $invoices[0]['id'];
                Logger::debug("Invoice $invoiceId has been chosen");
            }
        }
        else {
            if(!$service->upgradeEnabled())
                throw new UserFriendlyException('Upgrade/Downgrade on due date is restricted');

            $return = $seller->upgradePackage($service, $quantity, $promocode);
            if ($return instanceof UserService) {
                if (!$service->getUpgradeInvoiceId()) {
                    throw new UserFriendlyException('No invoice has been created. Contact please administrator');
                }
                $invoiceId = $service->getUpgradeInvoiceId();
            }
        }

        // Set/update urls
        if ($service instanceof UserService and (!empty($url[ 'redirect' ]) or !empty($url[ 'callback' ]))) {
            if (!empty($url[ 'redirect' ][ 'success' ])) {
                $service->setRedirectSuccessUrl($url[ 'redirect' ][ 'success' ]);
            }
            if (!empty($url[ 'redirect' ][ 'fail' ])) {
                $service->setRedirectFailUrl($url[ 'redirect' ][ 'fail' ]);
            }
            if (!empty($url[ 'callback' ])) {
                $service->setCallbackUrl($url[ 'callback' ]);
            }
            $service->save();

            if (UserService::STATUS_ACTIVE == $service->getStatus()) {
                $service->getCallback()->call('create');
            }
        }

        $info = [
            'productId' => $service->getProductId(),
            'serviceId' => $service->getServiceId()
        ];

        if (!empty($invoiceId)) {
            return array_merge([
                'invoiceId'  => $invoiceId,
                'invoiceUrl' => UserService\UserServiceProductDescriber::buildInvoiceUrlForRequest($invoiceId)
            ], ['info' => $info]);
        } else {
            return array_merge([
                'noInvoice' => true
            ], ['info' => $info]);
        }
    }

    public function cancelPackageAtTheEndOfTheBillingAction($userId, $serviceId, $reason = '')
    {
        $response = Helper::apiResponse('getClientsProducts', ['clientid' => $userId, 'serviceid' => $serviceId], 'result=success');
        if (!empty($response['products']['product'])) {
            return Helper::api('AddCancelRequest', [
                'serviceid' => $serviceId,
                'type' => 'End of Billing Period',
                'reason' => 'Cancellation through dashboard. Reason: ' . $reason
            ]);
        }
    }

    public function removeCancellationRequestAction($userId, $serviceId) {
        $result = 'error';
        $response = Helper::apiResponse('getClientsProducts', ['clientid' => $userId, 'serviceid' => $serviceId], 'result=success');
        if (!empty($response['products']['product'])) {
            UserService::removeCancelRequestAndReactivateInvoiceForService($serviceId);
            $result = 'success';
        }
        return [
            'result' => $result
        ];
    }

    public function cancelPackageAction($userId, $productId)
    {
        if (!$service = UserService::findByUserProduct($userId, $productId)) {
            throw new ErrorException('Service is not exists');
        }

        Logger::bindUserId($service->getUserId());

        $seller = new Seller();
        $seller->cancelPackage($service);

        return [
            'cancelled' => true
        ];
    }

    public function getPricingTiersAction($withUnpublished = false)
    {
        $storage = new PricingStorage();

        $pricing = [];

        foreach ($storage->getAllPricings() as $pricingTable) {
            $data = [
                'productId' => $pricingTable->getProductId(),
                'meta'      => Arr::except($pricingTable->getAllMeta(), ['sortOrder'])
            ];
            foreach ($pricingTable->getTiers() as $pricingTier) {
                if ($pricingTier->getMeta('unpublished') and !$withUnpublished) {
                    continue;
                }

                $data[ 'tiers' ][] = [
                    'from'  => $pricingTier->getFromQuantity(),
                    'to'    => $pricingTier->getToQuantity() ? $pricingTier->getToQuantity() : 9999999,
                    'price' => $pricingTier->getPrice(),
                ];
            }

            if (empty($data['tiers'])) {
                continue;
            }

            $pricing[] = $data;
        }

        return [
            'pricing' => $pricing
        ];
    }

    public function getUserProductsAction($userId, Request $request)
    {
        Logger::bindUserId($userId);

        $response = $response = Helper::apiResponse('getClientsProducts', ['clientId' => $userId], 'result=success');
        $productsIds = [];
        if (!empty($response['products']['product'])) {
            foreach ($response['products']['product'] as $product) {
                if (in_array($product['status'], ['Pending', 'Active', 'Suspended'])) {
                    $productsIds[] = $product['id'];
                }
            }
        }

        return [
            'userId'   => $userId,
            'products' => array_filter(array_map(function (UserService $service) use ($productsIds, $request) {
                // Wrong status
                if (!in_array($service->getStatus(), [
                    UserService::STATUS_ACTIVE,
                    UserService::STATUS_ACTIVE_UPGRADING,
                    UserService::STATUS_ACTIVE_UPGRADED,
                    UserService::STATUS_SUSPENDED
                ])
                ) {
                    return null;
                }

                // Service is cancelled
                if (!in_array($service->getServiceId(), $productsIds)) {
                    Logger::info('Package %s cancelling (service status is %s, but product is not active)');
                    $service->setStatus(UserService::STATUS_CANCELLED)->save();
                    return null;
                }

                $data = [
                    'productId' => $service->getProductId(),
                    'quantity'  => $service->getQuantity(),
                    'serviceId' => $service->getServiceId(),
                    'status'    => $service->getStatus() != UserService::STATUS_SUSPENDED ? 'active' : 'suspended',
                    'hasCancelRequest' => $service->getHasCancelRequest()
                ];

                // promo
                $hosting = $service->getServiceDescriber()->getData();
                $promotion = Helper::conn()->table('tblpromotions')
                    ->where('id', $hosting['promoid'])
                    ->first();

                if($promotion['recurring'] && $service->isPromoActive($promotion)) {
                    $data['promo'] = [
                        'promocode' => $promotion['code']
                    ];
                }

                $upgradeEnabled = true;
                if(!$service->upgradeEnabled()) {
                    $upgradeEnabled = false;
                }

                if ($service->getStatus() == UserService::STATUS_SUSPENDED) {
                    $data['statusReason'] = $service->getServiceDescriber()->getSuspendReason();

                    $upgradeEnabled = false;
                }

                if (!$upgradeEnabled && ($unpaidInvoice = $service->getServiceDescriber()->getUnpaidOverdueInvoice())) {
                    $data['unpaidInvoice'] =
                        UserService\UserServiceProductDescriber::buildInvoiceUrlForRequest($unpaidInvoice['id']);
                }

                $data['upgradeEnabled'] = $upgradeEnabled;

                return $data;
            }, UserService::findManyByUser($userId)))
        ];
    }

    public function calculateTotalAction($productId, $quantity, $userId = null, $promocode = null)
    {
        // Calculate price by pricing tiers
        $price = PricingHelper::getInstance()
                            ->getDefaultProductPriceForQuantity($productId, $quantity);
        $discount = 0;

        // Get price override
        if ($userId) {
            $countAsDiscount = .5; // if ratio is lesser count as discount
            $workarounds = new Seller\WhmcsWorkarounds();
            $priceOverriden = $workarounds->emulatePricingOverrideOnNewOrderHook($userId, $productId, $price, 'Monthly');

            // Decide if it's an discount or price
            if ($priceOverriden) {
                if ($priceOverriden < $price and ($priceOverriden / $price < $countAsDiscount)) {
                    $discount = $price - $priceOverriden;
                }
                else {
                    $price = $priceOverriden;
                }
            }
        }

        // Get discount
        if ($promocode) {
            if (!isset($_SESSION)) {
                global $_SESSION;
                if (empty($_SESSION)) {
                    $_SESSION = [];
                }
            }

            // Prepare functions
            if ($userId) {
                $_SESSION['uid'] = (int) $userId;
            }
            /** @noinspection PhpIncludeInspection */
            require_once Helper::getRootDir() . '/includes/orderfunctions.php';
            /** @noinspection PhpIncludeInspection */
            require_once Helper::getRootDir() . '/includes/adminfunctions.php';

            global $promo_data;
            $promo_data = Helper::conn()->selectOne('SELECT * FROM tblpromotions WHERE code = ?', [$promocode]);

            /** @noinspection PhpUndefinedFunctionInspection */
            $data = CalcPromoDiscount(
                $productId,
                'Monthly',
                $discount ? ($price - $discount) : $price,
                $discount ? ($price - $discount) : $price,
                $promocode
            );

            if (!empty($data['onetimediscount'])) {
                $discount = $discount ? $discount + $data[ 'onetimediscount' ] : $data[ 'onetimediscount' ];
                $promoValid = true;
            }
        }

        if ($discount > $price) {
            $discount = $price;
        }

        $promoData = ['isPromoValid' => !empty($promoValid)];
        if($promoData['isPromoValid']) {
            $promoData['promoData'] = array_merge([
                'isPromoRecurring' => $promo_data['recurring'],
                'isUpgradePromo' => $promo_data['upgrades'],
            ], $promo_data['upgrades'] ? ['upgradeConfig' => unserialize($promo_data['upgradeconfig'])] : []);
        }

        return array_merge([
            'total' => $price,
            'discount' => !empty($discount) ? $discount : 0
        ], $promoData);
    }
}
