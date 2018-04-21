<?php
/**
 * MYWORKS DESIGN Language File
 * English (en)
 *
 * Please Note: This language file is used to specify language used in the custom client area page.
 *
 */

if (!defined("WHMCS")) die("This file cannot be accessed directly");

$_LANG['pb_pageheader'] = "PayPal Billing";

$_LANG['pb_billingheader'] = "Current Billing ID";
$_LANG['pb_subscriptionheader'] = "Current Active Subscriptions";

$_LANG['pb_nobid'] = "None - Click below to set one up!";

$_LANG['pb_activetext'] = "Active!";

$_LANG['pb_notsetuptext'] = "Not Setup!";

$_LANG['pb_cancel'] = "Cancel Billing Agreement";

$_LANG['pb_create'] = "Create New Billing Agreement";

$_LANG['pb_clientdesc'] = "Your PayPal Billing Agreement ID allows us to automatically charge your PayPal account for invoices that are due and new orders, saving you the hassle of making sure your invoices are paid on time and easing your mind to let us take care of the billing for you!";

$_LANG['pb_subscriptionclientdesc'] = "You currently are using PayPal Subscriptions with us.</br></br> We recommend you convert to a PayPal Billing Agreement, which allows us to automatically charge your PayPal account for invoices that are due and new orders, saving you the hassle of making sure your invoices are paid on time and easing your mind to let us take care of the billing for you!</br></br>Simply use the button below to easily create a billing agreement with us.";

$_LANG['pb_autocancel'] = "Your PayPal Subscriptions will be cancelled once you successfully create a Billing Agreement.";

$_LANG['pb_invoicetext'] = "Your account is already setup with AutoPay using your PayPal Account. The amount due will be automatically charged upon the invoice due date, so no action is needed on your part. To manage your billing agreement, <a href=\"/paypalbilling.php\"click here.</a>";

$_LANG['pb_description'] = "Use the area below to charge a client on demand. It will create an invoice and pay it. This is recommended for on-demand usage only, as we recommend that when WHMCS creates an invoice, you use the <input type=\"button\" value=\"Attempt Capture\" class=\"button btn btn-success\"> to capture those invoices ahead of time, if you wish.";

$_LANG['invoicepaymentpendingreview'] = "Thank You! Your PayPal eCheck Payment was successful and will be applied to your invoice as soon as PayPal clears it.<br /><br />This can take up to a few days so your patience is appreciated.";