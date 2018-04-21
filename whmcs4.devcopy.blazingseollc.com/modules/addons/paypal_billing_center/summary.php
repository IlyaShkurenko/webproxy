<?php 
if (!defined("WHMCS"))
    die("This file cannot be accessed directly");

function cancel_billing_agreement($bid, $uid) {
    $billing_cancel_query = "UPDATE `mod_paypalbilling` SET `agreement_status` =  'canceled' WHERE `id` = '$uid' AND `paypalbillingid` = '$bid' ";
    $result = mysql_query($billing_cancel_query);
	if($result) {
		$update_uset_ba_qry = "UPDATE `tblclients` SET `gatewayid` = NULL  WHERE `id` = '$uid'";
		$result2 = mysql_query($update_uset_ba_qry);
    }
	return $result2;
}

function delete_billing_agreement($bid, $uid) {
	$billing_delete_query= "DELETE FROM `mod_paypalbilling` WHERE `id` = '$uid' AND `paypalbillingid` = '$bid' ";
	$result = mysql_query($billing_delete_query);
	if($result) {
		$update_gateway_id_qry = "UPDATE `tblclients` SET `gatewayid` = NULL  WHERE `id` = '$uid'";
		$result2 = mysql_query($update_gateway_id_qry);
    }
	return $result2;
}


if(isset($_POST['BA_refresh_all'])) {
	if($_POST['BA_refresh_all'] == "Refresh All") {
		require_once '..'.DIRECTORY_SEPARATOR.'modules/gateways/paypalbilling.php';
		require_once("..".DIRECTORY_SEPARATOR."includes/gatewayfunctions.php");
		$userid = $_GET['userid'];
		$GATEWAY = getGatewayVariables("paypalbilling");

		$ba_active = select_query("mod_paypalbilling", 'paypalbillingid, id', array('agreement_status' => "active"));
		while($ba = mysql_fetch_assoc($ba_active)) {
			$nvpStr = "&REFERENCEID=".urlencode($ba['paypalbillingid'])."&BillingAgreementStatus=Canceled";
			$httpParsedResponseAr_bacancel = PPHttpPost12('BillAgreementUpdate', $nvpStr, $GATEWAY['API_USERNAME'], $GATEWAY['API_PASSWORD'],$GATEWAY['API_SIGNATURE'], $environment);
			//print_r($httpParsedResponseAr_bacancel);
			if(strtoupper($httpParsedResponseAr_bacancel['ACK'])=='FAILURE' && $httpParsedResponseAr_bacancel['L_SHORTMESSAGE0']== "Billing%20Agreement%20was%20cancelled" && $httpParsedResponseAr_bacancel['L_ERRORCODE0'] == 10201) { //10201 - error code for billing agreement already cancelled.
				/*if($auto_delete)
					delete_billing_agreement($ba['paypalbillingid'], $ba['id']);*/
				//else 
					cancel_billing_agreement($ba['paypalbillingid'], $ba['id']);
			}
		}
	}
} elseif(isset($_POST['BAUpdate_cancel'])) {
 
	if($_POST['BAUpdate_cancel'] == 'Cancel Agreement' && !empty($_POST['BAUpdate_id']) && !empty($_POST['userid'])) {
			
			require_once ('..'.DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR.'modules/gateways/paypalbilling.php');
			require_once('..'.DIRECTORY_SEPARATOR.'includes/gatewayfunctions.php');
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
} elseif(isset($_POST['BA_dedupe_all'])) {
	$qry = "delete from mod_paypalbilling where b_num in ( 
select b_num from ( 
select b_num from mod_paypalbilling a group by paypalbillingid having count(*) > 1 
) b 
)";

  $delStatus = mysql_query($qry);
}


$client_billing_query = mysql_query("SELECT `id`, `firstname`, `lastname` FROM `tblclients` ORDER BY `id`");
//echo mysql_error();

?>
 
 <!--<div id="tabs"><ul><li class="tab" id="tab0"><a href="javascript:;">Billing ID Summary</a></li></div>-->
    <div class="tabbox" id="tab0box" >
        <div id="tab_content">
	
	<div id="dedupe_area">
		<form name="BA_dedupe" id="BA_dedupe" action="" method="post">
			<input type="submit" class="button btn btn-success" style = "float:left; margin-bottom:7px;" name="BA_dedupe_all" value="De-Duplicate">
		</form>
		</div>
		
		<div id="refresh_area">
		<form name="BA_refresh" id="BA_refresh" action="" method="post">
			<input type="submit" class="button btn btn-success" style = "float:right; margin-bottom:7px;" name="BA_refresh_all" value="Refresh All">
		</form>
		</div>

            <!-- Filter -->
			<table  id="sortabletbl0" class="datatable" cellspacing="1" cellpadding="3" border="0" width="100%">
					<tr>
						<th>Client ID</th>
						<th>Client Name</th>
						<th>Billing Agreement ID</th>
						<th>ID Status</th>
						<th>Cancel / Clear</th>
					</tr>
				<?php 
//echo $qry  = "SELECT * FROM `tblclients` FULL OUTER JOIN `mod_paypalbilling` ON tblclients.id = mod_paypalbilling.id";
				//$client_billing_query = mysql_query($qry);

					while ($client = mysql_fetch_assoc($client_billing_query)){
						  $client_ba_qry = mysql_query("SELECT `b_num`, `paypalbillingid`, `agreement_status`  FROM `mod_paypalbilling`  WHERE `id` =".$client['id']);
 $repeat = 0;
							while ($client_ba = mysql_fetch_assoc($client_ba_qry)): ?>
						  
								<tr><td><?php 
										if(!$repeat){  echo '<a href="clientssummary.php?userid='.$client['id'].'">'.$client['id'].'</a>'; } ?>
								    </td>
									<td><?php if(!$repeat) { echo '<a href="clientssummary.php?userid='.$client['id'].'">'.$client['firstname']." ".$client['lastname'].'</a>'; } ?> 
									</td>
									<td><?php echo $client_ba['paypalbillingid']; ?></td>
									<?php if ( $client_ba['agreement_status'] == "active" ) {
										echo "<td><span class='label active'>ACTIVE</span></td>";
									}else{
										echo "<td><span class='label inactive'>INACTIVE</span></td>";
									}?>
									
									<td>
										 <form name="BAsettings" id="BAsettingsform" action="" method="post">
											<input type="hidden" name="userid"  value="<?php echo $client["id"];?>">
											<input type="hidden" name="BAUpdate_id" value="<?php echo $client_ba['paypalbillingid'];?>">
											<?php if($client_ba['agreement_status']!="canceled") : ?>
													<input type="submit" name="BAUpdate_cancel" class="button btn btn-warning" value="Cancel Agreement">	
											<?php endif;
											?>
											
											<input type="submit" class="button btn btn-default" name="BAUpdate_clear" value="Clear ID from History">
										</form>
									</td>
								</tr><?php
								$repeat++;
							endwhile; 
					} 
							//$billing_qry = mysql_query('SELECT * FROM `mod_paypalbilling` WHERE `id`='.$client['id']);
				?>
					
						<?php //endwhile; ?>
				</table>

        </div>
    </div>