<?php
//require_once ('../modules/gateways/paypalbilling.php');
require_once (dirname(__FILE__)."/../../../modules/gateways/paypalbilling.php");

	if(isset($_POST['echeck_action'])) {
		if(!empty($_POST['echeck_update_id'])) {
			delete_echeck_data($_POST['echeck_update_id']);
		}
	}
?>
 
 <!--<div id="tabs"><ul><li class="tab" id="tab0"><a href="javascript:;">Billing ID Summary</a></li></div>-->
    <div class="tabbox" id="tab0box" >
        <div id="tab_content">
            <!-- Filter -->
			<p>When a client pays with an E-Check, the invoice will not be marked as paid, however the pending transaction will show up here. Once the E-Check clears, it will be marked as cleared below and the invoice will be marked as paid. This screen is dashboard for all E-Check payments made with this module, and a perfect way for you to review them.</p>
			<table  id="sortabletbl0" class="datatable" cellspacing="1" cellpadding="3" border="0" width="100%">
					<tr>
						<th>Invoice ID</th>
						<th>PayPal Transaction ID</th>
						<th>Transaction Status</th>
						<th>Clear Date</th>
						<th>Clear</th>
					</tr>
				<?php 

			
						  $echeck_qry = mysql_query("SELECT `id`, `invoice_id`, `paypal_trans_id`, `clear_date`, `status`  FROM `mod_paypalbilling_echeck`");
							while ($client_ba = mysql_fetch_assoc($echeck_qry)): 
								?>
						  
								<tr>
									<td>
									<?php 
										echo '<a href="invoices.php?action=edit&id='.$client_ba['invoice_id'].'">'.$client_ba['invoice_id'].'</a>';  ?>
								    </td>
									
									<td><?php echo $client_ba['paypal_trans_id']; ?></td>
									<?php if ( $client_ba['status'] == "paid" ) {
										echo "<td><span class='label label-success'>PAID</span></td>";
									}else if ( $client_ba['status'] == "denied" ){
										echo "<td><span class='label label-danger'>DENIED</span></td>";
									}else{
										echo "<td><span class='label label-warning'>PENDING</span></td>";
									} ?>
									<td><?php echo $client_ba['clear_date']; ?></td>
									<td>
										
										 <form name="Echecksettings" id="Echecksettingsform" action="" method="post">
											<input type="hidden" name="echeck_action"  value="clear">
											<input type="hidden" name="echeck_update_id" value="<?php echo $client_ba['id'];?>">
											<input type="submit" class="button btn btn-default" name="BAUpdate_clear" value="Clear">
										</form>
									</td>
								</tr>
								<?php endwhile;  ?>
				</table>

        </div></div>