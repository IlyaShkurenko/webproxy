<?php

namespace WHMCS\Module\Blazing\Proxy\Seller;

use ErrorException;
use Gears\Arrays;
use WHMCS\Module\Blazing\Proxy\Seller\UserService\UserServiceCallback;
use WHMCS\Module\Blazing\Proxy\Seller\UserService\UserServiceProductDescriber;
use WHMCS\Module\Framework\Helper;

class UserService
{

    const STATUS_NEW = 'new';
    const STATUS_ACTIVE = 'active';
    const STATUS_ACTIVE_UPGRADING = 'active_upgrading';
    const STATUS_ACTIVE_UPGRADED = 'active_upgraded';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_SUSPENDED = 'suspended';

    /**
     * @var int
     */
    protected $id;

    /**
     * @var int
     */
    protected $userId;

    /**
     * @var int
     */
    protected $productId;

    /**
     * @var int
     */
    protected $quantity;

    protected $promoId = 0;

    /**
     * @var array
     */
    protected $data = [];

    protected $relations = [];

    public static function findByUserProduct($userId, $productId)
    {
        $row = Helper::conn()->selectOne(
            'SELECT * FROM mod_blazing_proxy_seller_services WHERE user_id = ? AND product_id = ? ORDER BY id DESC',
            [$userId, $productId]
        );

        if (!$row) {
            return false;
        }

        return self::convertRowToEntity($row);
    }

    public static function findManyByUser($userId)
    {
        $rows = Helper::conn()->select(
            'SELECT * FROM mod_blazing_proxy_seller_services WHERE user_id = ?',
            [$userId]
        );

        return array_map(function ($row) {
            return self::convertRowToEntity($row);
        }, $rows);
    }

    public static function findByInitialInvoice($invoiceId)
    {
        $invoiceId = (int) $invoiceId;

        $row = Helper::conn()->selectOne(
            "SELECT * FROM mod_blazing_proxy_seller_services WHERE `data` REGEXP '\"initial\.invoiceId\":$invoiceId\[^\d\]'"
        );

        if (!$row) {
            return false;
        }

        return self::convertRowToEntity($row);
    }

    public static function findByUpgradeInvoice($invoiceId)
    {
        $invoiceId = (int) $invoiceId;

        $row = Helper::conn()->selectOne(
            "SELECT * FROM mod_blazing_proxy_seller_services WHERE `data` REGEXP '\"upgrade\.invoiceId\":$invoiceId\[^\d\]'"
        );

        if (!$row) {
            return false;
        }

        return self::convertRowToEntity($row);
    }

    public static function findByAnyInvoice($invoiceId)
    {
        $row = Helper::conn()->selectOne("
            SELECT ii.relid FROM tblinvoiceitems ii INNER JOIN tblinvoices i ON i.id = ii.invoiceid
            WHERE ii.type = 'Hosting' AND ii.invoiceid = ?
            UNION ALL
            SELECT u.relid FROM tblinvoiceitems ii INNER JOIN tblupgrades u ON ii.relid = u.id
            WHERE ii.type = 'Upgrade' AND ii.invoiceid = ?", [$invoiceId, $invoiceId]);

        if (empty($row['relid'])) {
            return false;
        }

        return static::findByCustomerServiceId($row['relid']);
    }

    public static function findByCustomerServiceId($serviceId)
    {
        $serviceId = (int) $serviceId;

        $row = Helper::conn()->selectOne(
            "SELECT * FROM mod_blazing_proxy_seller_services WHERE `data` REGEXP '\"serviceId\":$serviceId\[^\d\]'"
        );

        if (!$row) {
            return false;
        }

        return self::convertRowToEntity($row);
    }

    public function save()
    {
        $conn = Helper::db();

        if (!$this->id) {
            $conn->statement('INSERT INTO mod_blazing_proxy_seller_services
                (user_id, product_id, quantity, `data`) VALUES(?, ?, ?, ?)',
                [$this->getUserId(), $this->getProductId(), $this->getQuantity(), json_encode($this->getData())]);

            $entity = self::findByUserProduct($this->getUserId(), $this->getProductId());

            if (!$entity) {
                throw new ErrorException('Unable to save service!');
            }

            // Get the id
            $this->setId($entity->getId());
        } else {
            $conn->statement(
                'UPDATE mod_blazing_proxy_seller_services SET user_id = ?, product_id  = ?, quantity = ?, `data` = ?
                WHERE id = ?',
                [$this->getUserId(), $this->getProductId(), $this->getQuantity(), json_encode($this->getData()),
                    $this->getId()]);
        }
    }

    public function drop()
    {
        if (!$this->id) {
            throw new ErrorException("Can't be dropped, as service doesn't exists");
        }

        Helper::conn()->statement('DELETE FROM mod_blazing_proxy_seller_services WHERE id = ?', [$this->id]);

        unset($this->id);
    }

    public function __toString()
    {
        return (string) json_encode($this->toArray());
    }

    public function toArray()
    {
        $arr = [];

        foreach ([
            'getId',
            'getUserId',
            'getProductId',
            'getQuantity',

            // Virtual
            'getStatus',
            'getServiceId',
            'getInitialOrderId',
            'getInitialInvoiceId',
            'getUpgradeOrderId',
            'getUpgradeInvoiceId',
            'getTempProductId',
            'getTempOrderId',
            'getTempInvoiceId',
            'getTempQuantity',
            'getStatusBeforeSuspend'
        ] as $accessor) {
            $key = lcfirst(preg_replace('~^get~', '', $accessor));
            $value = $this->$accessor();

            if (!is_null($value)) {
                $arr[ $key ] = $value;
            }
        }

        return $arr;
    }

    // Accessors

    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set id
     *
     * @param int $id
     * @return $this
     */
    public function setId($id)
    {
        $this->id = (int) $id;

        return $this;
    }

    /**
     * Get userId
     *
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Set userId
     *
     * @param int $userId
     * @return $this
     */
    public function setUserId($userId)
    {
        $this->userId = (int) $userId;

        return $this;
    }

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
     * @param int $productId
     * @return $this
     */
    public function setProductId($productId)
    {
        $this->productId = (int) $productId;

        return $this;
    }

    /**
     * @return Product|null|false
     */
    public function getProduct()
    {
        if (empty($this->relations[Product::class]) and $this->productId) {
            $this->relations[Product::class] = Product::findById($this->productId);
        }

        return $this->relations[Product::class];
    }

    /**
     * @return UserServiceProductDescriber
     */
    public function getServiceDescriber()
    {
        if (empty($this->relations[UserServiceProductDescriber::class])) {
            $this->relations[UserServiceProductDescriber::class] = new UserServiceProductDescriber($this);
        }

        return $this->relations[UserServiceProductDescriber::class];
    }

    /**
     * Get quantity
     *
     * @return int
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * Set quantity
     *
     * @param int $quantity
     * @return $this
     */
    public function setQuantity($quantity)
    {
        $this->quantity = $quantity;

        return $this;
    }

    /**
     * Get data
     *
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Set data
     *
     * @param array $data
     * @return $this
     */
    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }

    public function getFromData($key, $default = null)
    {
        return Arrays::get($this->data, $key, $default);
    }

    public function setToData($key, $data)
    {
        $this->data[ $key ] = $data;

        return $this;
    }

    // Virtual accessors

    public function getStatus()
    {
        return $this->getFromData('status', self::STATUS_NEW);
    }

    public function setStatus($status)
    {
        return $this->setToData('status', $status);
    }

    public function getServiceId()
    {
        return $this->getFromData('serviceId');
    }

    public function setServiceId($serviceId)
    {
        return $this->setToData('serviceId', (int) $serviceId);
    }

    public function getInitialOrderId()
    {
        return $this->getFromData('initial.orderId');
    }

    public function setInitialOrderId($orderId)
    {
        return $this->setToData('initial.orderId', (int) $orderId);
    }

    public function getInitialInvoiceId()
    {
        return $this->getFromData('initial.invoiceId');
    }

    public function setInitialInvoiceId($invoiceId)
    {
        return $this->setToData('initial.invoiceId', (int) $invoiceId);
    }

    public function getUpgradeOrderId()
    {
        return $this->getFromData('upgrade.orderId');
    }

    public function setUpgradeOrderId($orderId)
    {
        return $this->setToData('upgrade.orderId', (int) $orderId);
    }

    public function getUpgradeInvoiceId()
    {
        return $this->getFromData('upgrade.invoiceId');
    }

    public function setUpgradeInvoiceId($invoiceId)
    {
        return $this->setToData('upgrade.invoiceId', (int) $invoiceId);
    }

    public function getTempProductId()
    {
        return $this->getFromData('temp.productId');
    }

    public function setTempProductId($invoiceId)
    {
        return $this->setToData('temp.productId', (int) $invoiceId);
    }

    public function getTempOrderId()
    {
        return $this->getFromData('temp.orderId');
    }

    public function setTempOrderId($orderId)
    {
        return $this->setToData('temp.orderId', (int) $orderId);
    }

    public function getTempInvoiceId()
    {
        return $this->getFromData('temp.invoiceId');
    }

    public function setTempInvoiceId($invoiceId)
    {
        return $this->setToData('temp.invoiceId', (int) $invoiceId);
    }

    public function getTempQuantity()
    {
        return $this->getFromData('temp.quantity');
    }

    public function setTempQuantity($quantity)
    {
        return $this->setToData('temp.quantity', (int) $quantity);
    }

    public function getRedirectSuccessUrl()
    {
        return $this->getFromData('url.redirect.success');
    }

    public function setRedirectSuccessUrl($url)
    {
        return $this->setToData('url.redirect.success', $url);
    }

    public function getRedirectFailUrl()
    {
        return $this->getFromData('url.redirect.fail');
    }

    public function setRedirectFailUrl($url)
    {
        return $this->setToData('url.redirect.fail', $url);
    }

    public function getCallbackUrl()
    {
        return $this->getFromData('url.callback');
    }

    public function setCallbackUrl($url)
    {
        return $this->setToData('url.callback', $url);
    }

    public function setPromoId($promoId) {
        $this->promoId = $promoId;
    }

    public function getPromoId() {
        return $this->promoId;
    }

    /**
     * @return UserServiceCallback
     */
    public function getCallback()
    {
        if (empty($this->relations[UserServiceCallback::class])) {
            $this->relations[UserServiceCallback::class] = new UserServiceCallback($this);
        }

        return $this->relations[UserServiceCallback::class];
    }

    public function getStatusBeforeSuspend()
    {
        return $this->getFromData('suspend.statusBefore');
    }

    public function setStatusBeforeSuspend($status)
    {
        return $this->setToData('suspend.statusBefore', $status);
    }


    // Internals

    protected static function convertRowToEntity(array $row)
    {
        $instance = new self();
        $instance
            ->setId($row[ 'id' ])
            ->setUserId($row[ 'user_id' ])
            ->setProductId($row[ 'product_id' ])
            ->setQuantity($row[ 'quantity' ])
            ->setData(json_decode($row[ 'data' ], true));

        return $instance;
    }

    public function getHasCancelRequest()
    {
        return ((Helper::conn()->selectOne(
                'SELECT id FROM `tblcancelrequests` creq WHERE creq.relid = ?',
                [$this->getServiceId()]
            ) != false) ? true : false);
    }

    public function removeCancelRequest() {
        self::removeCancelRequestForService($this->getServiceId());
    }

    public function removeCancelRequestAndReactivateInvoice() {
        self::removeCancelRequestAndReactivateInvoiceForService($this->getServiceId());
    }

    public static function removeCancelRequestForService($serviceid)
    {
        Helper::conn()->delete(
            "DELETE FROM `tblcancelrequests` WHERE `relid` = ?", [$serviceid]
        );
    }

    public static function removeCancelRequestAndReactivateInvoiceForService($serviceid) {
        self::removeCancelRequestForService($serviceid);

        $db = Helper::conn();
        $res = $db->update(
            'UPDATE `tblinvoices` SET `status`=\'Unpaid\'
                WHERE id = (SELECT invoiceid FROM `tblinvoiceitems` WHERE `type` = \'Hosting\' and `relid`=? ORDER BY id DESC LIMIT 1)
                and `duedate` = (SELECT `nextduedate` FROM `tblhosting` WHERE `id` = ?)
                and `status` = \'Cancelled\'',
            [$serviceid, $serviceid]
        );
    }

    public function applyPromo($promoid) {
        if($this->getTempProductId() == 0)
            throw new ErrorException('Promo is only applied on upgrades/downgrades when temp.productId is set');

        Helper::apiResponse('UpdateClientProduct', [
            'serviceid' => $this->getServiceId(),
            'autorecalc' => true,
            'promoid' => $promoid
        ], 'result=success');

        // if service was required for rec_for - 1 times, the next time due invoice generated
        // promo will disappear, set it back to be sure
        Helper::apiResponse('UpdateClientProduct', [
            'serviceid' => $this->getServiceId(),
            'promoid' => $promoid
        ], 'result=success');
    }

    public function resolvePromotion(&$promocode)
    {
        $hosting = $this->getServiceDescriber()->getData();

        if($promocode !== false && ($promotion = Helper::conn()->table('tblpromotions')
            ->where('code', $promocode)
            ->first()) != false
            && $this->validatePromo($promotion, true)
        ) {
            return $promotion['id'];
        } else {
            if (isset($hosting['promoid'])
                && ($promotion = Helper::conn()->table('tblpromotions')
                ->where('id', $hosting['promoid'])
                ->first()) != false
                && $this->validatePromo($promotion)
            ) {
                $promocode = $promotion['code'];

                return $hosting['promoid'];
            }
        }

        return 0;
    }

    public function validatePromo($promotion, $new = false) { // TODO: rename to validate promo on upgrade
        if($promotion['recurring']) {
            $response = Helper::conn()->selectOne("SELECT COUNT(*) as recurringcount FROM tblinvoiceitems
                JOIN `tblinvoices` ON `tblinvoices`.`id` = tblinvoiceitems.invoiceid
                WHERE `tblinvoiceitems`.`userid` = ? and `type` = 'Hosting' and `relid` = ? and `tblinvoices`.`status` = 'Paid'",
                [$this->getUserId(), $this->getServiceId()]
            );

            // when upgrading current due invoice is cancelled and generated new
            if(($promotion['upgrades'] || !$new) && ($promotion['recurfor'] == 0 || $promotion['recurfor'] >= $response['recurringcount'])) {
                return true;
            }
        } else if($new && $promotion['upgrades']) {
            return true;
        }

        Logger::info('### Promo not valid', [ 'code' => $promotion['code'] ]);

        return false;
    }

    // WHMCS has wrong logic about allowing promo to be applied
    // it calculates reccuring amount by counting all invoices
    // but need to be only invoices to which promo was applied
    // TODO: rewrite cond
    public function isPromoActive($promotion) {
        $response = Helper::conn()->selectOne("SELECT COUNT(*) as recurringcount FROM tblinvoiceitems
            JOIN `tblinvoices` ON `tblinvoices`.`id` = tblinvoiceitems.invoiceid
            WHERE `tblinvoiceitems`.`userid` = ? and `type` = 'Hosting' and `relid` = ? and `tblinvoices`.`status` = 'Paid'",
            [$this->getUserId(), $this->getServiceId()]
        );

        if($promotion['recurfor'] == 0 || $promotion['recurfor'] >= $response['recurringcount']) {
            return true;
        }

        return false;
    }

    public function getInitialOrder() {
        $order = Helper::apiResponse('getOrders', ['id' => $this->getInitialOrderId()], 'orders.order.0');
        return $order[ 'orders' ][ 'order' ][ 0 ];
    }

    public function upgradeEnabled() {
        $hosting = $this->getServiceDescriber()->getData();
        if( $hosting['nextduedate'] <= date("Y-m-d"))
            return false;

        return true;
    }
}
