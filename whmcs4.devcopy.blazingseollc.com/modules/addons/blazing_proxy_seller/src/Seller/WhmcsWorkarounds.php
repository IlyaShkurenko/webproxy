<?php

namespace WHMCS\Module\Blazing\Proxy\Seller\Seller;

use Axelarge\ArrayTools\Arr;
use WHMCS\Module\Blazing\Proxy\Seller\Logger;
use WHMCS\Module\Blazing\Proxy\Seller\UserService;
use WHMCS\Module\Framework\Addon;
use WHMCS\Module\Framework\Api\APIFactory;
use WHMCS\Module\Framework\Api\ArrayResult;
use WHMCS\Module\Framework\Helper;

class WhmcsWorkarounds
{
    public function refundOrdersCredits($userId)
    {
        $invoices = Helper::conn()->select(
            'SELECT id, credit, status, subtotal, duedate
            FROM tblinvoices WHERE userid = ? AND status IN ("Unpaid", "Cancelled") AND credit > 0',
            [$userId]
        );
        if (!$invoices) {
            return;
        }

        /** @noinspection PhpIncludeInspection */
        require_once Helper::getRootDir() . '/includes/invoicefunctions.php';

        foreach ($invoices as $invoice) {
            Logger::debug(sprintf('Workaround of refund of customers credits for "$%s" from %s invoice "%s"',
                $invoice['credit'], strtolower($invoice['status']), $invoice['id']), ['invoice' => $invoice]);

            // Refund credit
            APIFactory::billing()
                ->addCredit($userId, $invoice['credit'],
                    sprintf('Credit Removed by Proxy Seller plugin from Invoice #%s', $invoice['id']))
                ->validate('result=success');

            // Recalculate invoice

            // API request doesn't work here for some unknown reason
            // APIFactory::billing()->updateInvoice($invoice['invoiceId'], ['credit' => 0])->validate('result=success');
            Helper::conn()->update('UPDATE tblinvoices SET credit = 0 WHERE id = ?', [$invoice['id']]);

            /** @noinspection PhpUndefinedFunctionInspection */
            updateInvoiceTotal($invoice['id']);
        }
    }

    public function applyPreventZeroInvoices($serviceId)
    {
        /** @var ArrayResult $service */
        $service = APIFactory::client()->getProducts(null, $serviceId);

        if (!$service->isLoaded()) {
            return false;
        }
        $service = $service[0];

        $item = Helper::conn()->selectOne(
            "SELECT
              ii.id,
              ii.relid AS relId,
              ii.amount,
              ii.duedate
            FROM tblinvoiceitems ii
            WHERE ii.relid = ? AND ii.type = 'Hosting' AND duedate = ? AND amount = 0", [$serviceId, $service['nextDueDate']]);

        Logger::info('Workaround zero invoice', [
            'paymentMethod' => $service[ 'paymentMethod' ],
            'dueDate'       => $service[ 'nextDueDate' ],
            'item'          => $item,
            'whmcsService'  => $service->__toString(),
        ]);

        if ($item) {
            return false;
        }

        try {
//            Helper::conn()->insert("
//            INSERT INTO tblinvoices (
//                userid, invoicenum, `date`, duedate, datepaid, last_capture_attempt, subtotal, credit,
//                tax, tax2, total, taxrate, taxrate2, status, paymentmethod, notes)
//            VALUES (
//              ?, '', ?, ?, '0000-00-00 00:00:00', '0000-00-00 00:00:00',
//              '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', 'Paid', ?, 'MyWorks Billing workaround'
//            )", [$service['clientId'], $service['nextDueDate'], $service['nextDueDate'], $service['paymentMethod']]);
//            $invoiceId = Helper::conn()->getPdo()->lastInsertId();
            Helper::conn()->insert("
              INSERT INTO tblinvoiceitems (
                invoiceid, userid, `type`, relid, description, amount, taxed, duedate, paymentmethod, notes)
              VALUES (?, ?, 'Hosting', ?, '$0 invoice workaround', '0.00', '0', ?, ?,
                '$0 invoice workaround') ",
                [-1, $service['clientId'], $serviceId, $service['nextDueDate'], $service['paymentMethod']]);
        }
        catch (\Exception $e) {
            Logger::warn('Workaround exception', ['exception' => $e]);
        }

        return true;
    }

    public function revertPreventZeroInvoices($serviceId)
    {
        $service = APIFactory::client()->getProducts(null, $serviceId);

        if (!$service->isLoaded()) {
            return false;
        }
        $service = $service[0];

        $items = Helper::conn()->select(
            "SELECT
              ii.id,
              ii.description,
              ii.duedate
            FROM tblinvoiceitems ii
            WHERE ii.relid = ? AND ii.type = 'Hosting' AND amount = 0 AND ii.description LIKE '%invoice workaround%'",
            [$serviceId, $service['nextDueDate']]);

        if (!$items) {
            return false;
        }

        Logger::info('Workaround zero invoice revert', ['items' => $items]);
        foreach ($items as $item) {
            Helper::conn()->delete('DELETE FROM tblinvoiceitems WHERE id = ?', [$item['id']]);
        }

        return true;
    }

    public function cleanupZeroInvoices($serviceId)
    {

        $invoiceItems = Helper::conn()->select(
            'SELECT *, invoiceid AS invoiceId
            FROM tblinvoiceitems
            WHERE `type` = \'Hosting\' AND relid = ? AND amount = 0', [$serviceId]);

        if ($invoiceItems) {
            $invoicesRemoved = $ordersRemoved = [];
            foreach ($invoiceItems as $item) {
                $invoice = Helper::conn()->selectOne('SELECT * FROM tblinvoices WHERE id  = ?', [$item['invoiceId']]);
                $order = Helper::conn()->selectOne('SELECT * FROM tblorders WHERE invoiceid = ?', [$item['invoiceId']]);

                if ((!$invoice and $order) /*or ($invoice and !$order)*/) {
                    Logger::warn('Workaround cleanup zero invoices: both invoice and order must exist', [
                        'items' => $item,
                        'invoices' => $invoice,
                        'orders' => $order
                    ]);
                    continue;
                }
                if ($invoice['total'] > 0) {
                    Logger::warn('Workaround cleanup zero invoices: invoice is not $0 while invoice item if $0', [
                        'invoice' => $invoice
                    ]);
                    $invoice = null;
                    $order = null;
                }

                if ($invoice) {
                    Helper::conn()->delete('DELETE FROM tblinvoices WHERE id = ?', [$invoice['id']]);
                    $invoicesRemoved[] = $invoice;
                }
                if ($order) {
                    Helper::conn()->delete('DELETE FROM tblorders WHERE id = ?', [$order['id']]);
                    $ordersRemoved[] = $order;
                }

                Helper::conn()->delete('DELETE FROM tblinvoiceitems WHERE id = ?', [$item['id']]);
            }

            Logger::debug('Workaround zero invoices cleaned zero orders/invoices up by invoice items', [
                'items'    => $invoiceItems,
                'invoices' => $invoicesRemoved,
                'orders'    => $ordersRemoved
            ]);
        }

        $upgradeItems = Helper::conn()->select(
            'SELECT u.*, u.orderid AS orderId
            FROM tblupgrades u
            INNER JOIN tblorders o ON o.id = u.orderid
            WHERE `type` = "package" AND relid = ? AND o.amount = 0',
        [$serviceId]);

        if ($upgradeItems) {
            $ordersRemoved = [];
            foreach ($upgradeItems as $item) {
                $order = Helper::conn()->selectOne('SELECT * FROM tblorders WHERE id = ?', [$item['orderId']]);

                if ($order) {
                    Helper::conn()->delete('DELETE FROM tblorders WHERE id = ?', [$order['id']]);
                    $ordersRemoved[] = $order;
                }

                Helper::conn()->delete('DELETE FROM tblupgrades WHERE id = ?', [$item['id']]);
            }

            Logger::debug('Workaround zero invoices cleaned zero orders/invoices up by upgrade items', [
                'items'    => $upgradeItems,
                'orders'    => $ordersRemoved
            ]);
        }

        return $invoiceItems or $upgradeItems;
    }

    public function regenerateRecurringInvoice($serviceId)
    {
        /** @var ArrayResult $service */
        $service = APIFactory::client()->getProducts(null, $serviceId);

        if (!$service->isLoaded()) {
            return false;
        }
        $service = $service[0];

        $unpaidInvoices = Helper::conn()->select(
            "SELECT
              i.id, ii.amount, i.status
            FROM tblinvoiceitems ii
            INNER JOIN tblinvoices i ON ii.type = 'Hosting' AND ii.invoiceid = i.id
            WHERE ii.relid = ? AND i.status IN('Unpaid', 'Cancelled') AND i.duedate = ?
            ORDER BY ii.id DESC", [$serviceId, $service['nextDueDate']]);

        // No unpaid invoice is found
        if (!$unpaidInvoices) {
            return false;
        }

        Logger::info('Workaround regenerate recurring invoice', [
            'unpaidInvoices' => $unpaidInvoices,
            'dueDate'       => $service[ 'nextDueDate' ],
            'whmcsService'  => $service->__toString(),
        ]);

        // Cancel prev unpaid invoice
        $alreadyRegenerated = false;
        foreach ($unpaidInvoices as $invoice) {
            if ($invoice['amount'] != $service['recurringAmount']) {
                if ('Cancelled' != $invoice['status']) {
                    Helper::conn()->update('UPDATE tblinvoices SET status = "Cancelled", notes = "Cancelled by plugin" WHERE id = ?',
                        [$invoice['id']]);
                }
            }
            else {
                $alreadyRegenerated = $invoice;
            }
        }
        if ($alreadyRegenerated) {
            Logger::debug('Recurring invoice already regenerated, no need to generate once more',
                ['invoice' => $alreadyRegenerated]);

            return false;
        }

        // Temporary make invoice items orphans to re-create invoice
        $items = Helper::conn()->select(
            "SELECT
              ii.id,
              ii.relid AS relId,
              ii.amount,
              ii.duedate
            FROM tblinvoiceitems ii
            WHERE ii.relid = ? AND ii.type = 'Hosting'", [$serviceId]);
        if ($items) {
            Helper::conn()->update('UPDATE tblinvoiceitems SET relid = 1 WHERE id IN (' . join(',', Arr::pluck($items, 'id')) . ')');
        }
        Logger::debug('Invoice items hidden', ['items' => $items]);

        try {
            $result = Helper::apiResponse('genInvoices', ['serviceids' => $serviceId, 'noemails' => false], 'result=success');
            Logger::debug('Recurring invoice regenerated', ['result' => $result]);
        }
        catch (\Exception $e) {
            Logger::warn('Error on genInvoices: ' . $e->getMessage());
        }

        // Restore invoice items
        if ($items) {
            foreach ($items as $item) {
                Helper::conn()->update('UPDATE tblinvoiceitems SET relid = ? WHERE id = ?', [$item['relId'], $item['id']]);
            }
            Logger::debug('Invoice items restored', ['items' => $items]);
        }

        return empty($e);
    }

    public function emulatePricingOverrideOnNewOrderHook($userId, $productId, $price, $billingCycle)
    {
        $data = [
            'key' => 0,
            'pid' => $productId,
            'proddata' => [
                'pricing' => call_user_func(function() use ($price, $billingCycle) {
                    switch (strtolower($billingCycle)) {
                        case 'one time':
                        case 'monthly':
                            return [
                                'monthly' => $price,
                                'msetupfee' => 0
                            ];
                            break;

                        case 'quarterly':
                            return [
                                'quarterly' => $price,
                                'qsetupfee' => 0
                            ];
                            break;

                        case 'semi-annually':
                            return [
                                'semiannually' => $price,
                                'ssetupfee' => 0
                            ];
                            break;

                        case 'annually':
                            return [
                                'annually' => $price,
                                'asetupfee' => 0
                            ];
                            break;

                        case 'biennially':
                            return [
                                'biennially' => $price,
                                'bsetupfee' => 0
                            ];
                            break;

                        case 'triennially':
                            return [
                                'triennially' => $price,
                                'tsetupfee' => 0
                            ];
                            break;

                        default:
                            return null;
                    }
                }),
                'billingcycle' => $billingCycle,
                'qty' => 1
            ]
        ];

        // Preserve session uid
        $uid = Arr::getOrElse($_SESSION, 'uid');
        $_SESSION['uid'] = $userId;

        // Call hook
        ob_start();
        /** @noinspection PhpUndefinedFunctionInspection */
        $result = run_hook('OrderProductPricingOverride', $data);
        ob_end_clean();

        if (!empty($result)) {
            Logger::info('Workaround for pricing override on product purchase', [
                'response' => $result,
                'args'     => $data
            ]);
        }

        // Restore uid
        $_SESSION['uid'] = $uid;

        if (!empty($result[0])) {
            $result = $result[0];
        }

        return (float) (isset($result['recurring']) ? $result['recurring'] : $price);
    }

    public function emulateInvoicePaid(UserService $service)
    {
        /*if (UserService::STATUS_NEW != $service->getStatus()) {
            Logger::warn(sprintf('Invoice paid workaround called, although service status is not new'), [
                'service' => $service->toArray()
            ]);

            return false;
        }*/

        // Check the invoice
        $invoice = Helper::api('getInvoice', ['invoiceId' => $service->getInitialInvoiceId()]);
        if (empty($invoice['invoiceid']) or 'Paid' != $invoice['status']) {
            Logger::warn(sprintf('Invoice paid workaround called, although invoice is not exists or has not been paid'), [
                'invoice' => $invoice,
                'service' => $service->toArray()
            ]);

            return false;
        }

        $data = ['invoiceid' => $service->getInitialInvoiceId()];

        // Call hook
        ob_start();
        /** @noinspection PhpUndefinedFunctionInspection */
        $result = run_hook('InvoicePaid', $data);
        ob_end_clean();

        Logger::debug('Workaround for "invoice paid" hook after "service create"', [
            'response' => $result,
            'args'     => $data
        ]);

        return true;
    }

    public function inheritPromo($promo, $origProductId, $tempProductId)
    {
        return Helper::db()->affectingStatement("
          UPDATE tblpromotions
          SET appliesto = CONCAT(appliesto, ',$tempProductId,-')
          WHERE `code` = ? AND  appliesto REGEXP '(^|[^0-9])$origProductId([^0-9]|\$)'",
            [$promo]);
    }

    public function uninheritPromo($origProductId, $tempProductId)
    {
        Helper::db()->statement("
          UPDATE tblpromotions
          SET appliesto = REPLACE(appliesto, ',$tempProductId,-', '')
          WHERE appliesto REGEXP '(^|[^0-9])$origProductId([^0-9]|$)' AND appliesto REGEXP ',$tempProductId,-'");
    }

    public function transposeCustomFields($transitionalProductId, $targetProductId)
    {
        $map = Helper::conn()->select(
            'SELECT cf2.id as `from`, cf1.id as `to`
            FROM `tblcustomfields` cf1
            INNER JOIN `tblcustomfields` cf2 ON cf1.fieldname = cf2.fieldname
            WHERE cf1.type = "product" AND cf1.relid = ? AND cf2.relid = ?',
            [$targetProductId, $transitionalProductId]);

        if (!$map) {
            return;
        }

        Logger::debug('Workaround transpose custom fields', ['map' => $map]);

        foreach ($map as $row) {
            Helper::conn()->statement('UPDATE tblcustomfieldsvalues SET fieldid = ? WHERE fieldid = ?',
                [$row[ 'to' ], $row[ 'from' ]]);
        }
    }

    public function fixUpgradesRecords($serviceId, $originalProductId)
    {
        $upgrades = Helper::conn()->select(
            "SELECT * FROM `tblupgrades` WHERE relid = ? AND newvalue NOT LIKE '$originalProductId,%'",
            [$serviceId]
        );

        if (!$upgrades) {
            return;
        }

        Logger::debug('Workaround upgrades', ['records' => $upgrades]);

        foreach ($upgrades as $upgrade) {
            if (!preg_match('~^\d+,[a-z]+$~i', $upgrade['newvalue'])) {
                Logger::warn('Workaround upgrades: bad value format', [$upgrade['newvalue']]);

                continue;
            }

            Helper::db()->statement('UPDATE tblupgrades SET newvalue = ? WHERE id = ?',
                [preg_replace('~^\d+~', $originalProductId, $upgrade['newvalue']), $upgrade['id']]);
        }
    }
}
