<?php

/**
 * Hook for WHMCS of "Disputes/Reversals Watcher" Module
 *
 * @author Ruslan Ivanov
 */

use WHMCS\Database\Capsule;

add_hook('LogTransaction', 3, function($vars) {

    try {

        $adminUsername = Capsule::table('tbladmins')->where('disabled', 0)->where('roleid', 1)->first();

        if(empty($adminUsername)) {

            throw new Exception('No find admins in database!');

        }

        $adminUsername = $adminUsername->username;

        if(preg_match('/paypal/i', $vars['gateway'])) {

            // parse payment status
            preg_match('/\spayment_status => ([^\s]*)/i', $vars['data'], $payment_status);

            // parse case type
            preg_match('/\scase_type => ([^\s]*)/i', $vars['data'], $case_type);

            $dispute_status = false;

            if(!empty($case_type[1])) {

                if($case_type[1] == 'dispute' or $case_type[1] == 'complaint' or $case_type[1] == 'chargeback') {

                    $dispute_status = true;

                    // parse parent transaction id
                    preg_match('/txn_id => ([^\s]*)/i', $vars['data'], $transaction_id);

                    if(empty($transaction_id[1])) {

                        throw new Exception('Not found transaction id');

                    }

                }

            }

            if(!empty($payment_status[1]) and $payment_status[1] == 'Reversed') {

                $dispute_status = true;

                // parse payment amount
                preg_match('/mc_gross => ([^\s]*)/i', $vars['data'], $mc_gross);

                if(empty($mc_gross[1]) or (float)$mc_gross[1] > 0) {

                    throw new Exception('Not found refund amount or it > 0');

                }

                // parse parent transaction id
                preg_match('/parent_txn_id => ([^\s]*)/i', $vars['data'], $transaction_id);

                if(empty($transaction_id[1])) {

                    throw new Exception('Not found transaction id');

                }

            };

            if(!$dispute_status) return true;

            // add user to PayPal banlist
            preg_match('/\spayer_email => ([^\s]*)/i', $vars['data'], $payer_email);

            if(!empty($payer_email[1])) {

                addEmailToBan("$payer_email[1]");

            }

            // get parent transaction
            $transaction = Capsule::table('tblaccounts')->where('transid', $transaction_id[1])->first();

            if(empty($transaction)) {

                throw new Exception('Not found parent transaction in database.');

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
                    'suspendreason' => 'Suspended by reason: PayPal payment reversed',
                ];
                $result = localAPI('ModuleSuspend', $postData, $adminUsername);

                if($result['result'] = 'success') {

                    logActivity('Suspended Product. Service ID: ' . $service->id .
                        ' in Order ID: ' . $order->id . ' , Invoice ID: ' . $transaction->invoiceid . ' .
                         Reason: Dispute in PayPal');

                } else {

                    logActivity('Error in "Disputes/Reversals Watcher" Module! Error while suspending Product.
                     Please manual do it! Service ID: ' . $service->id . ' in Order ID: ' . $order->id .
                        ' , Invoice ID: ' . $transaction->invoiceid . ' . Reason: Dispute in PayPal');

                }
            }

            // if order canceled
            if ($api_result['result'] == 'success' and $query_result) {

                // send email to user
                $email_template = Capsule::table('tbladdonmodules')->where('module', 'disputes_reversals_watcher')
                    ->where('setting', 'messagename')->value('value');

                if (!empty($email_template)) {

                    $postData = [
                        'messagename' => $email_template,
                        'id' => $order->userid,
                    ];
                    $api_result = localAPI('SendEmail', $postData, $adminUsername);

                    if ($api_result['result'] == 'error') {

                        // save activity log
                        logActivity('Error while sending email in "Disputes/Reversals Watcher" module. Error message - ' . $api_result['message']);

                    }

                } else {

                    logActivity('Error while sending email in "Disputes/Reversals Watcher" module. Error message - 
                    you not setted email template in config of module');

                }

                // save activity log
                logActivity('"Disputes/Reversals Watcher" module. Payer PayPal email - ' . $payer_email[1] . ' open dispute. Payment reversed.
                     Order Cancelled & Refunded Successfully! Order ID: ' . $order->id);

            } else {

                logActivity('"Disputes/Reversals Watcher" module. Not canceled Order ID: ' . $order->id . ' . Please manual cancel order!');

            }

        }

    } catch (Exception $e) {

        logActivity('Error while working "Disputes/Reversals Watcher" Module! Message: ' . $e->getMessage());
        return true;

    }

});

/**
 * Add user to PayPal ban list
 *
 * @param $email string
 * @return mixed
 */
function addEmailToBan($email)
{

    $data = [
        'user_email' => $email,
        'description' => 'Dispute in PayPal',
    ];

    return curlCall("http://66.154.116.67/api/add_to_ban", $data);

}