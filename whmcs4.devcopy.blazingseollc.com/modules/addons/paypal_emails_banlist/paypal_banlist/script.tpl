{$paypal_email_banlist = mysql_fetch_array(select_query('tbladdonmodules', 'value', ['module' => 'paypal_emails_banlist']))}
{if $paypal_email_banlist}
    {if preg_match('/paypal/i', $paymentmethod)}
        <script type="text/javascript" src="{$BASE_PATH_JS}/jquery.min.js"></script>
        <script>
            var invoice = $('.container-fluid.invoice-container');
			{if $status eq "Unpaid" || $status eq "Draft"}
				{if !$paymentSuccess}
            	invoice.hide();
				{/if}
			{/if}
			
			$("#payPalEmail").click(function (e) {
                var paypal_email = $('input[name=paypal_email]').val();

                e.preventDefault();

                if (!validateEmail(paypal_email)) {
                    alert('{$LANG.paypalbanlistmodentervalidemail}');
                } else {
                    $.ajax({
                        type: 'POST',
                        url: '{$WEB_ROOT}/guard_api.php',
                        data: { 'email': paypal_email},
                        dataType: 'json',
                        success: function(data){
                            if(data.result) {
                                alert('{$LANG.paypalbanlistmodemailinbanlist}');
                                window.location.replace("{$WEB_ROOT}/clientarea.php");
                            } else {
                                $('#checkPaypalEmail').hide();
                                invoice.show();
                            }
                        }
                    });
                }
            });

            function validateEmail(email) {
                var re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{literal}{1,3}{/literal}\.[0-9]{literal}{1,3}{/literal}\.[0-9]{literal}{1,3}{/literal}\.[0-9]{literal}{1,3}{/literal}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{literal}{2,}{/literal}))$/i;
                return re.test(email);
            }
        </script>
    {/if}
{/if}