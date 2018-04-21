<?php 
/*
*************************************************************************
*                                                                       *
*  Start MyWorks Code				        					        *
* Copyright (c) 2007-2015 MyWorks Design. All Rights Reserved,          *
* PayPal Billing Center Coding                                          *
* Last Modified: 15 Dec 2014                                            *
*                                                                       *
*************************************************************************
*/
if (!defined("WHMCS"))
    die("This file cannot be accessed directly");

///////////////// VERSION NUMBER /////////////////////
$version = '2.5'; 
/////////////////////////////////////////////////////

function get_paypal_billing_center_version_data($modName, $xmlUrl = 'http://myworks.design/moduleversion.xml')
{

    $xml = simplexml_load_file($xmlUrl); // Check the latest version
    $res = new StdClass();
    // print_r($xml->children()>module);
    if ($xml) {
        foreach ($xml->children() as $addon) // children hierarchy : xml->module->addon
            {
            if (($modName == $addon->addon->name[0])) {
                $res->name    = $addon->addon->name[0];
                $res->version = $addon->addon->version[0];
            }
        }
    }

    return $res;
}	

function paypal_billing_get_admin_username_list(){
  $subsqry = full_query("select `username` from tbladmins where disabled = 0");
  $au_arr = array();
  while ($au = mysql_fetch_assoc($subsqry)) {
    $au_arr[] = $au['username'];
  }

  $au_str = '';
  if(count($au_arr)){
    $au_str = implode(',', $au_arr);
  }
  return $au_str;
  
}


function check_reference_status() {
	require_once (dirname(__FILE__)."/../../../modules/gateways/paypalbilling.php");
	require_once (dirname(__FILE__)."/../../../includes/gatewayfunctions.php");

    $description = "Billing Agreement Test";
	$GATEWAY = getGatewayVariables("paypalbilling");
   	$environment = ($GATEWAY['Testmode']) == 'on' ? 'sandbox' : '';

	$returnURL = "http://myworks.design";
	$cancelURL = "http://myworks.design";


    $nvpStr = "&MAXAMT=25&Amt=0&L_BILLINGTYPE0=MerchantInitiatedBilling&L_BILLINGAGREEMENTDESCRIPTION0=$description&RETURNURL=$returnURL&CANCELURL=$cancelURL";
    $httpParsedResponseAr = PPHttpPost12('SetExpressCheckout', $nvpStr, $GATEWAY['API_USERNAME'], $GATEWAY['API_PASSWORD'], $GATEWAY['API_SIGNATURE'], $environment);

        if ("SUCCESS" == strtoupper($httpParsedResponseAr["ACK"]) || "SUCCESSWITHWARNING" == strtoupper($httpParsedResponseAr["ACK"])) {
			
		    $query2 = "UPDATE tbladdonmodules set value='on' WHERE module='paypal_billing_center' and setting='referencestatus'";
		    $result = full_query($query2);
			$status = 'on';		
	}else{
		
	    $query2 = "UPDATE tbladdonmodules set value='off' WHERE module='paypal_billing_center' and setting='referencestatus'";
	    $result = full_query($query2);	
		$status = 'off';	
	}
	return $status;
}



function paypal_billing_center_config() {
	
	 $paypal_billing_get_admin_username_list = paypal_billing_get_admin_username_list();
	 
     $sql         = mysql_query("select value from tbladdonmodules where module='paypal_billing_center' and setting='referencestatus'");
     $local       = mysql_fetch_array($sql);
     $referencestatus = $local['value'];
	 
     if ($referencestatus == "on") {
         $referencestatus = "<span class=\"label active\">Enabled</span>";
     } else {
         $referencestatus = "<span class=\"label closed\">Not Enabled</span>";
     }

    $sql         = mysql_query("select value from tbladdonmodules where module='paypal_billing_center' and setting='isactivated'");
    $local       = mysql_fetch_array($sql);
    $isactivated = $local['value'];
    
    global $version;
    $addon = get_paypal_billing_center_version_data('MyWorks PayPal Billing Agreements Module');
    if (isset($addon->version)) {
        if (($version < $addon->version)) {
            $version_check = "<a href='https://myworks.design/account/clientarea.php?action=products'><span class=\"label active\">New Version Available!</span></a>";
        } else {
            $version_check = "<a href='https://myworks.design/account/clientarea.php?action=products'><span class=\"label expired\">Up To Date!</span></a>";
        }
        
    }

    $configarray = array(
        "name" => "MyWorks PayPal Billing Agreements Module",
        "description" => "Charge your PayPal customers on-demand instead of having to rely on subscriptions or one time payments! Easily charge them on-demand, when an invoice is due or on the run of your cron - all automated with this module!</br></br>
			
			<b>Reference Transactions Status:</b> $referencestatus </br>
		<a href='addonmodules.php?module=paypal_billing_center&action=status'><span class=\"label expired\">Check Again</span></a>",
        "version" => "$version",
        "author" => "<img src=\"../modules/addons/paypal_billing_center/img/logo.png\"> </br> $version_check $isactivated",
        "language" => "english",
        "fields" => array( 
  //License Key Field
        "licensekey" => array ("FriendlyName" => "License Key", "Type" => "text", "Size" => "25", "Description" => "Enter Your License Key Here", ), 

  //Set Gateway as Default Checkbox
		"autodefault" => array("FriendlyName" => "Automatically Set As Default", "Type" => "yesno", "Description" => "Check to change client's Profile > Default Payment Method AND Products > Payment Method to this gateway after paying for the first time with our gateway or signing up for a billing agreement. Helpful in making sure clients migrating to this form of payment stay using this form of payment.",), 
		
	    //Set Gateway as Default Checkbox
	  		"autoremove" => array("FriendlyName" => "Automatically Remove BA Details", "Type" => "yesno", "Description" => "Check to have our module automatically remove a Billing Agreement from on file with a client if they select a different method of payment on an invoice.",), 
			
			"whmcs_admin_username" => array(
				"FriendlyName" => "WHMCS Admin User",
				"Type" => "dropdown",
				"Options" => "$paypal_billing_get_admin_username_list",
				"Description" => "Select Admin User with full privileges, will be used for API calls."
			),
		
  //Auto-Cancel PayPal Subscriptions Checkbox
		"autocancel" => array("FriendlyName" => "AutoCancel PayPal Subscriptions", "Type" => "yesno", "Description" => "Check to automatically have client's subscriptions canceled when they pay with this gateway and setup a billing agreement.",), 
		
  //Auto-Cancel PayPal Username Field
		"PAYPAL_API_USERNAME" => array("FriendlyName" => "AutoCancel Paypal API Username", "Type" => "text", "Size" => "30", "Description" => "If the AutoCancel checkbox is checked above, this API information should be the API information to the PayPal account all the subscriptions are being paid to."), 
		  
  //Auto-Cancel PayPal Password Field
	    "PAYPAL_API_PASSWORD" => array("FriendlyName" => "AutoCancel Paypal API Password", "Type" => "text", "Size" => "30", "Description" => "If the AutoCancel checkbox is checked above, this API information should be the API information to the PayPal account all the subscriptions are being paid to."), 
	
 //Auto-Cancel PayPal Signature Field
	    "PAYPAL_API_SIGNATURE" => array("FriendlyName" => "AutoCancel Paypal API Signature", "Type" => "text", "Size" => "70", "Description" => "If the AutoCancel checkbox is checked above, this API information should be the API information to the PayPal account all the subscriptions are being paid to."),  
	
 //Custom Return URL Field
		"CUSTOM_RETURN_URL" => array("FriendlyName" => "Custom Return URL", "Type" => "text", "Size" => "70", "Description" => "Enter a URL in this box for clients to be redirected to after they successfully check out with this module."),
		
//Add Option for One Time Payment
		"addonetime" => array("FriendlyName" => "Add Button for One-Time Payment", "Type" => "yesno", "Description" => "Give your client the option to choose between setting up a billing agreement or simply completing a one-time payment for the order/invoice.",),
		
//Make Paypal Account Optional
		"paypalaccountoptional" => array("FriendlyName" => "Make PayPal Account Optional", "Type" => "yesno", "Description" => "This only works for clients using the one-time payment button above - and PayPal Account Optional must be ON in your PayPal Settings. This allows your clients to pay with a card without creating a PayPal Account.",),
		

//Pass WHMCS user details to PayPal
		"strictuserdetails" => array("FriendlyName" => "Strict User Details", "Type" => "yesno", "Description" => "This only works for clients using the one-time payment button above - and PayPal Account Optional must be ON in your PayPal Settings. This allows your clients to pay with a card without creating a PayPal Account.",),

//Add Option for One Time Payment
		"prefillemail" => array("FriendlyName" => "Pre-Fill user email in PayPal Login Form", "Type" => "yesno", "Description" => "Check this box to enable the pre-filling of your client's email address on file into the PayPal Login Form when they check out for the first time using our gateway.",),
	
//In-Context Checkout?
	"incontextcheckout" => array("FriendlyName" => "Enable In-Context Checkout?", "Type" => "yesno", "Description" => "Check To Enable New In-Context Checkout Style",),
	
//Set $0 Checkout Option
		"zerocheckout" => array("FriendlyName" => "Set Billing Agreement Up on $0 Checkout", "Type" => "yesno", "Description" => "Check to still redirect to PayPal and set up Billing Agreement even if the cart total is $0.00. This is PERFECT for Free Trial scenarios to immediately have the ID on file to charge them when the trial has ended.",),
		
//Set PayPal Fees Option		
		"addpaypalfees" => array("FriendlyName" => "Add PayPal Fees - Discreet Mode", "Type" => "yesno", "Description" => "Check to add PayPal fees to your client's final total after they are sent to PayPal. This amount will not show in the WHMCS order form or invoice.",),

//Set PayPal Fees Option		
		"showpaypalfees" => array("FriendlyName" => "Add PayPal Fees - Transparent Mode", "Type" => "yesno", "Description" => "Check to add PayPal Fees as a line item in the invoice instead of just a line item in the PayPal Transaction. This makes the PayPal transaction more visible to your clients. This option only valid if the above option is turned on.",),
						
//Custom Return URL Field
	   	"paypalfee_percent" => array("FriendlyName" => "Fee Percentage", "Type" => "text", "Size" => "5","Description" => "If the option above is turned on, enter the Transaction Fee percentage you'd like to assess to transactions through this gateway. <b>Format: Decimal. Example: \".029\" for 2.9%."),		
		
//Custom Return URL Field
		"paypalfee_transaction" => array("FriendlyName" => "Fee Amount", "Type" => "text", "Size" => "5", "Description" => "If the option above is turned on, enter the Per Transaction Amount  you'd like to assess to transactions through this gateway."),		
				
//Disable Invoice Created Notification If ID on file
		"disableemails" => array("FriendlyName" => "Disable Invoice Created Emails IF Billing Agreement on file", "Type" => "yesno", "Description" => "Check To disable the 'Invoice Created' email for invoices with this gateway as the payment method for clients who have a billing agreement ID on file. Since your clients are on a recurring basis, it's been found a lot easier to not send them invoices that will be automatically captured.",),
		
//Disable Credit Card Payment Confirmation Notification
		"disablepaymentemails" => array("FriendlyName" => "Disable Credit Card Emails", "Type" => "yesno", "Description" => "Check To Disable 'Credit Card Payment Confirmation' and 'Credit Card Invoice Created' emails from being sent to clients who have invoices with this gateway. The 'Invoice Payment Confirmation' and 'Invoice Created' email templates are still sent - so this is recommended to be turned off so clients don't receive duplicate confirmation emails because of the nature of this gateway being both a one-time and recurring gateway.",),

//Disable E-Check Option
		"disableecheck" => array("FriendlyName" => "Advanced E-Check Support", "Type" => "yesno", "Description" => "Turn this on to mark invoices that are paid with an E-Check as PAID when the transaction happens. Keep the box unchecked to wait unti the E-Check clears to mark the invoice as paid.",),

//Disable Sidebar in Client Area
		"disablesidebar" => array("FriendlyName" => "Disable Sidebar on PayPal Billing page", "Type" => "yesno", "Description" => "Turn this on disable the sidebar on your paypalbilling.php client area page.",),
		
//Only Show One-Time Payment Button for Client Group
		"onetimepaymentgrouplimit" => array("FriendlyName" => "Show One Time Payment Button for Client Group", "Type" => "text", "Size" => "5", "Description" => "Enter the ID for your client group you want to show the one-time payment button for in the invoice screen.",),
		
//Only Show One-Time Payment Button for Client Group
		"disablecartbutton" => array("FriendlyName" => "Disable the PayPal Button on the Checkout Complete Page", "Type" => "yesno", "Description" => "Check to disable the PayPal button showing on the \"Please wait while you are redirected to the gateway you chose to make payment\" page. PRO TIP: You can reduce the delay time that this page waits before sending a user to PayPal by adjusting the value in templates/youractivetemplate/forwardpage.tpl.",),

        )
    );
    return $configarray; 
	

	  
}

function paypal_billing_center_activate() {
			$query = "CREATE TABLE IF NOT EXISTS `mod_paypalbilling` (
			          `b_num` INT(11) NOT NULL AUTO_INCREMENT,
			          `id` INT(1) NOT NULL,
			          `paypalbillingid` TEXT NOT NULL,
			          `agreement_status` ENUM('active','canceled') NOT NULL DEFAULT 'active',
			          PRIMARY KEY (`b_num`) );";
		    $result = full_query($query);
			$echeckQry = "CREATE TABLE IF NOT EXISTS `mod_paypalbilling_echeck` (
						  `id` int(11) NOT NULL AUTO_INCREMENT,
						  `invoice_id` int(11) NOT NULL,
						  `paypal_trans_id` varchar(255) NOT NULL,
						  `clear_date` date DEFAULT NULL,
						  `status` enum('paid',  'pending',  'denied',  'completed') NOT NULL,
						  PRIMARY KEY (`id`)
						)";
		    $result = full_query($echeckQry);
			
	 #Activate Gateway  
		    $query0 = "DELETE FROM tblpaymentgateways WHERE gateway='paypalbilling';";
		    $result = full_query($query0);
		    $query1 = "INSERT INTO `tblpaymentgateways` (`gateway`, `setting`, `value`, `order`)
					  VALUES ('paypalbilling', 'name', 'MyWorks Paypal Billing Module', '0'),
					  ('paypalbilling', 'type', 'CC', '0'),
					  ('paypalbilling', 'visible', 'on', '0');";
		    $result = full_query($query1);
			
   		 #Activate Module  
   				    $query2 = "INSERT INTO `tbladdonmodules` (`module`, `setting`, `value`) VALUES ('paypal_billing_center', 'access', '1,2,3,4,5,6,7,8,9,10');";
   				    $result = full_query($query2);
					
			   	 #Activate Module  
			   			    $query2 = "INSERT INTO `tbladdonmodules` (`module`, `setting`, `value`) VALUES ('paypal_billing_center', 'enablegateway', '');";
			   			    $result = full_query($query2);
			
	 #Activate Module  
			    $query2 = "INSERT INTO `tbladdonmodules` (`module`, `setting`, `value`) VALUES ('paypal_billing_center', 'isactivated', '<span class=\"label active\">Licensed</span>');";
			    $result = full_query($query2);
							
				

    return array('status' => 'success', 'description' => 'You have successfully installed the MyWorks PayPal Billing Module! Go to Setup > Payments > Payment Gateways > MyWorks PayPal Billing Module to configure the gateway, and Addons > MyWorks PayPal Billing for more features!');   

	return array('status'=>'error','description'=>'There was an error activating the module.');
}

function paypal_billing_center_deactivate() {

	mysql_query ("DELETE FROM tblpaymentgateways WHERE gateway='paypalbilling';");
    return array('status' => 'success', 'description' => 'You have successfully uninstalled the MyWorks PayPal Billing Center gateway!');
	return array('status'=>'error','description'=>'There was an error de-activating the module.');
}

function paypal_billing_center_upgrade($vars) {
	
	global $version;
 
    $version == $vars['version'];
    # By default, version_compare() returns -1 if the first version is lower than the second, 0 if they are equal, and 1 if the second is lower.
	# if (version_compare($version, '1.7.1') < 0) {  

    # Run SQL Updates to upgrade to V1.3
    if ($version < 1.3) {
        $query = "UPDATE `tblclients` INNER JOIN `tblcustomfieldsvalues` ON `id` = `relid` SET `gatewayid` = `value` WHERE `value` LIKE 'B-%';";
    	$result = mysql_query($query);
		$query1 = "UPDATE `tblclients` SET `gatewayid` = REPLACE (`gatewayid`, '%2d', '-') WHERE `gatewayid` LIKE 'B-%';";
    	$result = mysql_query($query1);   	  
    } 

	# Run SQL Updates to upgrade to V1.5
    if ($version < 1.5) {
         $query = "CREATE TABLE `mod_paypalbilling` (`b_num` INT(11) NOT NULL AUTO_INCREMENT, `id` INT(1) NOT NULL, `paypalbillingid` TEXT NOT NULL, `agreement_status` ENUM('active','canceled') NOT NULL DEFAULT 'active', PRIMARY KEY (`b_num`) );";
	    $result = mysql_query($query);
		$query4 = "INSERT INTO `mod_paypalbilling` (id, paypalbillingid)
		SELECT tblclients.id, tblclients.gatewayid
		FROM tblclients
		WHERE `gatewayid` LIKE 'B-%';";
	    $result = mysql_query($query4);
    }

	# Run SQL Updates to upgrade to V1.9
    if ($version < 1.9) {
        $query2 = "INSERT INTO `tbladdonmodules` (`module`, `setting`, `value`) VALUES ('paypal_billing_center', 'isactivated', '<span class=\"label active\">Licensed</span>');";
	    $result = full_query($query2);
    }
	
	# Run SQL Updates to upgrade to V2.1
    if ($version < 2.1) {
        $query3 = "CREATE TABLE IF NOT EXISTS `mod_paypalbilling_echeck` (
						  `id` int(11) NOT NULL AUTO_INCREMENT,
						  `invoice_id` int(11) NOT NULL,
						  `paypal_trans_id` varchar(255) NOT NULL,
						  `clear_date` date DEFAULT NULL,
						  `status` enum('paid','pending') NOT NULL,
						  PRIMARY KEY (`id`)
						)";
	    $result = full_query($query3);
    }
	
    if ($version < 2.4) {
        //Alter E-Check Table
		$query = "ALTER TABLE  `mod_paypalagreement_echeck` CHANGE  `status`  `status` ENUM(  'paid',  'pending',  'denied',  'completed' )";
	    $result = full_query($query);
		
        //Alter E-Check Table
		$query = "ALTER TABLE  `mod_paypalagreement_echeck` ADD  `clear_date` date DEFAULT NULL";
	    $result = full_query($query);
		
		//Remove Admin Cron File Page
		$file_with_path = "../modules/addons/paypal_billing_center/cron.php";
		if (file_exists($file_with_path)) {
		  unlink($file_with_path);
		}
		//Remove Cron File
		$file_with_path1 = "../crons/paypalbilling.php";
		if (file_exists($file_with_path1)) {
		  unlink($file_with_path1);
		} 	
        //Insert new button image
		$query = "INSERT INTO `tblpaymentgateways` (`gateway`, `setting`, `value`, `order`) VALUES ('paypalbilling', 'RECURRING_BUTTON_IMAGE', 'https://www.paypalobjects.com/en_US/i/btn/btn_xpressCheckout.gif?akam_redir=1', 0)";
	    $result = full_query($query);
		

			
    } 
}

function getPayPalGatewayDetails() {

    //require_once("..".DIRECTORY_SEPARATOR."includes/gatewayfunctions.php");
	require_once (dirname(__FILE__)."/../../../includes/gatewayfunctions.php");

    #  include("../includes/invoicefunctions.php");
    $gatewaymodule = "paypalbilling"; # Gateway Module Name
    $GATEWAY = getGatewayVariables($gatewaymodule);
    return $GATEWAY;
}


function getCustomFieldValues($relid_array, $custom_field_name_array, $custom_field_type) {

    if (!count($relid_array)) {

        $relid_array = array(0);
    }

    $relids = "(" . implode(",", $relid_array) . ")";
    $custom_field_names = "('" . implode("','", $custom_field_name_array) . "')";
    $custom_field_value_table_query = "SELECT `tblcustomfieldsvalues`.`value`,
        
        `tblcustomfieldsvalues`.`relid`
        
        FROM `tblcustomfields` INNER
        
        JOIN `tblcustomfieldsvalues` ON 
        
        `tblcustomfields`.`id`=`tblcustomfieldsvalues`.`fieldid`
        
        WHERE `tblcustomfieldsvalues`.`relid` IN $relids
            
        AND `tblcustomfields`.`fieldname` IN  $custom_field_names AND `tblcustomfields`.`type`='$custom_field_type';";

    $customFieldResults = mysql_query($custom_field_value_table_query) or die(mysql_error() . " Error in getCustomFieldValues function ");

    $customfields = array();

    while ($row = mysql_fetch_assoc($customFieldResults)) {

        $customfields[$row['relid']] = $row['value'];
    }
    return $customfields;
}

if(!function_exists('mysql_select')) {
	function mysql_select($query) {

		$data = array();

		$result = mysql_query($query) or die(mysql_error());

		while ($row = mysql_fetch_assoc($result)) {

			$data[] = $row;
		}
		return $data;
	}
}

function call_to_paypal_for_charge($billingid, $GATEWAY, $amount, $comment) {

//    print_r(get_defined_vars());
//    exit;

    $environment = "";

    $desc = urlencode($GATEWAY['companyname'] . " - " . $comment);

    if ($GATEWAY['Testmode'])
        $environment = "sandbox";

    $API_UserName = urlencode($GATEWAY['API_USERNAME']);

    $API_Password = urlencode($GATEWAY['API_PASSWORD']);

    $API_Signature = urlencode($GATEWAY['API_SIGNATURE']);

    $API_Endpoint = "https://api-3t.paypal.com/nvp";

    if ("sandbox" === $environment || "beta-sandbox" === $environment) {
        $API_Endpoint = "https://api-3t.$environment.paypal.com/nvp";
    }
    $version = '63.0';
    $cur_code = urlencode($GATEWAY['currency']);
    $nvpStr_ = "&REFERENCEID=$billingid&PAYMENTACTION=SALE&AMT=$amount&CURRENCYCODE=$cur_code&BUTTONSOURCE=MyWorksDesign_SI_Custom&DESC=$desc";


   $resultarray_finalb = $httpParsedResponseArb = PPHttpPost12('BillAgreementUpdate', $nvpStr_, $API_UserName, $API_Password , $API_Signature,$environment);


$nvpStr_ .=  "&SHIPTONAME=$httpParsedResponseArb[FIRSTNAME]&SHIPTOSTREET=$httpParsedResponseArb[STREET]&SHIPTOSTREET2=$httpParsedResponseArb[STREET2]&SHIPTOCITY=$httpParsedResponseArb[CITY]&SHIPTOSTATE=$httpParsedResponseArb[STATE]&SHIPTOZIP=$httpParsedResponseArb[ZIP]&SHIPTOCOUNTRY=$httpParsedResponseArb[COUNTRY]&REQCONFIRMSHIPPING=1";

    // logActivity('MyWorks Billing capture: ' . json_encode(['params' => $_GET, 'trace' => debug_backtrace()]));

    $nvpreq = "METHOD=DoReferenceTransaction&VERSION=$version&PWD=$API_Password&USER=$API_UserName&SIGNATURE=$API_Signature$nvpStr_";
    #exit($API_Endpoint);
    #echo "$API_Endpoint?$nvpreq";
    // Set the curl parameters.
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "$API_Endpoint");
    curl_setopt($ch, CURLOPT_VERBOSE, 0);

    // Turn off the server and peer verification (TrustManager Concept).
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    // Set the API operation, version, and API signature in the request.
    // Set the request as a POST FIELD for curl.
    curl_setopt($ch, CURLOPT_POSTFIELDS, $nvpreq);
    // Get response from the server.
    $httpResponse = curl_exec($ch);

    #exit($httpResponse);
    if (!$httpResponse) {
        exit("$methodName_ failed: " . curl_error($ch) . '(' . curl_errno($ch) . ')');
    }

    // Extract the response details.
    $httpResponseAr = explode("&", $httpResponse);
    #print_r($httpResponseAr);
    #exit;
    $httpParsedResponseAr = array();
    foreach ($httpResponseAr as $i => $value) {
        $tmpAr = explode("=", $value);
        if (sizeof($tmpAr) > 1) {
            $httpParsedResponseAr[$tmpAr[0]] = urldecode($tmpAr[1]);
        }
    }

    if ((0 == sizeof($httpParsedResponseAr)) || !array_key_exists('ACK', $httpParsedResponseAr)) {
        exit("Invalid HTTP Response for POST request($nvpreq) to $API_Endpoint.");
    }

    return $httpParsedResponseAr;
}

function paypal_billing_center_output($vars) {
$LANG = $vars['_lang'];
include('menubar.php');
	

    if (isset($_POST['charge_client'])) {

        if ($_POST[clientid] != null) {

            if ($_POST['Invoice_mail'] == 1) {
                $option = true;
            } else {
                $option = "";
            }
            if ($_POST['Payment_confirmation'] == 1) {
                $perform = "";
            } else {
                $perform = true;
            }

            $comment = $_POST['comments'];
            $amount = (float) $_POST['amount'];
            $gateway_details = getPayPalGatewayDetails();
            /*$billing_id_query = "SELECT `paypalbillingid` FROM mod_paypalbilling WHERE id=$_POST[clientid] AND agreement_status='active'";
            $result = mysql_query($billing_id_query);
            $billingid_in_array = mysql_fetch_assoc($result);*/
			$bi_query = "SELECT gatewayid FROM tblclients WHERE `id` = ".$_POST['clientid'];
			$gatewayqry = mysql_query($bi_query); 
			$gatewayid = mysql_fetch_assoc($gatewayqry);
            $billingid = $gatewayid['gatewayid'];
			//$billingid_in_array['paypalbillingid'];
		 	$currency_query = "SELECT  `code` 
		                                FROM  `tblcurrencies` 
		                                LEFT JOIN  `tblclients` ON (  `tblcurrencies`.`id` =  `tblclients`.`currency` ) 
		                                WHERE  `tblclients`.`id` =$_POST[clientid]
		                                LIMIT 1";
		    $curr_result = full_query($currency_query);
		    $curency = mysql_fetch_assoc($curr_result);
		    $gateway_details['currency'] = $curency['code'];
            $pay_pal_return_data = call_to_paypal_for_charge($billingid, $gateway_details, $amount, $comment);

            if ($pay_pal_return_data['ACK'] == "Success") {
                $today = date('Ymd');
                $transid = $pay_pal_return_data['TRANSACTIONID'];

                $command = "createinvoice";
                $adminuser = $_SESSION['adminid'];
                $values["userid"] = $_POST['clientid'];
                $values["date"] = $today;
                $values["duedate"] = $today;
                $values["paymentmethod"] = "paypalbilling";
                $values["sendinvoice"] = $option;
                $values["itemdescription1"] = $_POST['comments'];
                $values["itemamount1"] = $amount;
                $values["itemtaxed1"] = 1;
                $results = localAPI($command, $values, $adminuser);
                if ($results['result'] == "success") {
                    if ($results['invoiceid']) {
                        $command = "addinvoicepayment";
                        $adminuser = $_SESSION['adminid'];
                        $values1["invoiceid"] = $results['invoiceid'];
                        $values1["transid"] = $transid;
                        $values1["noemail"] = $perform;
						$values1["datepaid"] = $today;
                        $values1["gateway"] = "paypalbilling";
						$values1['fees'] = $pay_pal_return_data['FEEAMT'];
                        $paymentresults = localAPI($command, $values1, $adminuser);
                        if ($paymentresults['result'] == "success") {
                            $display = "Successfully charged & created Invoice #" . $results['invoiceid'];
                            $command = "updateinvoice";
                            $adminuser = $_SESSION['adminid'];
                            $values2["invoiceid"] = $results['invoiceid'];
                            $values2["paymentmethod"] = "paypalbilling";
                            $update_payment_method = localAPI($command, $values2, $adminuser);
                        }
                    }
                }

                $status = "success";
                $reason = strtoupper($pay_pal_return_data[PAYMENTSTATUS]) . "REASON";
                $msg = "Charged Amount: $pay_pal_return_data[AMT], Fee: $pay_pal_return_data[FEEAMT], Payment Status: $pay_pal_return_data[PAYMENTSTATUS], Issues (if any): $pay_pal_return_data[$reason], $display ";
            } else {
                $status = "error";
                $msg = $pay_pal_return_data['PAYMENTSTATUS'];
                if ($msg == "") {
                    $msg = $pay_pal_return_data['L_SHORTMESSAGE0'] . ": " . $pay_pal_return_data['L_LONGMESSAGE0'];
                    if (preg_match("/ReferenceID/", $msg)) {
                        $msg.=" (Either the client has removed the billing agreement or the Billing Agreement ID has been canceled.)";
                    }
                }
            }
//            print_r($pay_pal_return_data);
//            exit;
            #addInvoicePayment($invoiceid, $transid, $amt, $feeamount, $gatewaymodule);
            logTransaction("PayPal Billing Center Addon", $pay_pal_return_data, $status);
        } else {
            $status = "error";
            $msg = "Please select a client";
        }
    }

    $custom_field_and_table_query = "SELECT `id`, `gatewayid`, CONCAT(`firstname`,' ',`lastname`) c_name FROM `tblclients`  WHERE `gatewayid` LIKE 'B-%' ORDER BY `firstname`" ;


    $clients = mysql_select($custom_field_and_table_query);
    ?>
    <?php if ($status) { ?>
        <div class="<?php echo $status; ?>box">
            <strong>
                <span class="title">
                    <?php echo strtoupper($status); ?>
                </span>
            </strong><br>
            <?php echo $msg; ?>
        </div>
    <?php } 
	
	if($_REQUEST['action'] == '') :
	?>
  <!--<div id="tabs"><ul><li class="tab" id="tab0"><a href="javascript:;">Charge a Client</a></li></div>-->
    <div class="tabbox" id="tab0box" >
        <div id="tab_content">
					<?php echo "<p>" . $LANG['description'] . "</p>";?>
            <?php
           // $bi_query = "SELECT gatewayid FROM tblclients";
//mysql_query($bi_query);
            ?>
			
            <!-- Filter -->
            <form method="post" action="<?php echo $module_link; ?>">
                <table  cellspacing="2" cellpadding="3" border="0" class="form" width="100%">
                    <tr>
                        <td width="15%" class="fieldlabel">Client Name</td>
                        <td class="fieldarea">
                            <select name="clientid" class="form-control input-sm">
                                <option value="">Select</option>
                                <?php foreach ($clients as $client) { ?>
                                    <option value="<?php echo $client['id'] ?>"><?php echo $client['c_name'] ?></option>
                                <?php } ?>
                            </select>
							
                        </td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">Amount</td>
                        <td class="fieldarea"><input type="text" class="form-control" name="amount" size="10" value="" placeholder="0.00"></td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">Invoice Line Item</td>
                        <td class="fieldarea"><textarea name="comments" class="form-control bottom-margin-6" rows="6" cols="50" style="padding: 2px;" placeholder="112 Characters Max"></textarea></td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">Send Invoice Created Email</td>
                        <td class="fieldarea"><input type="checkbox" name="Invoice_mail" value="1"></td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">Send Payment Confirmation Email</td>
                        <td class="fieldarea"><input type="checkbox" name="Payment_confirmation" value="1"></td>
                    </tr>
                </table>
                <img width="1" height="5" src="images/spacer.gif"><br>
                <div style="text-align: right;">
	                <input type="reset" class="button btn btn-warning" value="Reset">
                    <input type="submit" class="button btn btn-success" value="Charge Client" name="charge_client">
                </div>

            </form>


        </div>
    </div>
    <?php
	elseif($_REQUEST['action'] == 'summary'):

	  require( dirname( __FILE__ )."/summary.php"); 
	
	elseif($_REQUEST['action'] == 'ipn'):

	  require( dirname( __FILE__ )."/ipn.php");
	
	elseif($_REQUEST['action'] == 'migrate'):

	  require( dirname( __FILE__ )."/migrate.php");
	  
  	elseif($_REQUEST['action'] == 'echeck'):

  	  require( dirname( __FILE__ )."/echeck.php");
	  
    	elseif($_REQUEST['action'] == 'status'):
			
			//$check_reference_status = check_reference_status();
			if (check_reference_status() == "on"):
			echo '<div class="alert alert-success">
			  <strong>Congratulations!</strong> Reference Transactions are enabled on your PayPal account. Your configuration is complete, and you may now charge clients.</strong>
			</div>';
			elseif(check_reference_status() == "off"):
			echo '<div class="alert alert-warning">
		  <strong>Warning!</strong> Your PayPal Account does not have Reference Transactions enabled on it yet. Make sure your PayPal Account API information is correctly entered in Setup > Payments > Payment Gateways > MyWorks PayPal Billing, or <strong><a href="http://myworks.design/software/whmcs-paypal-billing-agreements-payment-gateway/documentation">view steps to enable now.</a></strong>
		</div>';
	endif;

	endif;
}

	/*
	*************************************************************************
	*                                                                       *
	*  End MyWorks Code / Start Licensing Code						        *
	* Copyright (c) 2007-2015 MyWorks Design. All Rights Reserved,          *
	* PayPal Billing Center Coding                                          *
	* Last Modified: 15 Dec 2014                                            *
	*                                                                       *
	*************************************************************************
	*/





?>