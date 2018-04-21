<?php

namespace WHMCS\Module\Blazing\Proxy\Seller\Events;

use WHMCS\Module\Framework\Events\AbstractHookListener;
use WHMCS\Module\Framework\Helper;
use WHMCS\Module\Blazing\Proxy\Seller\Util\IsolatedConnection;
use WHMCS\Module\Blazing\Proxy\Seller\UserService;

class InvoicePaid extends AbstractHookListener
{
    protected $name = 'InvoicePaid';

    protected function execute(array $args = null)
    {
        $db = new IsolatedConnection();

        $db->setFetchMode(\PDO::FETCH_ASSOC);

        $invoiceId = $args['invoiceid'];

        $invoiceItems = $db->select('SELECT * FROM `tblinvoices` inv
            JOIN `tblinvoiceitems` invitm ON invitm.invoiceid = inv.id
            WHERE invitm.type = \'Hosting\' and inv.id = ?', [$invoiceId]);
        foreach($invoiceItems as $item) {
            $res = $db->select('SELECT * FROM `tblupgrades` `upg` WHERE `upg`.`relid` = ? and `upg`.`status` = \'Pending\'',
                [$item['relid']]
            );

            foreach($res as $upgradeOrder) {
                if(UserService::findByCustomerServiceId($upgradeOrder['relid']) != false) {
                    Helper::api('CancelOrder', [
                        'orderid' => $upgradeOrder['orderid'],
                    ], 'result=success');
                }
            }
        }
    }
}
