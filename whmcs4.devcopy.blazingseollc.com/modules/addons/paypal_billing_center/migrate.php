<?php
if (!defined("WHMCS"))
    die("This file cannot be accessed directly");
?>
<h2>Step 1: Module Configuration</h2>

<p>Before you start migrating your customers, make sure the following steps are done.</p>

<p>1. Test the module and make sure it's working correctly. Make sure your gateway information is filled in.</p>

<p>2. Check the 2 checkboxes in the addon-module configuration to auto-set the gateway as default, and to auto cancel any existing PayPal subscriptions the customer might have setup, upon signing up for a billing agreement. For the auto-cancel to work, the subscription ID must be listed in the product subscription ID field.</p>   

<p>3. If the auto-cancel checkbox is checked, fill in the API information of the account all of the PayPal subscriptions are tied to. We'd assume this is the same as the API information you'll be using for this gateway, but left it separate just in case.</p>

<h2>Step 2: Customer Notification</h2>
<p>Once this module is configured successfully, you'll be able to do is start migrating your customers. This is super easy with our built-in migration compatibility.</p>

<p>1. Email your customers, and direct them to the link below. They'll need to sign into their account before they can access the page.</p> 

<p>3. That's it! After they login, they'll click the button in the client page you directed them to, and sign into their PayPal account. They'll agree to the billing agreement, and be returned to their client page. An active billing agreement will be assigned to their account, their paypal subscriptions will be auto cancelled, and they'll have their default payment method as this gateway.</p>

<p>We recommend adding a menu list item to your client area template entitled Manage PayPal Billing for your clients to easily see their billing status with you. The code for this list item is below - just add it to the correct place in your header.tpl template file.</p>

<div class="contentbox">
<div class="input-group input-group-med">
  <div class="input-group-btn">
    <input type="submit" value="Client Area Link" class="btn btn-default">
  </div>
  <input type="text" class="form-control" value="<?php global $CONFIG; echo $CONFIG['SystemURL'] ?>/paypalbilling.php">
</div>
<br> 
<div class="input-group input-group-med">
  <div class="input-group-btn">
    <input type="submit" value="Client Area Menu Code" class="btn btn-default">
  </div>
  <input type="text" class="form-control" value="<a href=&quot;<?php global $CONFIG; echo $CONFIG['SystemURL'] ?>/paypalbilling.php&quot;>Manage PayPal Billing</a>">
</div>      

</div>