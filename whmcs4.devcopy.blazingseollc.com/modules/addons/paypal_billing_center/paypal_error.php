<?php

define("CLIENTAREA", true);
define("FORCESSL", true); # Uncomment to force the page to use https://

require("init.php");

//WHMCS Variables
$ca = new WHMCS_ClientArea();
$ca->setPageTitle("PayPal Error");
$ca->addToBreadCrumb('index.php',$whmcs->get_lang('globalsystemname'));
$ca->addToBreadCrumb('paypal_error.php','PayPal Error');
$ca->initPage();
$ca->requireLogin(); // Uncomment this line to require a login to access this page

# To assign variables to the template system use the following syntax.
# These can then be referenced using {$variablename} in the template.

$LANG = $vars['_lang'];

# To assign variables to the template system use the following syntax.
# These can then be referenced using {$variablename} in the template.

$smartyvalues["errortitle"] = 'Error in Payment';

# Check login status
if ($ca->isLoggedIn()) {
	
  # User is logged in - put any code you like here
 
//$smartyvalues["errormsg"] = 'Somthing went wrong please contact support';
if ($_GET['CORRELATIONID']) {
//Check if transaction id belongs to current client
$invId = $_GET['invoiceid'];
$corrId = mysql_real_escape_string($_GET['CORRELATIONID']);

    $query = mysql_query("SELECT * FROM tblgatewaylog where data LIKE '%" . $corrId . "%'") or die(mysql_error());
	if(ctype_digit($invId)){
		$newquery = mysql_query("select `ordernum` from `tblorders` where `invoiceid` = '" . $invId . "'");
		$newrow = mysql_fetch_assoc($newquery);

		if (mysql_num_rows($query) == '1') {
			$data = mysql_fetch_assoc($query);
			$vars = explode('<br />', nl2br($data['data']));
			$params = array();
			foreach ($vars as $key => $value) {
				$param = explode('=>', $value);
				$params[trim($param['0'])] = trim($param['1']);
			}
			$smartyvalues["invoiceid"] = $_GET['invoiceid'];
			$smartyvalues["errormessage"] = urldecode($params['L_LONGMESSAGE0']);
			$smartyvalues["errorcode"] = urldecode($params['L_ERRORCODE0']);
			$smartyvalues["ordernumber"] = $newrow['ordernum'];
		}
		}
    }

} else {
    # User is not logged in
}
# Define the template filename to be used without the .tpl extension

$ca->setTemplate('paypal_error');

$ca->output();
?>