<?php

/**
 *
 * Force client area and force to be SSL only
 *
 */
use WHMCS\ClientArea;
use WHMCS\Database\Capsule;
define("CLIENTAREA", true);
define("FORCESSL", true);

require("init.php");

global $CONFIG;
/**
 *
 * Bring in the system URLS from WHMCS and trim any nasty whitespaces.
 * If the SSL url is set we should use that, if not use the plain URL.
 *
 */
if (!empty($CONFIG['SystemSSLURL'])) {
    $server = trim($CONFIG['SystemSSLURL']);
} else {
    $server = trim($CONFIG['SystemURL']);
}
if (substr($server, -1) != '/') {
    $server = $server . '/';
}

$systemUrl = $server;
$server .= 'paypalbilling.php';

/**
 *
 * Initialize the WHMCS client are class and create the breadcrumbs.
 * The client must be logged in to use this page.
 *
 */
$ca = new WHMCS_ClientArea();
$ca->initPage();
$ca->requireLogin();
global $vars;
$LANG = $vars['_lang'];

$languagefile = "modules/addons/paypal_billing_center/lang/".$clientsdetails['language'].".php";

if (file_exists($languagefile)) {
   include $languagefile;
} else {
    include ("modules/addons/paypal_billing_center/lang/english.php");
}
$ca->setPageTitle($_LANG['pb_pageheader']);
$ca->addToBreadCrumb('index.php', $whmcs->get_lang('globalsystemname'));
$ca->addToBreadCrumb('paypalbilling.php', $_LANG['pb_pageheader']);

/**
 *
 * If the client is LOGGED IN
 *
 */
if ($ca->isLoggedIn()) {

    /**
     *
     * The users ID should always come from the SESSION
     *
     */
    $userid = mysql_real_escape_string($_SESSION['uid']);
    if (!ctype_digit($userid)) {
        die();
    }

    /**
     *
     * For paypal callback check
     *
     */
    if (isset($_REQUEST['token']) && !empty($_REQUEST['token'])) {
        require_once('modules/gateways/paypalbilling.php');
        require_once('includes/gatewayfunctions.php');
        $params = getGatewayVariables("paypalbilling");
        $paypalToken = $_REQUEST['token'];
        $nvpStr = "&TOKEN=$paypalToken";
        $environment = $params['Testmode'] == 'on' ? 'sandbox' : '';
        $httpParsedResponseAr = PPHttpPost12('CreateBillingAgreement', $nvpStr, $params['API_USERNAME'],
            $params['API_PASSWORD'], $params['API_SIGNATURE'], $environment);

        /**
         *
         * DEBUG
         *
         * print_r($httpParsedResponseAr);
         *
         */

        if ("SUCCESS" == strtoupper($httpParsedResponseAr["ACK"]) || "SUCCESSWITHWARNING" == strtoupper($httpParsedResponseAr["ACK"])) {

            $billingid = mysql_real_escape_string(strip_tags(urldecode($httpParsedResponseAr["BILLINGAGREEMENTID"])));

            $result = mysql_query("
                                   INSERT
                                     INTO `mod_paypalbilling` (`id`, `paypalbillingid`)
                                   VALUES ('" . $userid . "','" . $billingid . "'
                                   ");

            $result = mysql_query("
                                   UPDATE `tblclients`
					                  SET `gatewayid` = '" . $billingid . "', `defaultgateway`= 'paypalbilling'
					                WHERE `id` = '" . $userid . "'
					              ");

            if ($result && isset($_REQUEST['zerocheckout'])) {
                $invId = $_REQUEST['invoiceid'];
                header("Location: {$systemUrl}viewinvoice.php?id=$invId");
            }

            if ($result) {
                header("Location: $server");
            }
        }

    }

    if (isset($_POST['BAUpdate_cancel']) && !empty($_POST['BAUpdate_id']) && !empty($_POST['userid'])) {

        require_once('modules/gateways/paypalbilling.php');
        require_once('includes/gatewayfunctions.php');
        $GATEWAY = getGatewayVariables("paypalbilling");
        $environment = ($GATEWAY['Testmode']) == 'on' ? 'sandbox' : '';
        $nvpStr = "&REFERENCEID=" . urlencode($_POST['BAUpdate_id']) . "&BillingAgreementStatus=Canceled";
        $httpParsedResponseAr_baupdate = PPHttpPost12('BillAgreementUpdate', $nvpStr, $GATEWAY['API_USERNAME'],
            $GATEWAY['API_PASSWORD'], $GATEWAY['API_SIGNATURE'], $environment);

        /**
         *
         * DEBUG
         *
         * print_r($httpParsedResponseAr_baupdate);
         *
         */

        if ((strtoupper($httpParsedResponseAr_baupdate['ACK']) == 'SUCCESS' && strtoupper($httpParsedResponseAr_baupdate['BILLINGAGREEMENTSTATUS']) == 'CANCELED') || (strtoupper($httpParsedResponseAr_baupdate['ACK']) == 'FAILURE' && $httpParsedResponseAr_baupdate['L_SHORTMESSAGE0'] == "Billing%20Agreement%20was%20cancelled" && $httpParsedResponseAr_baupdate['L_ERRORCODE0'] == 10201)) {
            cancel_billing_agreement($_POST['BAUpdate_id'], $userid);

        }

        /**
         *
         * DEBUG
         *
         * echo "<pre>";
         * print_r($httpParsedResponseAr_baupdate);exit;
         *
         */
    }


    $result = mysql_query("
                           SELECT `gatewayid`
                             FROM `tblclients`
                            WHERE `id`= '" . $userid . "'
                          ");
    $data = mysql_fetch_array($result);
    $bid = $data["gatewayid"];
    if (!preg_match("/^B-/", $bid)) {
        $bid = false;
    }
	

	
    if ($bid) {

    } else {

        require_once('modules/gateways/paypalbilling.php');
        require_once('includes/gatewayfunctions.php');
		
        $description = "Billing Agreement Setup";
		$GATEWAY = getGatewayVariables("paypalbilling");
		$url = PB_GetSystemURL();
		$userid = $clientsdetails['userid'];
 	   	$environment = ($GATEWAY['Testmode']) == 'on' ? 'sandbox' : '';
		
		$returnURL = urlencode($url . "/modules/gateways/callback/paypalbilling.php?clientid=$userid&basetup=true");
		$cancelURL = urlencode($url . "/paypalbilling.php");
	
	
        $nvpStr = "&MAXAMT=25&Amt=0&L_BILLINGTYPE0=MerchantInitiatedBilling&L_BILLINGAGREEMENTDESCRIPTION0=$description&RETURNURL=$returnURL&CANCELURL=$cancelURL";
        $httpParsedResponseAr = PPHttpPost12('SetExpressCheckout', $nvpStr, $GATEWAY['API_USERNAME'],
            $GATEWAY['API_PASSWORD'], $GATEWAY['API_SIGNATURE'], $environment);

         // echo "<pre>";
        //  print_r($httpParsedResponseAr);


        if ("SUCCESS" == strtoupper($httpParsedResponseAr["ACK"]) || "SUCCESSWITHWARNING" == strtoupper($httpParsedResponseAr["ACK"])) {

            $paypalToken = urldecode($httpParsedResponseAr["TOKEN"]);


                //$paypal_url = "https://www.paypal.com/webscr";
				$paypal_url = "https://www.paypal.com/checkoutnow/2";

            if ("sandbox" === $environment || "beta-sandbox" === $environment) {
                $paypal_url = "https://www.$environment.paypal.com/webscr";
            }

        }
    }
	
    $result = mysql_query("
                           SELECT `subscriptionid`
                             FROM `tblhosting`
                            WHERE `userid`= '" . $userid . "'
							AND `subscriptionid` not like '';
                          ");
	$rows = array();
	
	while($row = mysql_fetch_array($result))
	    $rows[] = $row;
	 $subscriptionids = array();
 foreach($rows as $row){ 
		$subscriptionids[] = $row['subscriptionid'];
		//echo $subscriptionids;
	};

	$smarty->assign('sid',$subscriptionids);
	
	if (getPaypalBillingAddonConfig('autocancel') == 'on'){
		$ca->assign('autocanceltext', 'true');
	}
	
    $ca->assign('paypal_url', $paypal_url);
	
	$ca->assign('number', urlencode($paypalToken));
    if (empty($bid)) {
    } else {
        $ca->assign('bid', $bid);
    }

    /**
     *
     * DEBUG
     * echo $server;
     *
     */

} else {

    /**
     *
     * User is NOT LOGGED IN
     *
     */
}

if (getPaypalBillingAddonConfig('disablesidebar') !== "on") {
/**
 * Set a context for sidebars
 *
 * @link http://docs.whmcs.com/Editing_Client_Area_Menus#Context
 */
Menu::addContext();

/**
 * Setup the primary and secondary sidebars
 *
 * @link http://docs.whmcs.com/Editing_Client_Area_Menus#Context
 */
Menu::primarySidebar('invoiceList');
Menu::secondarySidebar('invoiceList');

# Define the template filename to be used without the .tpl extension

}
$ca->setTemplate('paypalbilling');

$ca->output();

/**
 *
 * cancel_billing_agreement
 *
 * @param $bid
 * @param $userid
 * @return resource
 *
 */
function cancel_billing_agreement($bid, $userid)
{
    $billingID = mysql_real_escape_string($bid);

    $result = mysql_query("
                           UPDATE `mod_paypalbilling`
                              SET `agreement_status` =  'canceled'
                            WHERE `id` = '" . $userid . "'
                              AND `paypalbillingid` = '" . $billingID . "'
                          ");
    if ($result) {
        $result2 = mysql_query("
                                UPDATE `tblclients`
                                   SET `gatewayid` = NULL
                                 WHERE `id` = '" . $userid . "'
                               ");
    }
    return $result2;
}