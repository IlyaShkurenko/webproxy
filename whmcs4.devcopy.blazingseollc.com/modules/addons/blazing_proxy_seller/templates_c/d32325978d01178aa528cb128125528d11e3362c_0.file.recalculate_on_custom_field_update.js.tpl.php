<?php
/* Smarty version 3.1.29, created on 2018-02-09 17:28:27
  from "/srv/www/whmcs4.devcopy.blazingseollc.com/modules/addons/blazing_proxy_seller/templates/manual_order/recalculate_on_custom_field_update.js.tpl" */

if ($_smarty_tpl->smarty->ext->_validateCompiled->decodeProperties($_smarty_tpl, array (
  'has_nocache_code' => false,
  'version' => '3.1.29',
  'unifunc' => 'content_5a7dda3bc17461_04007954',
  'file_dependency' => 
  array (
    'd32325978d01178aa528cb128125528d11e3362c' => 
    array (
      0 => '/srv/www/whmcs4.devcopy.blazingseollc.com/modules/addons/blazing_proxy_seller/templates/manual_order/recalculate_on_custom_field_update.js.tpl',
      1 => 1518169945,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_5a7dda3bc17461_04007954 ($_smarty_tpl) {
echo '<script'; ?>
 type="text/javascript">
	(function () {
		var id = 'customfield' + <?php echo $_smarty_tpl->tpl_vars['id']->value;?>
,
			el = $('#' + id);

		console.info(id, el);

		// Field not found
		if (!el.length) {
			return;
		}

		// Already listens
		if (el.attr('data-proxy-listener')) {
			return;
		}

		// Update price on change
		el.change(function() {
				updatesummary();
		});

		// Mark as processed
	  el.attr('data-proxy-listener', 1);
	})();
<?php echo '</script'; ?>
><?php }
}
