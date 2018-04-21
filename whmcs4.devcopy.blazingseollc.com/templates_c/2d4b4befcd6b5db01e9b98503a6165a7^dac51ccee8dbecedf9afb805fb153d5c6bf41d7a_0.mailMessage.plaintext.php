<?php
/* Smarty version 3.1.29, created on 2018-02-09 23:51:57
  from "mailMessage:plaintext" */

if ($_smarty_tpl->smarty->ext->_validateCompiled->decodeProperties($_smarty_tpl, array (
  'has_nocache_code' => false,
  'version' => '3.1.29',
  'unifunc' => 'content_5a7e341de2f529_23207641',
  'file_dependency' => 
  array (
    'dac51ccee8dbecedf9afb805fb153d5c6bf41d7a' => 
    array (
      0 => 'mailMessage:plaintext',
      1 => 1518220317,
      2 => 'mailMessage',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_5a7e341de2f529_23207641 ($_smarty_tpl) {
$template = $_smarty_tpl;
?>Dear <?php echo $_smarty_tpl->tpl_vars['client_name']->value;?>
,


This is a payment receipt for Invoice <?php echo $_smarty_tpl->tpl_vars['invoice_num']->value;?>
 sent on <?php echo $_smarty_tpl->tpl_vars['invoice_date_created']->value;?>



<?php echo $_smarty_tpl->tpl_vars['invoice_html_contents']->value;?>



Amount: <?php echo $_smarty_tpl->tpl_vars['invoice_last_payment_amount']->value;?>

Transaction #: <?php echo $_smarty_tpl->tpl_vars['invoice_last_payment_transid']->value;?>

Total Paid: <?php echo $_smarty_tpl->tpl_vars['invoice_amount_paid']->value;?>

Remaining Balance: <?php echo $_smarty_tpl->tpl_vars['invoice_balance']->value;?>

Status: <?php echo $_smarty_tpl->tpl_vars['invoice_status']->value;?>



You may review your invoice history at any time by logging in to your client area.


Note: This email will serve as an official receipt for this payment.


<?php echo $_smarty_tpl->tpl_vars['signature']->value;
}
}
