<?php

/**
 * Hook for WHMCS of the "PayPal Emails Banlist" Module
 *
 * @author Ruslan Ivanov
 */

use WHMCS\Database\Capsule;

require dirname(__FILE__) . '/guardianAPI/GuardianApiCaller.php';

add_hook('LogTransaction', 0, function($vars) {

    if(preg_match('/paypal/i', $vars['gateway'])) {

        try {

            $gateway_data = paypal_emails_banlist_parse_payment_gateway_data($vars['data']);

            // get admin username for executing local API
            $adminUsername = Capsule::table('tbladmins')->where('disabled', 0)->where('roleid', 1)->first();

            if (empty($adminUsername)) {

                throw new Exception('No find admins in database!');

            }

            $adminUsername = $adminUsername->username;

            if (!$gateway_data) {
                logActivity('Paypal Banlist: cannot parse data ' . $vars['data']);
                return true;
            }

            if (
                (empty($gateway_data['payment_status']) or $gateway_data['payment_status'] != 'Completed') and
                true
            ) {
                logActivity('Paypal Banlist: conditions not met ' . json_encode($gateway_data));

                return true;
            }

            // parse payer email
            if (empty($gateway_data['payer_email'])) {

                throw new Exception('Error while canceled order. Not found payer email. Please manual do it!');

            }

            $email = $gateway_data['payer_email'];

            // create Caller object and check payer email in ban list
            $caller = new GuardianApiCaller();
            $result = $caller->checkEmailInBanList($email);
            $result = json_decode($result);

            if (!$result->result) {

                logActivity('Paypal Banlist: сhecked payer PayPal email - ' . $email . ' in banlist base. Not exist.');
                return true;

            }

            // parse transaction id
            preg_match('/\stxn_id => ([^\s]*)/i', $vars['data'], $transaction_id);
            if(empty($transaction_id[1])) {

                throw new Exception('Not found transaction id');

            }

            // get parent transaction
            $transaction = Capsule::table('tblaccounts')->where('transid', $gateway_data['txn_id'])->first();

            // Try to workaround no transaction is found
            if(empty($transaction)) {
                $invoice = Capsule::table('tblinvoices')
                    ->where('id', $gateway_data['custom'])
                    ->where('status', 'Unpaid')
                    ->first();
                if (!empty($invoice)) {
                    $transaction = (object) [
                        'invoiceid' => $invoice->id,
                        'transid' => $gateway_data['txn_id']
                    ];
                }

                if (empty($transaction)) {
                    $found_key = '';
                    foreach (array_keys($gateway_data) as $key) {
                        if (false !== stripos($key, 'Invoice Found')) {
                            $found_key = $key;
                            break;
                        }
                    }

                    if ($found_key and !empty($gateway_data[$found_key])) {
                        $invoice = Capsule::table('tblinvoices')
                            ->where('id', $gateway_data[$found_key])
                            ->where('status', 'Unpaid')
                            ->first();

                        if (!empty($invoice)) {
                            $transaction = (object) [
                                'invoiceid' => $invoice->id,
                                'transid' => $gateway_data['txn_id']
                            ];
                        }
                    }
                }

                // Still empty, throw an exception
                if (empty($transaction)) {
                    throw new Exception("Not found parent transaction \"{$gateway_data['txn_id']}\" in database." .
                        ' ' . json_encode($gateway_data));
                }

            }

            // get services
            $services = Capsule::table('tblinvoiceitems')->where('invoiceid', $transaction->invoiceid)->get();

            // get order
            $order = Capsule::table('tblorders')->where('invoiceid', $transaction->invoiceid)->first();

            // services id for suspend action
            $suspend_ids = [];
            foreach ($services as $service) {

                if($service->type == 'Upgrade') {

                    $service = Capsule::table('tblupgrades')->find($service->relid);

                }

                $suspend_ids[] = $service->relid;

            }

            $services = Capsule::table('tblhosting')->whereIn('id', $suspend_ids)
                ->whereIn('domainstatus', ['Active', 'Completed'])->get();

            // cancel order and set invoice status is "Refunded"
            $api_result = localAPI('CancelOrder', ['orderid' => $order->id], $adminUsername);
            $query_result = Capsule::table('tblinvoices')->where('id', $transaction->invoiceid)->update(['status' => 'Collections']);

            foreach ($services as $service) {

                $postData = [
                    'accountid' => $service->id,
                    'suspendreason' => 'Suspended by reason: Payer email in Pay Pal ban list',
                ];
                $result = localAPI('ModuleSuspend', $postData, $adminUsername);

                if($result['result'] = 'success') {

                    logActivity('Suspended Product. Service ID: ' . $service->id .
                        ' in Order ID: ' . $order->id . ' , Invoice ID: ' . $transaction->invoiceid . ' .
                         Reason: Payer email in Pay Pal ban list');

                } else {

                    logActivity('Error in "PayPal Emails Banlist" Module! Error while suspending Product.
                     Please manual do it! Service ID: ' . $service->id . ' in Order ID: ' . $order->id .
                        ' , Invoice ID: ' . $transaction->invoiceid . ' . Reason: Payer email in Pay Pal ban list');

                }
            }

            // refund payment
            $refund_result = paypal_emails_banlist_refund($transaction->transid, $order, $vars, $adminUsername);
            if(!$refund_result) {

                logActivity('Error in "PayPal Emails Banlist" module. Not refund payment. Please manual do it!
                                Order ID: ' . $order->id . ' . Invoice ID: ' . $transaction->invoiceid);

            }

            // if order canceled
            if ($api_result['result'] == 'success' and $query_result) {

                // send email to user
                $email_template = Capsule::table('tbladdonmodules')->where('module', 'paypal_emails_banlist')
                    ->where('setting', 'messagename')->value('value');

                if (!empty($email_template)) {

                    $postData = [
                        'messagename' => $email_template,
                        'id' => $order->userid,
                    ];
                    $api_result = localAPI('SendEmail', $postData, $adminUsername);

                    if ($api_result['result'] == 'error') {

                        // save activity log
                        logActivity('Error while sending email in "PayPal Emails Banlist" module. Error message - ' . $api_result['message']);

                    }

                } else {

                    logActivity('Error while sending email in "PayPal Emails Banlist" module. Error message - 
                    you not setted email template in config of module');

                }

                // save activity log
                logActivity('"PayPal Emails Banlist" module. Payer PayPal email - ' . $out[1] . ' in banlist base.
                     Order Cancelled & Refunded Successfully! Order ID: ' . $order->id);

            } else {

                logActivity('"PayPal Emails Banlist" module. Not canceled Order ID: ' . $order->id . ' . Please manual cancel order!');

            }

        } catch (Exception $e) {

            logActivity('Error in "PayPal Emails Banlist" module. Message: ' . $e->getMessage());
            return true;

        }
    }
});

/**
 * @param string $transaction
 * @param object $order
 * @param array $vars
 * @param string $adminUsername
 * @return bool
 */
function paypal_emails_banlist_refund($transaction, $order, $vars, $adminUsername) {

    // get PayPal sandbox setting
    $environment = Capsule::table('tbladdonmodules')->where('module', 'paypal_emails_banlist')
        ->where('setting', 'sandbox')->value('value');
    $environment = ($environment == 'on' ? '.sandbox' : '');

    $paypal_user = Capsule::table('tblpaymentgateways')->where('gateway', 'paypal')
        ->where('setting', 'apiusername')->value('value');

    $paypal_password = Capsule::table('tblpaymentgateways')->where('gateway', 'paypal')
        ->where('setting', 'apipassword')->value('value');

    $paypal_signature = Capsule::table('tblpaymentgateways')->where('gateway', 'paypal')
        ->where('setting', 'apisignature')->value('value');

    $data = [
        'USER'          => $paypal_user,
        'PWD'           => $paypal_password,
        'SIGNATURE'     => $paypal_signature,
        'METHOD'        => 'RefundTransaction',
        'VERSION'       => '94',
        'TRANSACTIONID' => $transaction,
        'REFUNDTYPE'    => 'Full',
    ];

    // call PayPal API
    $paypal_result = curlCall("https://api-3t" . $environment . ".paypal.com/nvp", $data);

    // array of new transaction
    $newtrx = [];

    // get refund transaction id
    preg_match('/REFUNDTRANSACTIONID=([^\s&]*)/i', $paypal_result, $paypal_response);

    if (!empty($paypal_response[1])) {

        $newtrx['id'] = $paypal_response[1];

    }

    // get refund amount
    preg_match('/TOTALREFUNDEDAMOUNT=([^\s&]*)/i', $paypal_result, $paypal_response);

    if (!empty($paypal_response[1])) {

        $newtrx['amount'] = str_replace('%2e', '.', $paypal_response[1]);

    }

    // get refund result
    preg_match('/ACK=([^\s&]*)/i', $paypal_result, $paypal_response);

    if (!empty($paypal_response[1])) {

        $newtrx['result'] = $paypal_response[1];

        if ($newtrx['result'] == 'Success') {

            // saving transaction
            $postData = [
                'paymentmethod' => $vars['gateway'],
                'userid'        => $order->userid,
                'invoiceid'     => $order->invoiceid,
                'transid'       => $newtrx['id'],
                'date'          => date('Y-m-d H:i:s'),
                'description'   => 'Refund of Transaction ID ' . $transaction,
                'amountout'     => $newtrx['amount'],
                'rate'          => '1.00000',
            ];

            $results = localAPI('AddTransaction', $postData, $adminUsername);

            // only for standard PayPal gateway
            if($results['result'] = 'success' and $vars['gateway'] == 'PayPal') {

                logTransaction($vars['gateway'], $paypal_result, 'Refunded');

            }

            return true;

        } else {

            return false;

        }

    }

    return false;

}

function paypal_emails_banlist_parse_payment_gateway_data($string) {
    $data = [];
    foreach (explode("\n", $string) as $line) {
        if (preg_match('~^(.+)\s+=>\s+(.+)$~', $line, $parsed)) {
            $data[trim($parsed[1])] = trim($parsed[2]);
        }
    }

    return $data;
}