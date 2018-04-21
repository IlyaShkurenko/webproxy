<?php


function remove_billing_id() {
 // $userid = $this->_tpl_vars['clientsdetails']['userid']; 
   	$billing_id_query = "UPDATE `mod_paypalbilling` SET `paypalbillingid` = NULL WHERE `id` = '$userid';";
    $result = mysql_query($billing_id_query);
	return $result;
}

function cancel_billing_agreement($bid, $uid) {
    $billing_cancel_query = "UPDATE `mod_paypalbilling` SET `agreement_status` =  'canceled' WHERE `id` = '$uid' AND `paypalbillingid` = '$bid' ";
    $result = mysql_query($billing_cancel_query);
	if($result) {
		$update_gateway_id_qry = "UPDATE `tblclients` SET `gatewayid` = NULL  WHERE `id` = '$uid' and `gatewayid` = '$bid'";
		$result2 = mysql_query($update_gateway_id_qry);
    }
	return $result2;
}

function delete_billing_agreement($bid, $uid) {
	$billing_delete_query= "DELETE FROM `mod_paypalbilling` WHERE `id` = '$uid' AND `paypalbillingid` = '$bid'";
	$result = mysql_query($billing_delete_query);
	if($result) {
		$update_gateway_id_qry = "UPDATE `tblclients` SET `gatewayid` = NULL  WHERE `id` = '$uid' and `gatewayid` = '$bid'";
		$result2 = mysql_query($update_gateway_id_qry);
    }
	return $result2;
}

if(isset($_POST['BA_remove_curr_ba'])) {
	if($_POST['BA_remove_curr_ba'] == "Remove Current Billing ID" && isset($_POST['userid'])  && isset($_POST['curr_bid'])) {
		delete_billing_agreement($_POST['curr_bid'], $_POST['userid']);
	}	
}


if(isset($_GET['action'])) {
    include '../../../init.php';
	require_once '../../../modules/gateways/paypalbilling.php';
	require_once("../../../includes/gatewayfunctions.php");
	$userid = $_GET['userid'];
	$GATEWAY = getGatewayVariables("paypalbilling");

	$ba_active = select_query("mod_paypalbilling", 'paypalbillingid, id', array('agreement_status' => "active", 'id'=>$userid));
	while($ba = mysql_fetch_assoc($ba_active)) {
		$nvpStr = "&REFERENCEID=".urlencode($ba['paypalbillingid'])."&BillingAgreementStatus=Canceled";
		$httpParsedResponseAr_bacancel = PPHttpPost12('BillAgreementUpdate', $nvpStr, $GATEWAY['API_USERNAME'], $GATEWAY['API_PASSWORD'],$GATEWAY['API_SIGNATURE'], $environment);
		if(strtoupper($httpParsedResponseAr_bacancel['ACK'])=='FAILURE' && $httpParsedResponseAr_bacancel['L_SHORTMESSAGE0']== "Billing%20Agreement%20was%20cancelled" && $httpParsedResponseAr_bacancel['L_ERRORCODE0'] == 10201) { //10201 - error code for billing agreement already cancelled.
			/*if($auto_delete)
				delete_billing_agreement($ba['paypalbillingid'], $ba['id']);*/
			//else 
				cancel_billing_agreement($ba['paypalbillingid'], $ba['id']);
		}
	}

    $curr_ba_id = select_query("tblclients", 'gatewayid', array('id' => $userid));
	$res = mysql_fetch_assoc($curr_ba_id);
	$current_id = $res['gatewayid'];
	$result_gateway = select_query("mod_paypalbilling", 'id, paypalbillingid, agreement_status', array('id' => $userid), 'agreement_status'); 
   ?>

   <style>
		.align-center {
			text-align : center;
		}
		.clientsummarystats th{
			text-align:center !important;
		}

		.green {
			color: green;
		}

		.red {
			color: red;
		}
   </style>
   


		<div class="clientssummarybox">
		<?php if($current_id): ?>
			<h1 style="float:left;"><?php echo $current_id;?></h1>
			<div class="" style="text-align:right;"> 
				<form name="BA_curr-remove-form" id="BA_curr-remove-form" action="" method="post">
					<input type="hidden" name="userid"  value="<?php echo $userid;?>">
					<input type="hidden" name="curr_bid"  value="<?php echo $current_id;?>">
					<input type="submit" class="button btn btn-warning" name="BA_remove_curr_ba" value="Remove Current Billing ID">
				</form>
			</div>
		<?php endif; ?>
			   <table class="clientssummarystats"> <tr><th style="text-align:center;">Billing Agreement ID</th><th style="text-align:center;">Status</th><th style="text-align:center;">Cancel/Clear</th></tr>
				<?php while($client = mysql_fetch_assoc($result_gateway)):
				//print_r($client);
				    $bid = $client["paypalbillingid"]; 
						$bstatus = $client["agreement_status"];
						
						$color = ($bstatus == "canceled") ? "red" : "green" ;
						?>
						<tr>
							<td class="align-center"><?php echo ($bid ? $bid : 'N/A'); ?></td>
							<td class="align-center <?php echo $color;?>"><?php echo ucfirst($bstatus); ?></td>
							<td class="align-center">
								 <form name="BAsettings" id="BAsettingsform" action="clientssummary.php?userid=<?php echo $client["id"];//echo $_SERVER['REQUEST_URI'];?>" method="post">
									<input type="hidden" name="userid"  value="<?php echo $client["id"];?>">
									<input type="hidden" name="BAUpdate_id" value="<?php echo $bid;?>">
									<?php if($bstatus!="canceled") : ?>
											<input type="submit" name="BAUpdate_cancel" value="Cancel Agreement">	
									<?php endif;
									?>
									<input type="submit"  name="BAUpdate_clear" value="Clear ID from History">
								</form>

							</td>
						</tr>
				 <?php endwhile;
					?>
				</table>
			</div>
		</div>
	

<?php return;
}

if(isset($_POST['BAUpdate_cancel'])) {
 
	if($_POST['BAUpdate_cancel'] == 'Cancel Agreement' && !empty($_POST['BAUpdate_id']) && !empty($_GET['userid'])) {
			require_once '../modules/gateways/paypalbilling.php';
			$GATEWAY = getGatewayVariables("paypalbilling");
			$environment = ($GATEWAY['Testmode'])== 'on' ? 'sandbox' : '';
			$nvpStr = "&REFERENCEID=".urlencode($_POST['BAUpdate_id'])."&BillingAgreementStatus=Canceled";
	        $httpParsedResponseAr_baupdate = PPHttpPost12('BillAgreementUpdate', $nvpStr, $GATEWAY['API_USERNAME'], $GATEWAY['API_PASSWORD'],$GATEWAY['API_SIGNATURE'], $environment);
			//print_r($httpParsedResponseAr_baupdate);
			if((strtoupper($httpParsedResponseAr_baupdate['ACK']) == 'SUCCESS' && strtoupper($httpParsedResponseAr_baupdate['BILLINGAGREEMENTSTATUS']) == 'CANCELED') || (strtoupper($httpParsedResponseAr_baupdate['ACK'])=='FAILURE' && $httpParsedResponseAr_baupdate['L_SHORTMESSAGE0']== "Billing%20Agreement%20was%20cancelled" && $httpParsedResponseAr_baupdate['L_ERRORCODE0'] == 10201)) {
					/*$auto_clear_qry = select_query("tbladdonmodules", 'value', array('module' => "paypal_billing_center", 'setting' => "autoclear"));
					$autoclear = mysql_fetch_assoc($auto_clear_qry);
					if($autoclear['value'] == 'on') { 
						delete_billing_agreement($_POST['BAUpdate_id'], $_GET['userid']);
					}
					else {*/
						cancel_billing_agreement($_POST['BAUpdate_id'], $_POST['userid']);
					//}
			}
	//echo "<pre>";
	//print_r($httpParsedResponseAr_baupdate);exit;
	}

} elseif(isset($_POST['BAUpdate_clear'])) { 
	if($_POST['BAUpdate_clear'] == 'Clear ID from History' && !empty($_POST['userid'])) { 
		delete_billing_agreement($_POST['BAUpdate_id'], $_POST['userid']);
	}
}
?>

   <?php
require_once 'paypal_billing_center.php';

   $trialdaysleft = 7-((strtotime(date("Y-m-d")) - strtotime($pblicensecheck["regdate"]))/86400);

  // echo '<textarea cols="100" rows="20">' . print_r($pblicensecheck, true) . '</textarea>';

   if (preg_match("/PayPalFree/", getPaypalBillingAddonConfig('licensekey'))) {
		
   ?>
   <div class="alert alert-info">
     <strong>Thanks for trying our free PayPal Billing trial!</strong> Feel free to contact us with any questions or to request a live demo. <strong><a href="http://myworks.design/software/whmcs-paypal-billing-agreements-payment-gateway">Only <?php echo $trialdaysleft;?> days left - buy now! </a></strong>
   </div>
   <?php }?>
   

<?php

  //  $userid = $this->_tpl_vars['clientsdetails']['userid'];
	//$result_gateway = select_query("mod_paypalbilling", 'paypalbillingid, agreement_status', array('id' => $userid));

		echo ' <div style="margin-top: 35px;position: absolute;right: 40px;"><div class="clientsummaryactions" style=""><img src="../modules/addons/paypal_billing_center/img/yes.png"> Current Billing Agreement ID: ' .$value. '<span style="color: green;"> (Active) </span>';
		$bid=true;
		echo '<input type="button" value="Manage / View" id="showBASettingsId" onclick="javascript:void(0)" class="button btn btn-success"></div></div>';
		
?>


<style>

/*div.row.client-summary-panels {
	margin-top:45px;
}*/
.client-summary-name {
	margin-bottom: 50px !important;
}

 .p10 { 
	 padding: 10px;
 }

 #BAsettingspopup {
	width: 50%;
	height: auto;
 }
</style>
<div id="BAsettingspopup" title="" style="display:none;">
	<p><span class="ui-icon ui-icon-alert" style="float:left; margin:0 7px 40px 0;"></span>Loading...</p>
</div>

<script>
	$(document).ready(function(){
$("#BAsettingspopup").dialog({
	autoOpen: false,
	resizable: true,
	modal: true,
	width: 650,
	title: "Billing Agreement Settings"
}); 

var userid = "<?php echo $_GET['userid']; ?>";

$("#showBASettingsId").click(
function() { //alert("inn");
$("#BAsettingspopup").dialog("open");
$("#BAsettingspopup").load("../modules/addons/paypal_billing_center/admin.php?action=showBASettingsId&userid="+userid);
return false;
}
);
});

</script>