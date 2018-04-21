<?php
if (!defined("WHMCS"))
    die("This file cannot be accessed directly");

	$modulelink = 'addonmodules.php?module=paypal_billing_center';
	$action = (isset($_GET['module']) && isset($_GET['action']) && $_GET['module']=='paypal_billing_center')?$_GET['action']:'';
	$version = '';
	$v_rs = mysql_query("SELECT `value` FROM `tbladdonmodules` WHERE `module` = 'paypal_billing_center' AND `setting` = 'version' ");
	if(mysql_num_rows($v_rs)>0){
		$v_data = mysql_fetch_array($v_rs);
		$version = $v_data['value'];
	}
	$version_check = '';
	$addon = get_paypal_billing_center_version_data('MyWorks PayPal Billing Agreements Module');
	if (isset($addon->version)) {
        if (($version < $addon->version)) {
            $version_check = "<a style=\"display:inline-block;padding:0px;\" target=\"_blank\" href='https://myworks.design/account/clientarea.php?action=products'><span class=\"label active\">New Version Available!</span></a>";
        } else {
            $version_check = "<a style=\"display:inline-block;padding:0px;\" target=\"_blank\" href='https://myworks.design/account/clientarea.php?action=products'><span class=\"label expired\">Up To Date!</span></a>";
        }
        
    }
?>
<nav class="navbar navbar-default">
  <div class="container-fluid">
	  <div class="navbar-header">
		  		<img src="https://cdn.myworks.design/myworks/favicon.png" style="float:left;padding-right:15px;" height="50px;">
	              <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
	                <span class="icon-bar"></span>
	                <span class="icon-bar"></span>
	                <span class="icon-bar"></span>
	              </button>
	              <a style="line-height:13px;color:#00BAD3;" class="navbar-brand">PayPal Billing<br><span style="font-size:10px;color:#FA9E1F;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; for WHMCS</span></a>
	            </div>
    <!-- Collect the nav links, forms, and other content for toggling -->
    <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
      <ul class="nav navbar-nav">   
		  <li><a href="<?php echo $modulelink;?>">Manual Charge</a></li>     
        <li class="dropdown">
          <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">Summary <span class="caret"></span></a>
          <ul class="dropdown-menu">
            <li><a href="<?php echo $modulelink;?>&action=summary">Billing Agreements</a></li>
			<li role="separator" class="divider"></li>
		    <li><a href="<?php echo $modulelink;?>&action=echeck">eChecks</a></li>           
          </ul>
        </li>
		
		<li><a href="<?php echo $modulelink;?>&action=ipn">IPN Setup</a></li> 

		<li><a href="<?php echo $modulelink;?>&action=migrate">Migrate Clients</a></li>
		
		<li><a href="<?php echo $modulelink;?>&action=status"><strong>Reference Transaction Status</strong></a></li>
		
      </ul>
	 

	  <ul class="nav navbar-nav navbar-right">
	        <li class="dropdown">
	          <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">Support <span class="caret"></span></a>
			    <ul class="dropdown-menu">
					<li><a href="http://myworks.design/software/whmcs-paypal-billing-agreements-payment-gateway/documentation">Documentation</a></li>
					<li role="separator" class="divider"></li>
					<li><a href="https://myworks.design/account/software.php?view=releases&projectid=1">Changelog</a></li>
					<li role="separator" class="divider"></li>
					<li><a href="https://myworks.design/account/submitticket.php">Open Ticket</a></li>
				</ul>
		  </li>
			          <li>
			            <b>Version: <?php echo $version;?></b></br> <?php echo $version_check;?>
			  	  </li>
	              </ul>
	  
    </div><!-- /.navbar-collapse -->
  </div><!-- /.container-fluid -->
</nav>