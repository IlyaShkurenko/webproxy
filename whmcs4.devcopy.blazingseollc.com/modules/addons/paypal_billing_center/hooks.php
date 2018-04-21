<?php 
if (!defined("WHMCS"))
    die("This file cannot be accessed directly");

////////////////////////////////////////////////
// Global function to call PayPal Billing's Config values
////////////////////////////////////////////////

function getPaypalBillingAddonConfig($name) {
	$sql = mysql_query("select value from tbladdonmodules where module='paypal_billing_center' and setting='$name'");
	$local=mysql_fetch_array($sql);
	$key=mysql_num_rows($sql);
	return $local['value'];
}

function PB_GetSystemURL() {
		require_once dirname ( __FILE__ ) .  '/../../../configuration.php';
		global $CONFIG;
		if (!empty($CONFIG['SystemSSLURL'])) {
			return trim($CONFIG['SystemSSLURL']);
		}
			return trim($CONFIG['SystemURL']);
		
}

function PB_GetReturnUrl() {
    return PB_GetSystemURL() . "/modules/gateways/callback/paypalbilling.php";
}

function myworks_get_system_url($return_ssl = FALSE, $append_admin = FALSE)
{
    global $CONFIG, $customadminpath;

    $system_url = '';
    if ($return_ssl)
    {
        if (substr($CONFIG['SystemURL'], 0, 8) == 'https://')
        {
            $system_url = $CONFIG['SystemURL'];
        }
        else
        {
            $system_url = (empty($CONFIG['SystemSSLURL']) ? $CONFIG['SystemURL'] : $CONFIG['SystemSSLURL']);
        }
    }
    else
    {
        $system_url = $CONFIG['SystemURL'];
    }

    return rtrim($system_url, '/') . (($append_admin) ? "/{$customadminpath}" : '') . '/';
}

////////////////////////////////////////////////
// Insert Admin Billing Agreement Management Page
////////////////////////////////////////////////

function hook_paypal_billing_summary_code($vars) {
    $userid = $vars['userid'];
	$result_gateway = select_query("tblclients", 'gatewayid', array('id' => $userid));
	$client = mysql_fetch_assoc($result_gateway);
	$value = $client['gatewayid'];
  	if (preg_match('/^B-/', $value)) {
	include 'admin.php';
}
}


add_hook('AdminAreaClientSummaryPage', 1, 'hook_paypal_billing_summary_code');

////////////////////////////////////////////////
// Insert Merge Tag for Billing Agreement
////////////////////////////////////////////////

function hook_paypal_billing_merge_tag($vars) {
	
    $message_names = Array(
        "First Invoice Overdue Notice",
        "Invoice Created",
		"Invoice Payment Confirmation",
		"Invoice Payment Reminder",
		"Second Invoice Overdue Notice",
		"Third Invoice Overdue Notice",
    );
	
    $merge_fields = array();
    if (in_array($vars['messagename'], $message_names)) {
        $invoice_id = mysql_real_escape_string($vars['relid']);
        $query =
            "SELECT `userid`, CONCAT_WS(' ', `firstname`, `lastname`) as 'name' ".
            "FROM `tblinvoices` ".
            "JOIN `tblclients` ON `tblinvoices`.`userid` = `tblclients`.`id` ".
            "WHERE `tblinvoices`.`id` = '" . $invoice_id ."'";
        $r = full_query($query);
        if ($r) {
			  
            $row = mysql_fetch_row($r);
            $client_id = $row[0];
            $client_name = $row[1];
			
		    $result_gateway = select_query("tblclients", 'gatewayid', array('id' => $client_id));
		    $client = mysql_fetch_assoc($result_gateway);
		    if (!empty($client['gatewayid'])) {
				
				$merge_fields['billing_agreement_id'] = $client['gatewayid'];
				
			}
		}
        }
 

    return $merge_fields;
}


add_hook('EmailPreSend', 1, 'hook_paypal_billing_merge_tag');




////////////////////////////////////////////////
// Add E-Check Window in Invoices
////////////////////////////////////////////////



function hook_paypal_billing_invoice_page($vars) {

global $CONFIG;
$filename = $vars['filename'];

if ($filename == "viewinvoice" ){
	if (getPaypalBillingAddonConfig('disableecheck') == "") {
$invoiceid = $vars['invoiceid'];

$echeck_qry = mysql_query("SELECT `id`, `invoice_id`, `paypal_trans_id`, `clear_date`, `status`  FROM `mod_paypalbilling_echeck` WHERE `invoice_id` = '$invoiceid' " );
$client_ba = mysql_fetch_assoc($echeck_qry);

if ($client_ba['status'] == "pending"){
	
	$return = array();
	$return = array("pendingReview" => "pendingReview");
	return $return;
}
}
}
}
add_hook('ClientAreaPage', 1, 'hook_paypal_billing_invoice_page');



////////////////////////////////////////////////
// Disable Invoice Creation Emails IF BA is on file
////////////////////////////////////////////////


if (getPaypalBillingAddonConfig('disableemails') == "on") {
	

	
	function hook_disable_invoice_notification($vars) {

	    // The names of the email templates that you don't want sent.
	    $message_names = Array(
	        "Invoice Created",
	    );

	    $merge_fields = array();
	    if (in_array($vars['messagename'], $message_names)) {
	        $invoice_id = mysql_real_escape_string($vars['relid']);
	        $query =
	            "SELECT `userid`, CONCAT_WS(' ', `firstname`, `lastname`) as 'name' ".
	            "FROM `tblinvoices` ".
	            "JOIN `tblclients` ON `tblinvoices`.`userid` = `tblclients`.`id` ".
	            "WHERE `tblinvoices`.`id` = '" . $invoice_id ."' and `tblinvoices`.`paymentmethod`='paypalbilling'";
	        $r = full_query($query);
	        if ($r) {
				  
	            $row = mysql_fetch_row($r);
	            $client_id = $row[0];
	            $client_name = $row[1];
				
			    $result_gateway = select_query("tblclients", 'gatewayid', array('id' => $client_id));
			    $client = mysql_fetch_assoc($result_gateway);
			    if (!empty($client['gatewayid'])) {
					
	                $merge_fields['abortsend'] = true; // don't send email
					
					$user = getPaypalBillingAddonConfig(whmcs_admin_username);
				    $command = "logactivity";
				    $adminuser = $user;
				    $values["description"] = "Blocked Invoice Created Email - Invoice ".$invoice_id." because of existing BA on file: ".$client['gatewayid']."";
 
				    $results = localAPI($command,$values,$adminuser);
					
					
	                if ($logfile) {
	                    $pid = getmypid();
	                    $logline = sprintf(
	                        "%s pre_send_email[%d]: ".
	                        "Not sending email '%s' to client %s\n",
	                        date("Y-m-d H:i:s"),
	                        $pid,
	                        $vars['messagename'],
	                        $client_name
	                    );
	                    $fh = fopen($logfile, "a");
	                    fwrite($fh, $logline);
	                    fclose($fh);
					}
					
				}
	        }
	    }

	    return $merge_fields;
	}

	add_hook("EmailPreSend", 10, "hook_disable_invoice_notification");
	
}



////////////////////////////////////////////////
// Disable Credit Card Emails
////////////////////////////////////////////////

if (getPaypalBillingAddonConfig('disablepaymentemails') == "on") {
	
	function hook_disable_creditcard_emails($vars) {
	    // If $logfile is not blank, messages about emails not sent will be
	    // logged to this file.
	   // $logfile = "/home/granite/public_html/presendemail.log";


	    // The names of the email templates that you don't want sent.
	    $message_names = Array(
	        "Credit Card Payment Confirmation",
			"Credit Card Invoice Created",
	    );

	    $merge_fields = array();
	    if (in_array($vars['messagename'], $message_names)) {
	        $invoice_id = mysql_real_escape_string($vars['relid']);
			$result = mysql_query("SELECT `userid`, CONCAT_WS(' ', `firstname`, `lastname`) as 'name' ".
		        "FROM `tblinvoices` ".
		        "JOIN `tblclients` ON `tblinvoices`.`userid` = `tblclients`.`id` ".
		        "WHERE `tblinvoices`.`id` = '".$invoice_id."' and `tblinvoices`.`paymentmethod`='paypalbilling'");
			if (mysql_num_rows($result)!=0) {
	         //   $row = mysql_fetch_row($r);
	          //  $client_id = $row[0];
	          //  $client_name = $row[1];
	                $merge_fields['abortsend'] = true; // don't send email
					
					$user = getPaypalBillingAddonConfig(whmcs_admin_username);
				    $command = "logactivity";
				    $adminuser = $user;
				    $values["description"] = "Blocked Credit Card Invoice Email for - Invoice ".$invoice_id." with PayPal Billing because it's a duplicate email notification.";
 
				    $results = localAPI($command,$values,$adminuser);
				}
		
		
		
	    }

	    return $merge_fields;
	}
	
	add_hook("EmailPreSend", 1, "hook_disable_creditcard_emails");
}

	
	/*
	function hook_disable_invoice_payment_confirmation_1($vars) {
	$merge_fields = array();

	if ($vars['messagename'] == "Invoice Payment Confirmation") {
	            $merge_fields['abortsend'] = true; // don't send email
	}

	return $merge_fields;
	}

	add_hook("EmailPreSend", 1, "hook_disable_invoice_payment_confirmation_1");
	
*/

////////////////////////////////////////////////
//Auto Remove Billing Agreement Upon Payment Method Change
////////////////////////////////////////////////

if (getPaypalBillingAddonConfig('autoremove') == "on") {

function hook_paypal_billing_center_auto_remove_payment_method($vars) {
	
    $client = Menu::context('client');
    if (!is_null($client)) {
        $user = getPaypalBillingAddonConfig(whmcs_admin_username);
        $call = localAPI(
            'updateclient',
            array(
                'clientid' => $client['id'],
                'clearcreditcard' => true,
            ),
            $user
        );
        logActivity('CC Details Removed For Client ID:' . $client['id']);
    }
}

add_hook('InvoiceChangeGateway', 1, 'hook_paypal_billing_center_auto_remove_payment_method');

}



////////////////////////////////////////////////
// Zero Checkout Hook
////////////////////////////////////////////////


function hook_paypal_billing_center_trial($params) {
	
	require_once (dirname(__FILE__)."/../../../init.php");
	require_once (dirname(__FILE__)."/../../../modules/gateways/paypalbilling.php");
	require_once (dirname(__FILE__)."/../../../includes/gatewayfunctions.php");
	
	$GATEWAY = getGatewayVariables("paypalbilling");	
	$url = PB_GetSystemURL();	
	$userid = $params['clientdetails']['userid'];
	$amount = $params['amount'];
	$paymentmethod = $params['paymentmethod'];
	$desc = 'Billing agreement creation'; // get inv desc
	$environment = $GATEWAY['Testmode'] == 'on' ? 'sandbox' : '';
	
    $result_gateway = select_query("tblclients", 'gatewayid', array('id' => $userid));
    $client = mysql_fetch_assoc($result_gateway);
	
	
	if(($amount == 0.00 && $paymentmethod == 'paypalbilling' && getPaypalBillingAddonConfig('zerocheckout') == "on" && empty($client['gatewayid']) && $_GET['zerocheckout'] !== "complete") || ($paymentmethod == 'credit' && getPaypalBillingAddonConfig('zerocheckout') == "on" && empty($client['gatewayid']) && $_GET['zerocheckout'] !== "complete")) {
		
		$currencyID = urlencode("GBP");
		$returnURL = urlencode($url . "/modules/gateways/callback/paypalbilling.php?amount=$amount&currency=$currencyID&clientid=$userid&zerocheckout=true");
		$cancelURL = urlencode($url . "/cart.php?a=complete");
		
		//redirect to paypal for billing agreement creation.
			$nvpStr = "&MAXAMT=$amount&Amt=$amount&RETURNURL=$returnURL&CANCELURL=$cancelURL&CURRENCYCODE=$currencyID&BUTTONSOURCE=MyWorksDesign_SI_Custom&DESC=" . urlencode($desc) . "&L_BILLINGTYPE0=MerchantInitiatedBilling&L_BILLINGAGREEMENTDESCRIPTION0=" . urlencode($desc) . "&page_style=" . urlencode($GATEWAY['Paypal_Custom_Page']);

	        $httpParsedResponseAr = PPHttpPost12('SetExpressCheckout', $nvpStr.$clientContactInfoStrDoRef, $GATEWAY['API_USERNAME'], $GATEWAY['API_PASSWORD'],$GATEWAY['API_SIGNATURE'], $environment);
			

			if ("SUCCESS" == strtoupper($httpParsedResponseAr["ACK"]) || "SUCCESSWITHWARNING" == strtoupper($httpParsedResponseAr["ACK"])) {
	            $token = urldecode($httpParsedResponseAr["TOKEN"]);
				

					$paypal_url = "https://www.paypal.com/webscr";
		
	            if ("sandbox" === $environment || "beta-sandbox" === $environment) {
	                $paypal_url = "https://www.$environment.paypal.com/webscr";
	            }
	

					
				$redirectURL=$paypal_url."?token=$token&cmd=_express-checkout&page_style=".$GATEWAY['Paypal_Custom_Page'];
			}

			header("Location: $redirectURL");
	}
}


add_hook('InvoiceCreation', 1, 'hook_paypal_billing_center_trial');
add_hook('ShoppingCartCheckoutCompletePage', 1, 'hook_paypal_billing_center_trial');	