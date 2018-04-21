<?php 

require_once dirname ( __FILE__ ) .  '/../../../../init.php';
require_once dirname ( __FILE__ ) .  '/../../../gateways/paypalbilling.php'; 
require_once dirname ( __FILE__ ) .  '/../../../../includes/gatewayfunctions.php'; 
require_once dirname ( __FILE__ ) .  '/../../../../includes/invoicefunctions.php'; 

function cancel_billing_agreement($bid, $uid) {
    $billing_cancel_query = "UPDATE `mod_paypalbilling` SET `agreement_status` =  'canceled' WHERE `id` = '$uid' AND `paypalbillingid` = '$bid' ";
    $result = mysql_query($billing_cancel_query);
	if($result) {
		$update_uset_ba_qry = "UPDATE `tblclients` SET `gatewayid` = NULL  WHERE `id` = '$uid'";
		$result2 = mysql_query($update_uset_ba_qry);
    }
	return $result2;
}

$GATEWAY = getGatewayVariables("paypalbilling"); 
$environment = ($GATEWAY['Testmode'])== 'on' ? 'sandbox' : ''; 

$ba_active = select_query("mod_paypalbilling", 'paypalbillingid, id', array('agreement_status' => "active"));
while($ba = mysql_fetch_assoc($ba_active)) {
	$nvpStr = "&REFERENCEID=".urlencode($ba['paypalbillingid'])."&BillingAgreementStatus=Canceled";
	$httpParsedResponseAr_bacancel = PPHttpPost12('BillAgreementUpdate', $nvpStr, $GATEWAY['API_USERNAME'], $GATEWAY['API_PASSWORD'],$GATEWAY['API_SIGNATURE'], $environment);
	if(strtoupper($httpParsedResponseAr_bacancel['ACK'])=='FAILURE' && $httpParsedResponseAr_bacancel['L_SHORTMESSAGE0']== "Billing%20Agreement%20was%20cancelled" && $httpParsedResponseAr_bacancel['L_ERRORCODE0'] == 10201) { //10201 - error code for billing agreement already cancelled.
		
			cancel_billing_agreement($ba['paypalbillingid'], $ba['id']);
	}
} 

//echeck code


  $echecks = get_echeck_trans();
  $adminuser = getPaypalBillingAddonConfig('whmcs_admin_username');

  foreach($echecks AS $echeck){
	  $transId = $echeck['paypal_trans_id'];
	  $nvpStr = "&TRANSACTIONID=".urlencode($transId);
	  $echeckRes = PPHttpPost12('GetTransactionDetails', $nvpStr, $GATEWAY['API_USERNAME'], $GATEWAY['API_PASSWORD'],$GATEWAY['API_SIGNATURE'], $environment);
	if(strtoupper($echeckRes['PAYMENTSTATUS']) == 'COMPLETED'){
		mark_echeck_paid($echeck['id']);
		//update_echeck_data($echeck['id'], 'paid')
		addInvoicePayment($echeck['invoice_id'], $transid, 0, 0, $paypalgatewayname);
		logTransaction("$paypalgatewayname:echeck", $echeckRes, "Successful");
	} else if(strtoupper($echeckRes['PAYMENTSTATUS']) == 'FAILED') {
		mark_echeck_failed($echeck['id']);
		$command = "updateinvoice";		
		$values["invoiceid"] = $echeck['invoice_id'];
		$values["status"] = "Unpaid";
		$values["paymentmethod"] = "$paypalgatewayname:echeck";

		$invResults = localAPI($command,$values,$adminuser);
	}
  }
  $command = "logactivity";
  $values["description"] = "MyWorks PayPal Billing Cron Job has completed successfully.";

  $results = localAPI($command,$values,$adminuser);
?>