<?php

namespace WHMCS\Module\Blazing\Proxy\Seller\Events;

use WHMCS\Module\Blazing\Proxy\Seller\Logger;
use WHMCS\Module\Blazing\Proxy\Seller\Seller;
use WHMCS\Module\Blazing\Proxy\Seller\UserService;
use WHMCS\Module\Framework\Events\AbstractHookListener;
use WHMCS\Module\Framework\Helper;
use WHMCS\Module\Blazing\Proxy\Seller\Util\IsolatedConnection;

class InvoiceCreated extends AbstractHookListener
{
    protected $name = 'InvoiceCreated';

    protected function execute(array $args = null)
    {
        $db = new IsolatedConnection();

        $db->setFetchMode(\PDO::FETCH_OBJ);

        if(!isset($args['invoiceid']))
            return true;

        $invoiceid = $args['invoiceid'];

        $response = $db->select(
            'SELECT * FROM `tblinvoiceitems` invitm WHERE invitm.invoiceid = ? and (invitm.type = \'Hosting\' or invitm.type = \'Upgrade\')',
            [$invoiceid]
        );

        if(empty($response))
            return true;

        foreach($response as $invoiceItem)  {
            $hosting = $db->selectOne('SELECT * FROM `tblhosting` WHERE `id` = ?', [$invoiceItem->relid]);
            if(empty($hosting) || $hosting->amount != 0 || (($service = UserService::findByCustomerServiceId($hosting->id)) === false)) // || $hosting['promoid'] != 0)
                continue;

            $productId = $hosting->packageid;
            $userId = $hosting->userid;
            $serviceId = $hosting->id;

            if( ($promoHosting = $db->selectOne(
                        "SELECT * FROM tblinvoiceitems WHERE `invoiceid` = ? and `userid` = ?
                        and (`type` = 'PromoHosting' or (`description` LIKE 'Promotional Code:%' and `type` = ''))
                        and (`relid` = ? or (`relid` = 0 and `id` = ?+1))",
                    [$invoiceid, $userId, $serviceId, $invoiceItem->id]
                    )) == false)
                continue;

            $order = $db->table('tblorders')
                ->where('id', $hosting->orderid)
                ->first();

            if(empty($order->promocode)) {
                if(($promocode = $this->getPromocodeFromInvoiceItemDesc($promoHosting->description)) == false)
                    continue;
            } else {
                $promocode = $order->promocode;
            }

            $promotion = $db->table('tblpromotions')
                ->where('code', $promocode)
                ->first();

            if(!($promotion->recurring && $promotion->recurfor && preg_match("/(,|^)$productId(,|$)/", $promotion->appliesto)) )
                return true;

            logActivity('### Fix requiring amount after invoice generation ###');

            $quantity = $service->getTempQuantity() ? $service->getTempQuantity() : $service->getQuantity();
            $seller = new Seller();
            $total = $seller->calculateTotalForQuantity($productId, $quantity);

            Helper::apiResponse('UpdateClientProduct', [
                'serviceid' => $serviceId,
                'recurringamount' => $total,
                //'pid' => $tempProductId,
                //'autorecalc' => true,
                'promoid' => $promotion->id
            ], 'result=success');
        }
    }

    protected function getPromocodeFromInvoiceItemDesc($str) {
        $ds = strripos($str, ':')+2;
        $str = substr($str, $ds, strripos($str, '-')-1-$ds);
        return $str;
    }
}
