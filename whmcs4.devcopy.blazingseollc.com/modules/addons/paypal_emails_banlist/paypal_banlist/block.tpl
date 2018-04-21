{$paypal_email_banlist = mysql_fetch_array(select_query('tbladdonmodules', 'value', ['module' => 'paypal_emails_banlist']))}
{if $paypal_email_banlist}
	{if preg_match('/paypal/i', $paymentmethod)}
		{if $status eq "Unpaid" || $status eq "Draft"}
			{if !$paymentSuccess}
			<div class="invoice-container" id="checkPaypalEmail">
				<div class="row">
					<div class="col-sm-12 text-center">
						<span>{$LANG.invoicesrefundedspantext1}{$companyname}{$LANG.invoicesrefundedspantext2}</span><br><br>
						<input type="text" name="paypal_email" placeholder="{$LANG.invoicesrefundedplaceholder}" class="small-text">
						<a href="#" class="btn btn-default" id="payPalEmail"><i class="fa fa-check-square"></i> {$LANG.paypalbanlistmodcheckbutton}</a>
					</div>
				</div>
			</div>
			{/if}
		{/if}
	{/if}
{/if}
