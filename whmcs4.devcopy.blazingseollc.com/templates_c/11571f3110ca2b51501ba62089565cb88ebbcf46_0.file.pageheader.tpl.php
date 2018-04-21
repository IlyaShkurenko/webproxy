<?php
/* Smarty version 3.1.29, created on 2018-02-08 23:07:23
  from "/srv/www/whmcs4.devcopy.blazingseollc.com/templates/six/includes/pageheader.tpl" */

if ($_smarty_tpl->smarty->ext->_validateCompiled->decodeProperties($_smarty_tpl, array (
  'has_nocache_code' => false,
  'version' => '3.1.29',
  'unifunc' => 'content_5a7cd82bc045b3_52592581',
  'file_dependency' => 
  array (
    '11571f3110ca2b51501ba62089565cb88ebbcf46' => 
    array (
      0 => '/srv/www/whmcs4.devcopy.blazingseollc.com/templates/six/includes/pageheader.tpl',
      1 => 1500305316,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_5a7cd82bc045b3_52592581 ($_smarty_tpl) {
$template = $_smarty_tpl;
?><div class="header-lined">
    <h1><?php echo $_smarty_tpl->tpl_vars['title']->value;
if ($_smarty_tpl->tpl_vars['desc']->value) {?> <small><?php echo $_smarty_tpl->tpl_vars['desc']->value;?>
</small><?php }?></h1>
    <?php if ($_smarty_tpl->tpl_vars['showbreadcrumb']->value) {
$_smarty_tpl->smarty->ext->_subtemplate->render($_smarty_tpl, ((string)$_smarty_tpl->tpl_vars['template']->value)."/includes/breadcrumb.tpl", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, true);
}?>
</div>
<?php }
}
