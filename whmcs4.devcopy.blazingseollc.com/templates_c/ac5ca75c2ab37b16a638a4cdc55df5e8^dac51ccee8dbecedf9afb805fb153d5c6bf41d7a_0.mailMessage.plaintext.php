<?php
/* Smarty version 3.1.29, created on 2018-02-09 22:08:35
  from "mailMessage:plaintext" */

if ($_smarty_tpl->smarty->ext->_validateCompiled->decodeProperties($_smarty_tpl, array (
  'has_nocache_code' => false,
  'version' => '3.1.29',
  'unifunc' => 'content_5a7e1be3b84f21_78503976',
  'file_dependency' => 
  array (
    'dac51ccee8dbecedf9afb805fb153d5c6bf41d7a' => 
    array (
      0 => 'mailMessage:plaintext',
      1 => 1518214115,
      2 => 'mailMessage',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_5a7e1be3b84f21_78503976 ($_smarty_tpl) {
$template = $_smarty_tpl;
?>Dear <?php echo $_smarty_tpl->tpl_vars['client_name']->value;?>
,

Thank you for signing up with us. Your new account has been setup and you can now login to our client area using the details below.

Email Address: <?php echo $_smarty_tpl->tpl_vars['client_email']->value;?>

Password: <?php echo $_smarty_tpl->tpl_vars['client_password']->value;?>


To login, visit <?php echo $_smarty_tpl->tpl_vars['whmcs_url']->value;?>


<?php echo $_smarty_tpl->tpl_vars['signature']->value;
}
}
