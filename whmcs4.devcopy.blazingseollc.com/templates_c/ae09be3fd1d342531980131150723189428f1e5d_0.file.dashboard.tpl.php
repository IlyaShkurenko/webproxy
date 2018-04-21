<?php
/* Smarty version 3.1.29, created on 2018-02-09 12:09:13
  from "/srv/www/whmcs4.devcopy.blazingseollc.com/modules/addons/blazing_proxy_seller/templates/dashboard.tpl" */

if ($_smarty_tpl->smarty->ext->_validateCompiled->decodeProperties($_smarty_tpl, array (
  'has_nocache_code' => false,
  'version' => '3.1.29',
  'unifunc' => 'content_5a7d8f695e30a8_98513020',
  'file_dependency' => 
  array (
    'ae09be3fd1d342531980131150723189428f1e5d' => 
    array (
      0 => '/srv/www/whmcs4.devcopy.blazingseollc.com/modules/addons/blazing_proxy_seller/templates/dashboard.tpl',
      1 => 1518169945,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_5a7d8f695e30a8_98513020 ($_smarty_tpl) {
?>
<form action="" method="POST">

	<?php if ($_smarty_tpl->tpl_vars['saved']->value) {?>
		<div class="successbox">
			<strong><span class="title">Changes Saved Successfully!</span></strong>
			<br>
			Configuration settings have been saved successfully
		</div>
		<hr>
	<?php }?>

	<div class="btn-container" style="text-align: left; padding: 0">
		<input class="btn btn-success" type="submit" value="Save">
	</div>
	<hr>

	<ul class="nav nav-tabs admin-tabs" role="tablist">
		<li class="active"><a href="#tiers" role="tab" data-toggle="tab" aria-expanded="false">Pricing Tiers</a></li>
		<li><a href="#settings" role="tab" data-toggle="tab" aria-expanded="false">Settings</a></li>
	</ul>

	<div class="tab-content admin-tabs">
		<div class="tab-pane active" id="tiers">
			<?php
$_from = $_smarty_tpl->tpl_vars['pricing']->value;
if (!is_array($_from) && !is_object($_from)) {
settype($_from, 'array');
}
$__foreach_pricingTable_0_saved_item = isset($_smarty_tpl->tpl_vars['pricingTable']) ? $_smarty_tpl->tpl_vars['pricingTable'] : false;
$__foreach_pricingTable_0_saved_key = isset($_smarty_tpl->tpl_vars['id']) ? $_smarty_tpl->tpl_vars['id'] : false;
$_smarty_tpl->tpl_vars['pricingTable'] = new Smarty_Variable();
$_smarty_tpl->tpl_vars['id'] = new Smarty_Variable();
$_smarty_tpl->tpl_vars['pricingTable']->_loop = false;
foreach ($_from as $_smarty_tpl->tpl_vars['id']->value => $_smarty_tpl->tpl_vars['pricingTable']->value) {
$_smarty_tpl->tpl_vars['pricingTable']->_loop = true;
$__foreach_pricingTable_0_saved_local_item = $_smarty_tpl->tpl_vars['pricingTable'];
?>
				<h2><?php echo $_smarty_tpl->tpl_vars['products']->value[$_smarty_tpl->tpl_vars['id']->value]['name'];?>
</h2>

				<h3>Product/Service</h3>

				<div class="form-group">
					<select name="pricing[<?php echo $_smarty_tpl->tpl_vars['id']->value;?>
][productId]" id="">
						<?php
$_from = $_smarty_tpl->tpl_vars['productsGrouped']->value;
if (!is_array($_from) && !is_object($_from)) {
settype($_from, 'array');
}
$__foreach_productsInGroup_1_saved_item = isset($_smarty_tpl->tpl_vars['productsInGroup']) ? $_smarty_tpl->tpl_vars['productsInGroup'] : false;
$__foreach_productsInGroup_1_saved_key = isset($_smarty_tpl->tpl_vars['groupTitle']) ? $_smarty_tpl->tpl_vars['groupTitle'] : false;
$_smarty_tpl->tpl_vars['productsInGroup'] = new Smarty_Variable();
$_smarty_tpl->tpl_vars['groupTitle'] = new Smarty_Variable();
$_smarty_tpl->tpl_vars['productsInGroup']->_loop = false;
foreach ($_from as $_smarty_tpl->tpl_vars['groupTitle']->value => $_smarty_tpl->tpl_vars['productsInGroup']->value) {
$_smarty_tpl->tpl_vars['productsInGroup']->_loop = true;
$__foreach_productsInGroup_1_saved_local_item = $_smarty_tpl->tpl_vars['productsInGroup'];
?>
							<optgroup label="<?php echo $_smarty_tpl->tpl_vars['groupTitle']->value;?>
">
								<?php
$_from = $_smarty_tpl->tpl_vars['productsInGroup']->value;
if (!is_array($_from) && !is_object($_from)) {
settype($_from, 'array');
}
$__foreach_product_2_saved_item = isset($_smarty_tpl->tpl_vars['product']) ? $_smarty_tpl->tpl_vars['product'] : false;
$_smarty_tpl->tpl_vars['product'] = new Smarty_Variable();
$_smarty_tpl->tpl_vars['product']->_loop = false;
foreach ($_from as $_smarty_tpl->tpl_vars['product']->value) {
$_smarty_tpl->tpl_vars['product']->_loop = true;
$__foreach_product_2_saved_local_item = $_smarty_tpl->tpl_vars['product'];
?>
									<option value="<?php echo $_smarty_tpl->tpl_vars['product']->value['id'];?>
" <?php if ($_smarty_tpl->tpl_vars['id']->value == $_smarty_tpl->tpl_vars['product']->value['id']) {?>selected="selected"<?php }?>><?php echo $_smarty_tpl->tpl_vars['product']->value['name'];?>
</option>
								<?php
$_smarty_tpl->tpl_vars['product'] = $__foreach_product_2_saved_local_item;
}
if ($__foreach_product_2_saved_item) {
$_smarty_tpl->tpl_vars['product'] = $__foreach_product_2_saved_item;
}
?>
							</optgroup>
						<?php
$_smarty_tpl->tpl_vars['productsInGroup'] = $__foreach_productsInGroup_1_saved_local_item;
}
if ($__foreach_productsInGroup_1_saved_item) {
$_smarty_tpl->tpl_vars['productsInGroup'] = $__foreach_productsInGroup_1_saved_item;
}
if ($__foreach_productsInGroup_1_saved_key) {
$_smarty_tpl->tpl_vars['groupTitle'] = $__foreach_productsInGroup_1_saved_key;
}
?>
					</select>
				</div>

				<h3>Type</h3>

				<div class="form-group">
					<select name="pricing[<?php echo $_smarty_tpl->tpl_vars['id']->value;?>
][type]" id="">
						<?php
$_from = $_smarty_tpl->tpl_vars['availableTypes']->value;
if (!is_array($_from) && !is_object($_from)) {
settype($_from, 'array');
}
$__foreach_title_3_saved_item = isset($_smarty_tpl->tpl_vars['title']) ? $_smarty_tpl->tpl_vars['title'] : false;
$__foreach_title_3_saved_key = isset($_smarty_tpl->tpl_vars['type']) ? $_smarty_tpl->tpl_vars['type'] : false;
$_smarty_tpl->tpl_vars['title'] = new Smarty_Variable();
$_smarty_tpl->tpl_vars['type'] = new Smarty_Variable();
$_smarty_tpl->tpl_vars['title']->_loop = false;
foreach ($_from as $_smarty_tpl->tpl_vars['type']->value => $_smarty_tpl->tpl_vars['title']->value) {
$_smarty_tpl->tpl_vars['title']->_loop = true;
$__foreach_title_3_saved_local_item = $_smarty_tpl->tpl_vars['title'];
?>
							<option value='<?php echo $_smarty_tpl->tpl_vars['type']->value;?>
' <?php if ($_smarty_tpl->tpl_vars['type']->value == $_smarty_tpl->tpl_vars['pricingTable']->value[0]['type']) {?>selected="selected"<?php }?>><?php echo $_smarty_tpl->tpl_vars['title']->value;?>
</option>
						<?php
$_smarty_tpl->tpl_vars['title'] = $__foreach_title_3_saved_local_item;
}
if ($__foreach_title_3_saved_item) {
$_smarty_tpl->tpl_vars['title'] = $__foreach_title_3_saved_item;
}
if ($__foreach_title_3_saved_key) {
$_smarty_tpl->tpl_vars['type'] = $__foreach_title_3_saved_key;
}
?>
					</select>
				</div>

				<h3>Pricing tier</h3>

				<table class="table table-bordered">
					<tr>
						<th>From</th>
						<th>To</th>
						<th>Price</th>
						<th>Published</th>
					</tr>
					<?php
$_from = $_smarty_tpl->tpl_vars['pricingTable']->value;
if (!is_array($_from) && !is_object($_from)) {
settype($_from, 'array');
}
$__foreach_tier_4_saved_item = isset($_smarty_tpl->tpl_vars['tier']) ? $_smarty_tpl->tpl_vars['tier'] : false;
$__foreach_tier_4_saved_key = isset($_smarty_tpl->tpl_vars['i']) ? $_smarty_tpl->tpl_vars['i'] : false;
$_smarty_tpl->tpl_vars['tier'] = new Smarty_Variable();
$_smarty_tpl->tpl_vars['i'] = new Smarty_Variable();
$_smarty_tpl->tpl_vars['tier']->_loop = false;
foreach ($_from as $_smarty_tpl->tpl_vars['i']->value => $_smarty_tpl->tpl_vars['tier']->value) {
$_smarty_tpl->tpl_vars['tier']->_loop = true;
$__foreach_tier_4_saved_local_item = $_smarty_tpl->tpl_vars['tier'];
?>
						<tr>
							<td>
								<input type="text" class="form-control" size="3" name="pricing[<?php echo $_smarty_tpl->tpl_vars['id']->value;?>
][tiers][<?php echo $_smarty_tpl->tpl_vars['i']->value;?>
][from]" value="<?php echo $_smarty_tpl->tpl_vars['tier']->value['from'];?>
" />
							</td>
							<td>
								<input type="text" class="form-control" size="3" value="<?php if ($_smarty_tpl->tpl_vars['tier']->value['to']) {
echo $_smarty_tpl->tpl_vars['tier']->value['to'];
} else { ?>∞<?php }?>" disabled />
							</td>
							<td>
								<input type="text" class="form-control" size="3" name="pricing[<?php echo $_smarty_tpl->tpl_vars['id']->value;?>
][tiers][<?php echo $_smarty_tpl->tpl_vars['i']->value;?>
][price]" value="<?php echo $_smarty_tpl->tpl_vars['tier']->value['price'];?>
" />
							</td>
							<td>
								<label class="checkbox-inline">
									<input type="checkbox" name="pricing[<?php echo $_smarty_tpl->tpl_vars['id']->value;?>
][tiers][<?php echo $_smarty_tpl->tpl_vars['i']->value;?>
][published]" value="1"
									       <?php if ($_smarty_tpl->tpl_vars['tier']->value['published']) {?>checked="checked"<?php }?>>
								</label>
							</td>
						</tr>
					<?php
$_smarty_tpl->tpl_vars['tier'] = $__foreach_tier_4_saved_local_item;
}
if ($__foreach_tier_4_saved_item) {
$_smarty_tpl->tpl_vars['tier'] = $__foreach_tier_4_saved_item;
}
if ($__foreach_tier_4_saved_key) {
$_smarty_tpl->tpl_vars['i'] = $__foreach_tier_4_saved_key;
}
?>
					<?php
$_from = range(101,103);
if (!is_array($_from) && !is_object($_from)) {
settype($_from, 'array');
}
$__foreach_i_5_saved_item = isset($_smarty_tpl->tpl_vars['i']) ? $_smarty_tpl->tpl_vars['i'] : false;
$_smarty_tpl->tpl_vars['i'] = new Smarty_Variable();
$_smarty_tpl->tpl_vars['i']->_loop = false;
foreach ($_from as $_smarty_tpl->tpl_vars['i']->value) {
$_smarty_tpl->tpl_vars['i']->_loop = true;
$__foreach_i_5_saved_local_item = $_smarty_tpl->tpl_vars['i'];
?>
						<tr>
							<td>
								<input type="text" class="form-control" size="3" name="pricing[<?php echo $_smarty_tpl->tpl_vars['id']->value;?>
][tiers][<?php echo $_smarty_tpl->tpl_vars['i']->value;?>
][from]" value="" />
							</td>
							<td>
								<input type="text" class="form-control" size="3" disabled />
							</td>
							<td>
								<input type="text" class="form-control" size="3" name="pricing[<?php echo $_smarty_tpl->tpl_vars['id']->value;?>
][tiers][<?php echo $_smarty_tpl->tpl_vars['i']->value;?>
][price]" value="" />
							</td>
							<td>
								<label class="checkbox-inline">
									<input type="checkbox" name="pricing[<?php echo $_smarty_tpl->tpl_vars['id']->value;?>
][tiers][<?php echo $_smarty_tpl->tpl_vars['i']->value;?>
][published]" value="1" checked="checked">
								</label>
							</td>
						</tr>
					<?php
$_smarty_tpl->tpl_vars['i'] = $__foreach_i_5_saved_local_item;
}
if ($__foreach_i_5_saved_item) {
$_smarty_tpl->tpl_vars['i'] = $__foreach_i_5_saved_item;
}
?>
				</table>

				<h3>Sort order</h3>

				<div class="form-group">
					<input type="text" name="pricing[<?php echo $_smarty_tpl->tpl_vars['id']->value;?>
][sortOrder]" value="<?php echo $_smarty_tpl->tpl_vars['pricingTable']->value[0]['sortOrder'];?>
" />
				</div>

				<hr>
			<?php
$_smarty_tpl->tpl_vars['pricingTable'] = $__foreach_pricingTable_0_saved_local_item;
}
if ($__foreach_pricingTable_0_saved_item) {
$_smarty_tpl->tpl_vars['pricingTable'] = $__foreach_pricingTable_0_saved_item;
}
if ($__foreach_pricingTable_0_saved_key) {
$_smarty_tpl->tpl_vars['id'] = $__foreach_pricingTable_0_saved_key;
}
?>

			

			<h2>New Pricing Table</h2>

			<h3>Product/Service</h3>

			<div class="form-group">
				<select name="pricing[new][productId]" id="">
					<option value="0">None</option>
					<?php
$_from = $_smarty_tpl->tpl_vars['productsGrouped']->value;
if (!is_array($_from) && !is_object($_from)) {
settype($_from, 'array');
}
$__foreach_productsInGroup_6_saved_item = isset($_smarty_tpl->tpl_vars['productsInGroup']) ? $_smarty_tpl->tpl_vars['productsInGroup'] : false;
$__foreach_productsInGroup_6_saved_key = isset($_smarty_tpl->tpl_vars['groupTitle']) ? $_smarty_tpl->tpl_vars['groupTitle'] : false;
$_smarty_tpl->tpl_vars['productsInGroup'] = new Smarty_Variable();
$_smarty_tpl->tpl_vars['groupTitle'] = new Smarty_Variable();
$_smarty_tpl->tpl_vars['productsInGroup']->_loop = false;
foreach ($_from as $_smarty_tpl->tpl_vars['groupTitle']->value => $_smarty_tpl->tpl_vars['productsInGroup']->value) {
$_smarty_tpl->tpl_vars['productsInGroup']->_loop = true;
$__foreach_productsInGroup_6_saved_local_item = $_smarty_tpl->tpl_vars['productsInGroup'];
?>
						<optgroup label="<?php echo $_smarty_tpl->tpl_vars['groupTitle']->value;?>
">
							<?php
$_from = $_smarty_tpl->tpl_vars['productsInGroup']->value;
if (!is_array($_from) && !is_object($_from)) {
settype($_from, 'array');
}
$__foreach_product_7_saved_item = isset($_smarty_tpl->tpl_vars['product']) ? $_smarty_tpl->tpl_vars['product'] : false;
$_smarty_tpl->tpl_vars['product'] = new Smarty_Variable();
$_smarty_tpl->tpl_vars['product']->_loop = false;
foreach ($_from as $_smarty_tpl->tpl_vars['product']->value) {
$_smarty_tpl->tpl_vars['product']->_loop = true;
$__foreach_product_7_saved_local_item = $_smarty_tpl->tpl_vars['product'];
?>
								<option value="<?php echo $_smarty_tpl->tpl_vars['product']->value['id'];?>
"><?php echo $_smarty_tpl->tpl_vars['product']->value['name'];?>
</option>
							<?php
$_smarty_tpl->tpl_vars['product'] = $__foreach_product_7_saved_local_item;
}
if ($__foreach_product_7_saved_item) {
$_smarty_tpl->tpl_vars['product'] = $__foreach_product_7_saved_item;
}
?>
						</optgroup>
					<?php
$_smarty_tpl->tpl_vars['productsInGroup'] = $__foreach_productsInGroup_6_saved_local_item;
}
if ($__foreach_productsInGroup_6_saved_item) {
$_smarty_tpl->tpl_vars['productsInGroup'] = $__foreach_productsInGroup_6_saved_item;
}
if ($__foreach_productsInGroup_6_saved_key) {
$_smarty_tpl->tpl_vars['groupTitle'] = $__foreach_productsInGroup_6_saved_key;
}
?>
				</select>
			</div>

			<h3>Type</h3>

			<div class="form-group">
				<select name="pricing[new][type]" id="">
					<option value="0">None</option>
					<?php
$_from = $_smarty_tpl->tpl_vars['availableTypes']->value;
if (!is_array($_from) && !is_object($_from)) {
settype($_from, 'array');
}
$__foreach_title_8_saved_item = isset($_smarty_tpl->tpl_vars['title']) ? $_smarty_tpl->tpl_vars['title'] : false;
$__foreach_title_8_saved_key = isset($_smarty_tpl->tpl_vars['type']) ? $_smarty_tpl->tpl_vars['type'] : false;
$_smarty_tpl->tpl_vars['title'] = new Smarty_Variable();
$_smarty_tpl->tpl_vars['type'] = new Smarty_Variable();
$_smarty_tpl->tpl_vars['title']->_loop = false;
foreach ($_from as $_smarty_tpl->tpl_vars['type']->value => $_smarty_tpl->tpl_vars['title']->value) {
$_smarty_tpl->tpl_vars['title']->_loop = true;
$__foreach_title_8_saved_local_item = $_smarty_tpl->tpl_vars['title'];
?>
						<option value="<?php echo $_smarty_tpl->tpl_vars['type']->value;?>
"><?php echo $_smarty_tpl->tpl_vars['title']->value;?>
</option>
					<?php
$_smarty_tpl->tpl_vars['title'] = $__foreach_title_8_saved_local_item;
}
if ($__foreach_title_8_saved_item) {
$_smarty_tpl->tpl_vars['title'] = $__foreach_title_8_saved_item;
}
if ($__foreach_title_8_saved_key) {
$_smarty_tpl->tpl_vars['type'] = $__foreach_title_8_saved_key;
}
?>
				</select>
			</div>

			<h3>Pricing tier</h3>

			<table class="table table-bordered">
				<tr>
					<th>From</th>
					<th>To</th>
					<th>Price</th>
					<th>Published</th>
				</tr>
				<?php
$_from = range(1,6);
if (!is_array($_from) && !is_object($_from)) {
settype($_from, 'array');
}
$__foreach_i_9_saved_item = isset($_smarty_tpl->tpl_vars['i']) ? $_smarty_tpl->tpl_vars['i'] : false;
$_smarty_tpl->tpl_vars['i'] = new Smarty_Variable();
$_smarty_tpl->tpl_vars['i']->_loop = false;
foreach ($_from as $_smarty_tpl->tpl_vars['i']->value) {
$_smarty_tpl->tpl_vars['i']->_loop = true;
$__foreach_i_9_saved_local_item = $_smarty_tpl->tpl_vars['i'];
?>
					<tr>
						<td>
							<input type="text" class="form-control" size="3" name="pricing[new][tiers][<?php echo $_smarty_tpl->tpl_vars['i']->value;?>
][from]" value="" />
						</td>
						<td>
							<input type="text" class="form-control" size="3" disabled />
						</td>
						<td>
							<input type="text" class="form-control" size="3" name="pricing[new][tiers][<?php echo $_smarty_tpl->tpl_vars['i']->value;?>
][price]" value="" />
						</td>
						<td>
							<label class="checkbox-inline">
								<input type="checkbox" name="pricing[new][tiers][<?php echo $_smarty_tpl->tpl_vars['i']->value;?>
][published]" value="1" checked="checked">
							</label>
						</td>
					</tr>
				<?php
$_smarty_tpl->tpl_vars['i'] = $__foreach_i_9_saved_local_item;
}
if ($__foreach_i_9_saved_item) {
$_smarty_tpl->tpl_vars['i'] = $__foreach_i_9_saved_item;
}
?>
			</table>

			<h3>Sort order</h3>

			<div class="form-group">
				<input type="text" name="pricing[new][sortOrder]" value="<?php echo count($_smarty_tpl->tpl_vars['pricing']->value)+1;?>
" />
			</div>

		</div>
		<div class="tab-pane" id="settings">
				<table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
					<tbody>
					<tr>
						<td class="fieldlabel">Default Payment Method</td>
						<td class="fieldarea">
							<select name="settings[payment_gateway]" class="form-control select-inline">
								<?php
$_from = $_smarty_tpl->tpl_vars['availablePaymentMethods']->value;
if (!is_array($_from) && !is_object($_from)) {
settype($_from, 'array');
}
$__foreach_title_10_saved_item = isset($_smarty_tpl->tpl_vars['title']) ? $_smarty_tpl->tpl_vars['title'] : false;
$__foreach_title_10_saved_key = isset($_smarty_tpl->tpl_vars['method']) ? $_smarty_tpl->tpl_vars['method'] : false;
$_smarty_tpl->tpl_vars['title'] = new Smarty_Variable();
$_smarty_tpl->tpl_vars['method'] = new Smarty_Variable();
$_smarty_tpl->tpl_vars['title']->_loop = false;
foreach ($_from as $_smarty_tpl->tpl_vars['method']->value => $_smarty_tpl->tpl_vars['title']->value) {
$_smarty_tpl->tpl_vars['title']->_loop = true;
$__foreach_title_10_saved_local_item = $_smarty_tpl->tpl_vars['title'];
?>
									<option value="<?php echo $_smarty_tpl->tpl_vars['method']->value;?>
" <?php if ($_smarty_tpl->tpl_vars['method']->value == $_smarty_tpl->tpl_vars['settings']->value['payment_gateway']) {?>selected="selected"<?php }?>><?php echo $_smarty_tpl->tpl_vars['title']->value;?>
</option>
								<?php
$_smarty_tpl->tpl_vars['title'] = $__foreach_title_10_saved_local_item;
}
if ($__foreach_title_10_saved_item) {
$_smarty_tpl->tpl_vars['title'] = $__foreach_title_10_saved_item;
}
if ($__foreach_title_10_saved_key) {
$_smarty_tpl->tpl_vars['method'] = $__foreach_title_10_saved_key;
}
?>
							</select>
							<br>
							The default method which will be used by plugin. If something wrong with payment method -
							plugin will use first one
						</td>
					</tr>
					</tbody>
				</table>

		</div>
	</div>

	<div class="btn-container" style="text-align: left">
		<input class="btn btn-success" type="submit" value="Save">
	</div>
</form><?php }
}
