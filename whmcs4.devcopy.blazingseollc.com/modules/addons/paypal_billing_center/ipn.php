<?php
if (!defined("WHMCS"))
    die("This file cannot be accessed directly");
?>
<h2>Summary</h2>
<p>Our module uses PayPal IPN or a cron job to notify our gateway of any cancellations of Billing Agreements & eCheck updates. 
</br>If a Billing Agreement ID is cancelled, it will remain in your history popup (Client Summary Page) but will be removed from being actively used by the client. This will prevent payment errors and payment declined errors.
</br>When an eCheck is created / clears, if you have IPN set up, it will properly update it in the invoice for you.</p>
</br>

<h1>Option A: IPN Setup</h1>
<b>If your PayPal account is currently using IPN for something else, like the core PayPal module, since there can only be one IPN URL in your PayPal Account, you'll need to use the cron job option below instead.</b></br></br>
<p>1. Update your PayPal IPN, usually in <strong>Profile > Settings > My Selling Tools > Instant Payment Notifications.</strong> You can access this page by clicking the button below, or you may manually navigate to it by logging into your PayPal account.</p>
<p>2. Simply click Edit Settings, update your <strong>Notification URL</strong> to the one below and ensure <strong>Receive IPN messages (Enabled)</strong> is checked.</p>


<div style="text-align:center;">
<a href="https://www.paypal.com/cgi-bin/customerprofileweb?cmd=%5fprofile%2dipn%2dnotify" class="btn btn-info"><i class="fa fa-paypal"></i> Manage Paypal IPN</a> 
</div>
</br>
	<div class="input-group input-group-med">
	  <div class="input-group-btn">
	    <input type="submit" value="Notification URL" class="btn btn-default">
	  </div>
	  <input type="text" class="form-control" value="<?php global $CONFIG; echo $CONFIG['SystemURL'] ?>/modules/gateways/paypalbilling.php">
	</div>
</br>
</br>
<h1>Option B: Cron Job Setup</h1>
<p>We recommend you set this cron job to run once a day - an hour before your WHMCS cron job. It's a fast process and you won't know it's running unless you receive an error.</p>

</br>
	<div class="input-group input-group-med">
	  <div class="input-group-btn">
	    <input type="submit" value="Cron Job Command" class="btn btn-default">
	  </div>
	  <input type="text" class="form-control" value="php -q <?php echo dirname(dirname(dirname(dirname(__FILE__))));?>/modules/addons/paypal_billing_center/cron/paypalbilling.php">
	</div>
</br>