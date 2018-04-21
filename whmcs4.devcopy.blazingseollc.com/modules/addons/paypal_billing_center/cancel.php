<?php
/*
*************************************************************************
*                                                                       *
*  Start MyWorks Code				         					        *
* Copyright (c) 2007-2014 MyWorks Design. All Rights Reserved,          *
* PayPal Billing Center Coding                                          *
* Last Modified: 24th May 2014                                          *
*                                                                       *
*************************************************************************
*/ 


$auto_sub_cancellation_qry = mysql_query("SELECT value FROM `tbladdonmodules` WHERE module = 'paypal_billing_center' AND setting LIKE 'autocancel'");
$auto_cancel = mysql_fetch_assoc($auto_sub_cancellation_qry);
if($auto_cancel['value']=='on') {
	cancelSubscription($userid); 
}

// This function will check if a valid subscription ID exists on cancellation
function cancelSubscription($userid) {
	if(!$userid) {
		return false;
	}
	// Check if we have a subscription ID entered in WHMCS:
	$q = "SELECT id AS relid, subscriptionid FROM tblhosting WHERE userid = {$userid}  AND subscriptionid != '' AND paymentmethod = 'paypal'";
	$r = mysql_query($q) or die("Error in query " . mysql_error());
	// If we do, cancel it in PayPal:
	if (mysql_num_rows($r) > 0) {

		while($row = mysql_fetch_assoc($r)) {
			$subscriptionid = $row['subscriptionid']; 
			$relid = $row['relid'];
			// Do PayPal Cancellation
			cancelSubscriptionAPI($subscriptionid, $userid, $relid);
		}
	}
	else {
		logactivity("MYWORKS DEBUG: No PayPal Subscription ID detected for this service - not attempting to cancel.");
	}
}

// This function is used to call the PayPal function with cancellation parameters
// id in tblhosting is relid here
function cancelSubscriptionAPI($subscriptionid, $userid, $relid) {
	$urlsubscriptionid = urlencode($subscriptionid);
	$nvpStr="ProfileID=$urlsubscriptionid&Action=Cancel&Note=Automated+cancellation.";
	$httpParsedResponseAr = paypalCURLL('ManageRecurringPaymentsProfileStatus', $nvpStr);
	if("SUCCESS" == strtoupper($httpParsedResponseAr["ACK"]) || "SUCCESSWITHWARNING" == strtoupper($httpParsedResponseAr["ACK"])) {

		//remove subcription id
		$update_qry = "UPDATE tblhosting SET subscriptionid = '' WHERE id = $relid";

		try {
		   $status = mysql_query($update_qry);
		   if(!$status) {
			   $error = mysql_error();
				throw new Exception('Update subscriptionid to null is not successful :'.$error);
		   }
		} catch (Exception $e) {
		    echo 'Caught exception: ',  $e->getMessage(), "\n";
		}
		// Log success to WHMCS activity log
		logactivity("MYWORKS DEBUG: Successfully terminated Paypal Subscription ID: $subscriptionid attached to Service ID: $relid and User ID: $userid");
	} 
	else {
		// Build failure message
		foreach ($httpParsedResponseAr as $key => $value) {
			$message .= "Key: $key - Value: $value. ";
		}
		// Log failure to WHMCS activity log
		logactivity("MYWORKS DEBUG: Failed to terminate Paypal Subscription ID: $subscriptionid attached to Service ID: $relid and User ID: $userid. Response was $message.");
	}
}

//if(!function_exists('paypalCURL')) {
// This is a generic PayPal API function
function paypalCURLL($methodName_, $nvpStr_) {

	$paypal_api_query = "SELECT setting,value FROM `tbladdonmodules` WHERE module = 'paypal_billing_center' AND setting LIKE 'paypal_api_%'";
	$result = mysql_query($paypal_api_query);
	while ( $row = mysql_fetch_array($result) ) {
		if ($row['setting'] == 'PAYPAL_API_USERNAME')
		$username = $row['value'];
		else if ($row['setting'] == 'PAYPAL_API_PASSWORD')
		$password = $row['value'];
		else if ($row['setting'] == 'PAYPAL_API_SIGNATURE')
		$signature = $row['value'];
	}
	$API_UserName = urlencode($username);
	$API_Password = urlencode($password);
	$API_Signature = urlencode($signature);
	$API_Endpoint = "https://api-3t.paypal.com/nvp";
	$version = urlencode('51.0');
	// setting the curl parameters.
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $API_Endpoint);
	curl_setopt($ch, CURLOPT_VERBOSE, 1);
	// turning off the server and peer verification(TrustManager Concept).
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_POST, 1);
	// NVPRequest for submitting to server
	$nvpreq = "METHOD=$methodName_&VERSION=$version&PWD=$API_Password&USER=$API_UserName&SIGNATURE=$API_Signature&$nvpStr_";
	// setting the nvpreq as POST FIELD to curl
	curl_setopt($ch, CURLOPT_POSTFIELDS, $nvpreq);
	// getting response from server
	$httpResponse = curl_exec($ch);
	if(!$httpResponse) {
		exit("$methodName_ failed: ".curl_error($ch).'('.curl_errno($ch).')');
	}
	$httpResponseAr = explode("&", $httpResponse);
	$httpParsedResponseAr = array();
	foreach ($httpResponseAr as $i => $value) {
		$tmpAr = explode("=", $value);
		if(sizeof($tmpAr) > 1) {
			$httpParsedResponseAr[$tmpAr[0]] = $tmpAr[1];
		}
	}
	if((0 == sizeof($httpParsedResponseAr)) || !array_key_exists('ACK', $httpParsedResponseAr)) {
		exit("Invalid HTTP Response for POST request($nvpreq) to $API_Endpoint.");
	}
	return $httpParsedResponseAr;
}

//}

