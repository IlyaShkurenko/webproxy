<?php

namespace WHMCS\Module\Blazing\Proxy\Seller;

use Axelarge\ArrayTools\Arr;
use ErrorException;
use WHMCS\Module\Blazing\Proxy\Seller\Events\ServiceCreate;
use WHMCS\Module\Blazing\Proxy\Seller\Events\ServiceUpgrade;
use WHMCS\Module\Blazing\Proxy\Seller\Exception\UserFriendlyException;
use WHMCS\Module\Blazing\Proxy\Seller\Pricing\PricingStorage;
use WHMCS\Module\Blazing\Proxy\Seller\Seller\WhmcsWorkarounds;
use WHMCS\Module\Blazing\Proxy\Seller\Pricing\PricingHelper;
use WHMCS\Module\Framework\Api\APIFactory;
use WHMCS\Module\Framework\Helper;

class Seller
{

    protected $tempProductTitle = '%s. Quantity: %s';
    protected $defaultPaymentGateway = '';
    protected $defaultCurrency = 1;

    protected $workarounds;

    function __construct()
    {
        $this->workarounds = new WhmcsWorkarounds();
    }

    public function getWorkarounds() {
        return $this->workarounds;
    }

    public function createPackage($userId, $productId, $quantity, $affiliateId = false, $promocode = false, $priceOverride = false)
    {
        if ($service = UserService::findByUserProduct($userId, $productId)) {
            // Remove previous service
            if (UserService::STATUS_NEW == $service->getStatus()) {
                $this->workarounds->refundOrdersCredits($userId);
                $this->cancelPackage($service);
                $this->cleanupServiceUpgradeEntities($service);
                $service->drop();
            } elseif (UserService::STATUS_CANCELLED == $service->getStatus()) {
                // That's ok
            } else {
                throw new ErrorException('Service already exists! You have to update the current one');
            }
        }

        if ($quantity <= 0) {
            throw new ErrorException('Quantity should be bigger than 0');
        }

        Logger::info('### Creating new service', [
            'productId' => $productId,
            'quantity'  => $quantity,
            'affiliate' => $affiliateId,
            'promocode' => $promocode
        ]);

        $this->workarounds->refundOrdersCredits($userId);

        $totalOrig = $this->calculateTotalForQuantity($productId, $quantity);
        $total = $priceOverride === false ?
            $this->workarounds->emulatePricingOverrideOnNewOrderHook($userId, $productId, $totalOrig, 'monthly') :
            $priceOverride;
        $tempProductId = $this->createTempProductFromProductId($productId, $quantity, $totalOrig);

        // Promo workaround (selected products promo)
        if ($promocode) {
            $this->workarounds->inheritPromo($promocode, $productId, $tempProductId);
        }

        ServiceCreate::disable();
        $response = Helper::api('addOrder', array_merge([
            'clientId'      => $userId,
            'pId'           => $tempProductId,
            'priceOverride' => $total,
            'billingCycle'  => 'monthly',
            'paymentMethod' => $this->getPaymentGateway($userId)
        ],
            (int) $affiliateId ? ['affId' => (int) $affiliateId] : [],
            $promocode ? ['promocode' => $promocode] : []));
        ServiceCreate::enable();

        if (empty($response[ 'productids' ]) or empty($response[ 'orderid' ])) {
            throw new ErrorException(sprintf('Unable to create package, response - "%s"', json_encode($response)));
        }

        if($promocode) {
            $row = Helper::conn()->selectOne(
                'SELECT * FROM tblhosting WHERE id = ?',
                [(int) $response[ 'productids' ]]
            );

            $promotion = Helper::conn()->table('tblpromotions')
                ->where('code', $promocode)
                ->first();

            // fix amount if promo aplied when rec_for = 1
            if($row['amount'] == 0) {
                $orderResponse = Helper::api('GetOrders', ['id' => $response['orderid']]);
                $orderAmount = $orderResponse['orders']['order'][0]['amount'];
                Helper::apiResponse('UpdateClientProduct',
                    [
                        'serviceid' => $response['productids'],
                        'recurringamount' => $orderAmount,
                        'promoid' => $promotion['id']
                    ],
                    'result=success');
            }
        }

        $service = new UserService();
        $service
            ->setUserId($userId)
            ->setProductId($productId)
            ->setTempProductId($tempProductId)
            ->setQuantity($quantity)
            ->setStatus(UserService::STATUS_NEW)
            ->setInitialOrderId($response[ 'orderid' ])
            ->setServiceId($response[ 'productids' ])
            ->setInitialInvoiceId($response[ 'invoiceid' ]);
        $service->save();

        Logger::info('Created new service', [ 'service' => $service->toArray() ]);

        // If invoice paid already (by credit), just process the service
        $invoice = Helper::api('getInvoice', ['invoiceId' => $service->getInitialInvoiceId()]);
        if (!empty($invoice['invoiceid']) and 'Paid' == $invoice['status']) {
            Logger::info(sprintf('Invoice is paid (by credits probably), execute processing'));
            $this->doProcessNewPackage($service);

            // Otherwise it will not be called
            $this->workarounds->emulateInvoicePaid($service);

            // Let customer be redirected, so he can see his invoice already paid
        }

        if ($promocode) {
            $this->workarounds->uninheritPromo($service->getProductId(), $service->getProductId());
        }

        return $service;
    }

    public function processNewPackage(UserService $service)
    {
        if (UserService::STATUS_NEW != $service->getStatus()) {
            throw new ErrorException(sprintf('Service status "%s", what means it can\'t be processed as new!',
                $service->getStatus()));
        }

        Logger::info('### Processing new service (new ~> active)', [ 'service' => $service->toArray() ]);
        $this->doProcessNewPackage($service);

        return $service;
    }

    protected function doProcessNewPackage(UserService $service)
    {
        // Update whmcs order status by needs
        $order = $service->getInitialOrder(); //$this->getServiceInitialOrder($service);
        //$order = Helper::apiResponse('getOrders', ['id' => $service->getInitialOrderId()], 'orders.order.0');
        //$order = $order[ 'orders' ][ 'order' ][ 0 ];
        if ('Active' != $order[ 'status' ]) {
            Helper::apiResponse('acceptOrder', ['orderId' => $service->getInitialOrderId(), 'sendEmail' => false],
                'result=success');
        }

        // Handle service
        $this->doSwitchToOrigProduct($service);

        $service->setStatus(UserService::STATUS_ACTIVE)->save();
        $service->getCallback()->call('create');

        Logger::debug('Service processed', [ 'service' => $service->toArray() ]);

        // Do cleanup
        $this->cleanupServiceUpgradeEntities($service, false);
    }

    /*public function getServiceInitialOrder(UserService $service) {
        $order = Helper::apiResponse('getOrders', ['id' => $service->getInitialOrderId()], 'orders.order.0');
        return $order[ 'orders' ][ 'order' ][ 0 ];
    }*/

    public function upgradePackage(UserService $service, $quantity, $promocode = false)
    {
        if ($quantity <= 0) {
            throw new ErrorException('Quantity should be bigger than 0');
        }

        if (!in_array($service->getStatus(),
            [UserService::STATUS_ACTIVE, UserService::STATUS_ACTIVE_UPGRADING, UserService::STATUS_ACTIVE_UPGRADED])
        ) {
            throw new ErrorException(sprintf('Service status "%s", what means it can\'t be upgraded!',
                $service->getStatus()));
        }
        if ($quantity == $service->getQuantity()) {
            throw new UserFriendlyException('Cannot upgrade package to the same quantity: ' . $quantity . '!');
        }

        Logger::info(sprintf('### Upgrade/downgrade service (quantity %s ~> %s)', $service->getQuantity(), $quantity),
            [ 'service' => $service->toArray() ]);

        // Clean of previous orders, temp products, etc
        $this->workarounds->refundOrdersCredits($service->getUserId());
        $this->cleanupServiceUpgradeEntities($service, UserService::STATUS_ACTIVE_UPGRADING == $service->getStatus());

        $total = $this->calculateTotalForQuantity($service->getProductId(), $quantity);

        if ($quantity < $service->getQuantity())
            $promocode = false;

        if(($promoId = $service->resolvePromotion($promocode))) {
            $service->setPromoId($promoId);
        } else {
            $promocode = false;
        }

        // Create temp product
        $service
            ->setTempProductId($this->createTempProductFromProductId($service->getProductId(), $quantity, $total))
            ->save();
        Logger::debug(sprintf('Switching customer service to temp product (%s ~> %s)',
            $service->getProductId(), $service->getTempProductId()));

        // Promo workaround (selected products promo)
        // TODO: move to promo inheritence to UserService:resolvePromo
        if ($promocode) {
            $promoInherited = $this->workarounds->inheritPromo($promocode, $service->getProductId(), $service->getTempProductId());
        }

        Emitter::emitEvent(new EmitterEvents\BeforeUpgradeEvent($service));

        // Upgrade product
        ServiceUpgrade::disable();
        $response = Helper::apiResponse('upgradeProduct', array_merge([
            'clientId'               => $service->getUserId(),
            'serviceId'              => $service->getServiceId(),
            'type'                   => 'product',
            'newProductId'           => $service->getTempProductId(),
            'newProductBillingCycle' => 'monthly',
            'paymentMethod'          => $this->getPaymentGateway($service->getUserId())
        ],
            $promocode ? ['promocode' => $promocode] : []),
            'newproductid', 'Unable to create a package, response - %s');
        ServiceUpgrade::enable();

        $service
            ->setStatus(UserService::STATUS_ACTIVE_UPGRADING)
            ->setUpgradeOrderId($response[ 'orderid' ])
            ->setUpgradeInvoiceId($response[ 'invoiceid' ])
            ->setTempQuantity($quantity);

        Logger::info('Service updated', [ 'service' => $service->toArray() ]);
        $service->save();

        // reduce promo number of usage
        // seems to be fine if all steps above are succeeded
        if($promoInherited) {
            Logger::info('### Decrement promo number of usage', [ 'code' => $promocode ]);
            $hosting = $service->getServiceDescriber()->getData();
            Helper::conn()->table('tblpromotions')->where('id', $hosting['promoid'])->decrement('uses');
        }

        // Downgrade doesn't require invoice to be paid
        /*if ($quantity < $service->getQuantity()) {
            Logger::info(sprintf('Reduce quantity, executing upgrade'));
            $this->doProcessUpgrade($service);

            // No need to be redirected to invoice
            return true;
        }*/

        // If invoice paid already (by credit), just upgrade the service
        // We need to do it as above native upgrade package method was disabled
        if ($service->getUpgradeInvoiceId()) {
            $invoice = Helper::api('getInvoice', ['invoiceId' => $service->getUpgradeInvoiceId()]);
            if (!empty($invoice['invoiceid']) and 'Paid' == $invoice['status']) {
                Logger::info(sprintf('Invoice is paid (by credits probably), execute upgrading'));
                $this->doProcessUpgrade($service);

                // Let customer be redirected (on upgrade only), so he can see his invoice already paid
                if ($quantity > $service->getQuantity()) {
                    return true;
                }
            }
        }

        // If there no invoice it means upgrade price is lower than previous price
        // Let it be, let it be, let it be
        if (!$service->getUpgradeInvoiceId()) {
            Logger::info(sprintf('Invoice doesn\'t exist (upgrade price lower), execute upgrading'));
            $this->doProcessUpgrade($service);

            // No invoice to be redirected to
            return true;
        }

        return $service;
    }

    public function processUpgrade(UserService $service)
    {
        if (UserService::STATUS_ACTIVE_UPGRADING != $service->getStatus()) {
            throw new ErrorException(sprintf('Service status "%s", what means it can\'t be processed!',
                $service->getStatus()));
        }

        $response = Helper::apiResponse('getInvoice', ['invoiceId' => $service->getUpgradeInvoiceId()], 'invoiceid');
        if ('Paid' != $response[ 'status' ]) {
            throw new ErrorException(sprintf('Invoice status should be "Paid". Current status - "%s"',
                $response[ 'status' ]));
        }

        Logger::info('### Upgrading service', [ 'service' => $service->toArray() ]);
        $this->doProcessUpgrade($service);
    }

    protected function doProcessUpgrade(UserService $service)
    {
        // reassign promo on downgrade because if cost is 0 promo is not aplied
        if($service->getTempQuantity() < $service->getQuantity() && ($promoId = $service->getPromoId())) {
            if($promoId != 0)
                $service->applyPromo($promoId);
        }

        $this->doSwitchToOrigProduct($service);
        $this->workarounds->regenerateRecurringInvoice($service->getServiceId());

        // Mark everything as processed

        $service
            ->setStatus(UserService::STATUS_ACTIVE_UPGRADED)
            ->setQuantity($service->getTempQuantity())
            ->save();
        $service->getCallback()->call('upgrade');

        Logger::debug('Service upgraded', [ 'service' => $service->toArray() ]);

        // Orders cleanup (emails won't be sent)
        Helper::api('acceptOrder', ['orderId' => $service->getUpgradeOrderId(), 'sendEmail' => false]);
        Helper::api('acceptOrder', ['orderId' => $service->getTempOrderId(), 'sendEmail' => false]);

        // set recurringchange to zero to prevent whmcs recurring amount recalculation if upgrade invoice marked paid twice
        Helper::conn()->update("UPDATE `tblupgrades` SET `recurringchange` = 0 WHERE `tblupgrades`.`orderid` = ?", [$service->getUpgradeOrderId()]);

        $this->cleanupServiceUpgradeEntities($service, false);
    }

    protected function doSwitchToOrigProduct(UserService $service)
    {
        if (!$service->getTempProductId()) {
            throw new ErrorException('No temp product has been defined', [ 'service' => $service->toArray() ]);
        }

        /*
        // Preserve attributes before downgrade

        $user = Helper::apiResponse('getClientsDetails', ['clientId' => $service->getUserId()], 'userid');
        $creditBefore = (float) $user[ 'credit' ];

        $response = APIFactory::client()->getProducts($service->getUserId(), $service->getServiceId())
            ->validate(0)[0];
        $recurringPrice = (float) $response[ 'recurringAmount' ];
        $nextDueDate = $response[ 'nextDueDate' ];
        $paymentMethod = $response[ 'paymentMethod' ];
        $promoId = $response[ 'promoId' ];

        Logger::debug("Preserved properties", [
            'credits'        => $creditBefore,
            'recurringPrice' => $recurringPrice,
            'nextDueDate'    => $nextDueDate,
            'paymentMethod'  => $paymentMethod,
            'promoId'        => $promoId
        ]);

        // Revert product to original one
        Logger::info(sprintf('Switching customer service to orig product (%s ~> %s)',
            $service->getTempProductId(), $service->getProductId()));

        // Prevent any emails from being sent during the downgrade process
        EmailSilencer::enableSilence();

        ServiceUpgrade::disable();
        try {
            $response = Helper::apiResponse('upgradeProduct', [
                'clientId'               => $service->getUserId(),
                'serviceId'              => $service->getServiceId(),
                'type'                   => 'product',
                'newProductId'           => $service->getProductId(),
                'newproductbillingcycle' => 'monthly',
                'paymentMethod'          => 'paypal'
            ], 'newproductid');
        }
        catch (\Exception $e) {
            Logger::warn('Switch product back exception', ['exception' => $e, 'trace' => $e->getTrace()]);
            throw $e;
        }
        ServiceUpgrade::enable();
        $service
            ->setTempInvoiceId($response[ 'invoiceid' ])
            ->setTempOrderId($response[ 'orderid' ]);

        // Revert original attributes

        $user = Helper::apiResponse('getClientsDetails', ['clientId' => $service->getUserId()], 'userid');
        $creditAfter = (float) $user[ 'credit' ];

        if ($creditBefore != $creditAfter) {
            Helper::apiResponse('addCredit', [
                'clientId'    => $service->getUserId(),
                'description' => sprintf('Revert of customer credit to "%s" (Blazing Proxy Seller)', $creditBefore),
                'amount'      => $creditBefore - $creditAfter
            ], 'newbalance');
        }

        Helper::apiResponse('updateClientProduct', array_merge([
            'serviceId'       => $service->getServiceId(),
            'recurringAmount' => $recurringPrice,
            'nextDueDate'     => $nextDueDate,
            'paymentmethod'   => $paymentMethod,
            'promoId'         => $promoId
        ], $service->getProduct()->getCustomFieldQuantityId() ? [
            'customFields' => base64_encode(serialize([
                $service->getProduct()->getCustomFieldQuantityId() => (
                    UserService::STATUS_ACTIVE_UPGRADING == $service->getStatus() ?
                        $service->getTempQuantity() : $service->getQuantity()
                )
            ]))
        ] : []), 'serviceid');

        $this->workarounds->cleanupZeroInvoices($service->getServiceId());

        // Emails have been sent to the hell, good
        EmailSilencer::disableSilence();
        */

        // Revert product to original one
        Logger::info(sprintf('Switching customer service to orig product (%s ~> %s)',
            $service->getTempProductId(), $service->getProductId()));

        $this->workarounds->transposeCustomFields($service->getTempProductId(), $service->getProductId());
        APIFactory::service()->updateClientProduct($service->getServiceId(), [
            'pId' => $service->getProductId(),
            'customFields' => base64_encode(serialize([
                $service->getProduct()->getCustomFieldQuantityId() => (
                UserService::STATUS_ACTIVE_UPGRADING == $service->getStatus() ?
                    $service->getTempQuantity() : $service->getQuantity()
                )
            ]))
        ])
            ->validate('result=success');
        $this->workarounds->fixUpgradesRecords($service->getServiceId(), $service->getProductId());
    }

    public function cancelPackage(UserService $service)
    {
        $callCallback = false;

        if (UserService::STATUS_NEW == $service->getStatus()) {
            Logger::info('### Cancelling new service', [ 'service' => $service->toArray() ]);
            if ($this->isOrderExists($service->getInitialOrderId())) {
                $this->cancelOrder($service->getInitialOrderId(), false, true, true);
            } else {
                Logger::debug(sprintf('Order "%s" is not exists, remove request was not executed', $service->getInitialOrderId()));
            }
        }
        elseif (in_array($service->getStatus(),
            [UserService::STATUS_ACTIVE, UserService::STATUS_ACTIVE_UPGRADING, UserService::STATUS_ACTIVE_UPGRADED])) {
            Logger::info('### Cancelling active service', [ 'service' => $service->toArray() ]);

            // Remove temps
            $this->workarounds->refundOrdersCredits($service->getUserId());
            $this->cleanupServiceUpgradeEntities($service,
                UserService::STATUS_ACTIVE_UPGRADING == $service->getStatus());

            // Cancel product order
            $orderId = UserService::STATUS_ACTIVE == $service->getStatus() ?
                $service->getInitialOrderId() : $service->getUpgradeOrderId();
            if ($this->isOrderExists($orderId)) {
                $this->cancelOrder($orderId, true, true);
            }
            else {
                Logger::debug(sprintf('Order "%s" is not exists, remove request was not executed', $orderId));
            }

            // Cancel
            Helper::apiResponse('updateClientProduct', [
                'serviceId' => $service->getServiceId(),
                'status'    => 'Cancelled',
            ], 'serviceid');

            $callCallback = true;
        }
        elseif (UserService::STATUS_SUSPENDED == $service->getStatus()) {
            Logger::info('### Terminating/cancelling suspended service', [ 'service' => $service->toArray() ]);

            // Remove temps
            $this->workarounds->refundOrdersCredits($service->getUserId());
            $this->cleanupServiceUpgradeEntities($service,
                UserService::STATUS_ACTIVE_UPGRADING == $service->getStatusBeforeSuspend());

            // Cancel
            Helper::apiResponse('updateClientProduct', [
                'serviceId' => $service->getServiceId(),
                'status'    => 'Cancelled',
            ], 'serviceid');

            $callCallback = true;
        }

        $service->setStatus(UserService::STATUS_CANCELLED)->save();
        if ($callCallback) {
            $service->getCallback()->call('cancellation');
        }
    }

    public function suspendPackage(UserService $service)
    {
        if (!in_array($service->getStatus(),
            [UserService::STATUS_ACTIVE, UserService::STATUS_ACTIVE_UPGRADED, UserService::STATUS_ACTIVE_UPGRADING])
        ) {
            throw new ErrorException(sprintf('Wrong package status "%s", should be "active"', $service->getStatus()));
        }

        Logger::info('### Suspending service', [ 'service' => $service->toArray() ]);

        // Update product status
        $product = Helper::apiResponse('getClientsProducts', [
            'clientId'  => $service->getUserId(),
            'serviceId' => $service->getServiceId()
        ], 'products.product.0')[ 'products' ][ 'product' ][ 0 ];
        if ('Suspended' != $product[ 'status' ]) {
            Helper::apiResponse('updateClientProduct', [
                'serviceId' => $service->getServiceId(),
                'status'    => 'Suspended',
            ], 'serviceid');
            Logger::debug(sprintf('Product %s status updated (%s ~> %s)', $service->getServiceId(),
                $product[ 'status' ], 'Suspended'));
        }

        // Update service status
        $service->setStatusBeforeSuspend($service->getStatus());
        $service->setStatus(UserService::STATUS_SUSPENDED);
        $service->save();
        $service->getCallback()->call('suspend');

        if (UserService::STATUS_ACTIVE_UPGRADING == $service->getStatusBeforeSuspend()) {
            $this->cleanupServiceUpgradeEntities($service);
        }

        Logger::info('Service suspended', [ 'service' => $service->toArray() ]);

        return $service;
    }

    public function unsuspendPackage(UserService $service)
    {
        if (UserService::STATUS_SUSPENDED != $service->getStatus()) {
            throw new ErrorException(sprintf('Wrong package status "%s"', $service->getStatus()));
        }

        if (!$service->getStatusBeforeSuspend()) {
            throw new ErrorException('Status before suspend is not saved');
        }

        Logger::info('### Unsuspending service', [ 'service' => $service->toArray() ]);

        // Update product status
        $product = Helper::apiResponse('getClientsProducts', [
            'clientId'  => $service->getUserId(),
            'serviceId' => $service->getServiceId()
        ], 'products.product.0')[ 'products' ][ 'product' ][ 0 ];
        if ('Active' != $product[ 'status' ]) {
            Helper::apiResponse('updateClientProduct', [
                'serviceId' => $service->getServiceId(),
                'status'    => 'Suspended',
            ], 'serviceid');
            Logger::debug(sprintf('Product %s status updated (%s ~> %s)', $service->getServiceId(),
                $product[ 'status' ], 'Active'));
        }

        // Update service status
        $service->setStatus($service->getStatusBeforeSuspend());
        $service->save();
        $service->getCallback()->call('unsuspend');

        Logger::info('Service unsuspended', [ 'service' => $service->toArray() ]);

        return $service;
    }

    // --- Internal

    public function calculateTotalForQuantity($productId, $quantity)
    {
        $pricingHelper = PricingHelper::getInstance();
        $total = $pricingHelper->getDefaultProductPriceForQuantity($productId, $quantity);
        $price = $pricingHelper->getLastProductPrice();

        Logger::debug("Total price \"$total\"", ['productId' => $productId, 'quantity' => $quantity, 'price' => $price]);

        return $total;
    }

    public function getProduct($productId)
    {
        $response = Helper::api('getProducts', [
            'pid' => $productId
        ]);
        if (empty($response[ 'products' ][ 'product' ][ 0 ])) {
            throw new ErrorException('No product found, response - ' . json_encode($response));
        }

        return $response[ 'products' ][ 'product' ][ 0 ];
    }

    protected function createTempProductFromProductId($productId, $quantity, $total)
    {
        $product = $this->getProduct($productId);
        $tempProductId = $this->createTempProductId(
            $product[ 'gid' ],
            sprintf($this->tempProductTitle, $product[ 'name' ], $quantity),
            $total, $product[ 'description' ]);
        if (!$tempProductId) {
            throw new ErrorException('No temp product id received');
        }

        // Copy custom fields
        foreach (Helper::conn()
            ->select('SELECT * FROM tblcustomfields WHERE `type` = "product" AND relid = ?', [$productId]) as $row) {
            $row['relid'] = $tempProductId;
            $row = Arr::except($row, ['id']);
            $fields = array_map(function($value) { return '`' . $value . '`'; }, array_keys($row));
            $values = array_values($row);
            $placehoders = array_fill(0, count($values), '?');

            Helper::conn()->statement(
                'INSERT INTO tblcustomfields
                    (' . join(',', $fields) . ')
                VALUES
                    (' . join(',', $placehoders) . ')', $values);
        }

        Emitter::emitEvent(new EmitterEvents\TransitionalProductGeneratedEvent($productId, $tempProductId));

        return $tempProductId;
    }

    public function createTempProductId($gid, $name, $total, $description = '')
    {
        try {
            $response = Helper::api('addProduct', [
                'type'        => 'other',
                'module'      => 'blazing_proxy_seller',
                'gid'         => $gid,
                'name'        => $name,
                'paytype'     => 'recurring',
                'hidden'      => 1,
                'pricing'     => [$this->defaultCurrency => ['monthly' => $total]],
                'description' => $description,
                'autosetup'   => 'payment',
                'order'       => 999
            ]);

            if (!empty($response[ 'pid' ])) {
                Helper::conn()->update("UPDATE tblproducts SET retired = 1 WHERE id = ?", [$response[ 'pid' ]]);

                return $response[ 'pid' ];
            } else {
                throw new ErrorException('No temp product id received, response - ' . json_encode($response));
            }
        } catch (\Exception $e) {
            // WHMCS create product api method workaround
            $conn = Helper::conn();
            Helper::restoreDb();

            $conn->insert("
                INSERT INTO `tblproducts`
                (
                    `type`, `gid`, `name`, `description`, `hidden`, `showdomainoptions`, `welcomeemail`, `stockcontrol`,
                    `qty`, `proratabilling`, `proratadate`, `proratachargenextmonth`, `paytype`, `allowqty`, `subdomain`,
                    `autosetup`, `servertype`, `servergroup`,
                    `configoption1`, `configoption2`, `configoption3`, `configoption4`, `configoption5`,
                    `configoption6`, `configoption7`, `configoption8`, `configoption9`, `configoption10`,
                    `configoption11`, `configoption12`, `configoption13`, `configoption14`, `configoption15`,
                    `configoption16`, `configoption17`, `configoption18`, `configoption19`, `configoption20`,
                    `configoption21`, `configoption22`, `configoption23`, `configoption24`,
                    `freedomain`, `freedomainpaymentterms`, `freedomaintlds`,
                    `recurringcycles`, `autoterminatedays`, `autoterminateemail`, `configoptionsupgrade`,
                    `billingcycleupgrade`, `upgradeemail`, `overagesenabled`, `overagesdisklimit`, `overagesbwlimit`,
                    `overagesdiskprice`, `overagesbwprice`, `tax`,
                    `affiliateonetime`, `affiliatepaytype`, `affiliatepayamount`,
                    `order`, `retired`, `is_featured`, `created_at`, `updated_at`
                ) VALUES (
                  ?, ?, ?, ?, ?, 0, 0, 0,
                  0, 0, 0, 0, ?, 0, '',
                  ?, ?, 0,
                  '', '', '', '', '',
                  '', '', '', '', '',
                  '', '', '', '', '',
                  '', '', '', '', '',
                  '', '', '', '',
                  '', '', '',
                  0, 0, 0, 0,
                  '', 0, '', 0, 0,
                  '0.0000', '0.0000', 0,
                  0, '', '0.00',
                  999, 1, 0, NOW(), NOW()
                )", [
                'other', $gid, $name, $description, 1,
                'recurring',
                'payment', 'blazing_proxy_seller'
            ]);

            $tempPid = $conn->getPdo()->lastInsertId();
            $conn->insert("
              INSERT INTO `tblpricing`
              (
                `type`, `currency`, `relid`,
                `msetupfee`, `qsetupfee`, `ssetupfee`, `asetupfee`, `bsetupfee`, `tsetupfee`,
                `monthly`, `quarterly`, `semiannually`, `annually`, `biennially`, `triennially`
            ) VALUES
            (
              'product', ?, ?,
              '0.00', '0.00', '0.00', '0.00', '0.00', '0.00',
              ?, '-1.00', '-1.00', '-1.00', '-1.00', '-1.00'
            )", [
                $this->defaultCurrency,
                $tempPid,
                $total
            ]);

            return $tempPid;
        }
    }

    public function removeTempProduct($productId)
    {
        // As there is API method to remove product, remove it from database
        Helper::db()->statement('DELETE FROM `tblpricing` WHERE `type` = "product" AND `relid` = ?', [$productId]);
        Helper::db()->statement('DELETE FROM `tblproducts` WHERE id = ?', [$productId]);
        Helper::db()->statement('DELETE FROM `tblcustomfields` WHERE `type` = "product" AND relid = ?', [$productId]);
    }

    protected function cleanupServiceUpgradeEntities(UserService $service, $order = true, $product = true)
    {
        if ($service->getUpgradeOrderId() and $order) {
            Logger::debug(sprintf('Service cleanup, order "%s" cancelling', $service->getUpgradeOrderId()));
            if ($this->isOrderExists($service->getUpgradeOrderId())) {
                $this->cancelOrder($service->getUpgradeOrderId(), false, true, true);
            }
            else {
                Logger::debug(sprintf('Order "%s" is not exists, remove request was not executed', $service->getUpgradeOrderId()));
            }
            $service->setUpgradeOrderId(0)->save();
        }
        if ($service->getTempProductId() and $product) {
            try {
                $previousProductExists = !!$this->getProduct($service->getTempProductId());
            } catch (ErrorException $e) {

            }
            if (!empty($previousProductExists)) {
                Logger::debug(sprintf('Service cleanup, temp product "%s" removing', $service->getTempProductId()));
                $this->removeTempProduct($service->getTempProductId());
                // TODO: move to somewhere in UserService
                $this->workarounds->uninheritPromo($service->getProductId(), $service->getTempProductId());
                $service->setTempProductId(0)->save();
            }
        }
    }

    protected function cancelOrder($orderId, $cancelSubscription = false, $forceIfNotPending = false, $delete = false)
    {
        if ($forceIfNotPending) {
            Helper::api('PendingOrder', ['orderId' => $orderId]);
        }

        // Subscription cancellation workaround - skip that, if error occurs
        $response = Helper::api('CancelOrder', ['orderId' => $orderId, 'cancelSub' => (int) $cancelSubscription]);
        if (!empty($response[ 'result' ]) and 'success' != $response[ 'result' ]) {
            $exceptionMessage = "Order \"$orderId\" cannot be cancelled , response - %s";

            if (!empty($response[ 'message' ]) and
                false !== stripos($response[ 'message' ], 'subscription cancellation failed')
            ) {
                Logger::warn('Cancel order, subscription workaround applied');
                Helper::apiResponse('CancelOrder', ['orderId' => $orderId, 'cancelSub' => 0],
                    'result=success', $exceptionMessage);
            } else {
                throw new ErrorException(sprintf($exceptionMessage, json_encode($response)));
            }
        }

        if ($delete) {
            Helper::apiResponse('DeleteOrder', ['orderId' => $orderId], 'result=success',
                "Order \"\$orderId\" cancelled, but cannot be deleted , response - %s");
        }
    }

    protected function isOrderExists($orderId, $notCancelled = false)
    {
        $order = APIFactory::orders()->getOrder($orderId);

        if ($orderId != $order['id']) {
            return false;
        }

        if ('Cancelled' == $order['status'] and $notCancelled) {
            return false;
        }

        return true;
    }

    protected function getPaymentGateway($userId)
    {
        if (!$this->defaultPaymentGateway) {
            $paymentGateways = array_values(Arr::pluck(APIFactory::system()->getPaymentMethods(), 'module'));
            if (!$paymentGateways) {
                throw new UserFriendlyException('No payment methods available');
            }

            if (count($paymentGateways) == 1) {
                $paymentGateway = $paymentGateways[0];
            }
            else {
                $lastInvoice = Helper::conn()->selectOne('
                  SELECT paymentmethod FROM tblinvoices WHERE userid = ? AND `status` = ?
                  ORDER BY id DESC
                  LIMIT 1', [$userId, 'Paid']);
                Helper::restoreDb();

                $paymentGateway = false;
                if (!empty($lastInvoice['paymentmethod'])) {
                    $paymentGateway = $lastInvoice['paymentmethod'];
                }

                // Not found
                if (!in_array($paymentGateway, $paymentGateways)) {
                    $paymentGateway = Settings::getInstance()->get('payment_gateway');
                }

                // Not available anymore
                if (!in_array($paymentGateway, $paymentGateways)) {
                    $paymentGateway = $paymentGateways[0];
                }
            }

            $this->defaultPaymentGateway = $paymentGateway;
        }

        return $this->defaultPaymentGateway;
    }
}
