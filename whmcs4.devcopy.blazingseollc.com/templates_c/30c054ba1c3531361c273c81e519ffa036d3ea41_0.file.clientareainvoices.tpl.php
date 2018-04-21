<?php
/* Smarty version 3.1.29, created on 2018-02-09 16:18:55
  from "/srv/www/whmcs4.devcopy.blazingseollc.com/templates/six/clientareainvoices.tpl" */

if ($_smarty_tpl->smarty->ext->_validateCompiled->decodeProperties($_smarty_tpl, array (
  'has_nocache_code' => false,
  'version' => '3.1.29',
  'unifunc' => 'content_5a7dc9ef8b0642_83816451',
  'file_dependency' => 
  array (
    '30c054ba1c3531361c273c81e519ffa036d3ea41' => 
    array (
      0 => '/srv/www/whmcs4.devcopy.blazingseollc.com/templates/six/clientareainvoices.tpl',
      1 => 1500305316,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_5a7dc9ef8b0642_83816451 ($_smarty_tpl) {
$template = $_smarty_tpl;
$_smarty_tpl->smarty->ext->_subtemplate->render($_smarty_tpl, ((string)$_smarty_tpl->tpl_vars['template']->value)."/includes/tablelist.tpl", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array('tableName'=>"InvoicesList",'filterColumn'=>"4"), 0, true);
?>

<?php echo '<script'; ?>
 type="text/javascript">
    jQuery(document).ready( function ()
    {
        var table = jQuery('#tableInvoicesList').removeClass('hidden').DataTable();
        <?php if ($_smarty_tpl->tpl_vars['orderby']->value == 'default') {?>
            table.order([4, 'desc'], [2, 'asc']);
        <?php } elseif ($_smarty_tpl->tpl_vars['orderby']->value == 'invoicenum') {?>
            table.order(0, '<?php echo $_smarty_tpl->tpl_vars['sort']->value;?>
');
        <?php } elseif ($_smarty_tpl->tpl_vars['orderby']->value == 'date') {?>
            table.order(1, '<?php echo $_smarty_tpl->tpl_vars['sort']->value;?>
');
        <?php } elseif ($_smarty_tpl->tpl_vars['orderby']->value == 'duedate') {?>
            table.order(2, '<?php echo $_smarty_tpl->tpl_vars['sort']->value;?>
');
        <?php } elseif ($_smarty_tpl->tpl_vars['orderby']->value == 'total') {?>
            table.order(3, '<?php echo $_smarty_tpl->tpl_vars['sort']->value;?>
');
        <?php } elseif ($_smarty_tpl->tpl_vars['orderby']->value == 'status') {?>
            table.order(4, '<?php echo $_smarty_tpl->tpl_vars['sort']->value;?>
');
        <?php }?>
        table.draw();
        jQuery('#tableLoading').addClass('hidden');
    });
<?php echo '</script'; ?>
>

<div class="table-container clearfix">
    <table id="tableInvoicesList" class="table table-list hidden">
        <thead>
            <tr>
                <th><?php echo $_smarty_tpl->tpl_vars['LANG']->value['invoicestitle'];?>
</th>
                <th><?php echo $_smarty_tpl->tpl_vars['LANG']->value['invoicesdatecreated'];?>
</th>
                <th><?php echo $_smarty_tpl->tpl_vars['LANG']->value['invoicesdatedue'];?>
</th>
                <th><?php echo $_smarty_tpl->tpl_vars['LANG']->value['invoicestotal'];?>
</th>
                <th><?php echo $_smarty_tpl->tpl_vars['LANG']->value['invoicesstatus'];?>
</th>
                <th class="responsive-edit-button" style="display: none;"></th>
            </tr>
        </thead>
        <tbody>
            <?php
$_from = $_smarty_tpl->tpl_vars['invoices']->value;
if (!is_array($_from) && !is_object($_from)) {
settype($_from, 'array');
}
$__foreach_invoice_0_saved_item = isset($_smarty_tpl->tpl_vars['invoice']) ? $_smarty_tpl->tpl_vars['invoice'] : false;
$__foreach_invoice_0_saved_key = isset($_smarty_tpl->tpl_vars['num']) ? $_smarty_tpl->tpl_vars['num'] : false;
$_smarty_tpl->tpl_vars['invoice'] = new Smarty_Variable();
$_smarty_tpl->tpl_vars['num'] = new Smarty_Variable();
$_smarty_tpl->tpl_vars['invoice']->_loop = false;
foreach ($_from as $_smarty_tpl->tpl_vars['num']->value => $_smarty_tpl->tpl_vars['invoice']->value) {
$_smarty_tpl->tpl_vars['invoice']->_loop = true;
$__foreach_invoice_0_saved_local_item = $_smarty_tpl->tpl_vars['invoice'];
?>
                <tr onclick="clickableSafeRedirect(event, 'viewinvoice.php?id=<?php echo $_smarty_tpl->tpl_vars['invoice']->value['id'];?>
', false)">
                    <td><?php echo $_smarty_tpl->tpl_vars['invoice']->value['invoicenum'];?>
</td>
                    <td><span class="hidden"><?php echo $_smarty_tpl->tpl_vars['invoice']->value['normalisedDateCreated'];?>
</span><?php echo $_smarty_tpl->tpl_vars['invoice']->value['datecreated'];?>
</td>
                    <td><span class="hidden"><?php echo $_smarty_tpl->tpl_vars['invoice']->value['normalisedDateDue'];?>
</span><?php echo $_smarty_tpl->tpl_vars['invoice']->value['datedue'];?>
</td>
                    <td data-order="<?php echo $_smarty_tpl->tpl_vars['invoice']->value['totalnum'];?>
"><?php echo $_smarty_tpl->tpl_vars['invoice']->value['total'];?>
</td>
                    <td><span class="label status status-<?php echo $_smarty_tpl->tpl_vars['invoice']->value['statusClass'];?>
"><?php echo $_smarty_tpl->tpl_vars['invoice']->value['status'];?>
</span></td>
                    <td class="responsive-edit-button" style="display: none;">
                        <a href="viewinvoice.php?id=<?php echo $_smarty_tpl->tpl_vars['invoice']->value['id'];?>
" class="btn btn-block btn-info">
                            <?php echo $_smarty_tpl->tpl_vars['LANG']->value['manageproduct'];?>

                        </a>
                    </td>
                </tr>
            <?php
$_smarty_tpl->tpl_vars['invoice'] = $__foreach_invoice_0_saved_local_item;
}
if ($__foreach_invoice_0_saved_item) {
$_smarty_tpl->tpl_vars['invoice'] = $__foreach_invoice_0_saved_item;
}
if ($__foreach_invoice_0_saved_key) {
$_smarty_tpl->tpl_vars['num'] = $__foreach_invoice_0_saved_key;
}
?>
        </tbody>
    </table>
    <div class="text-center" id="tableLoading">
        <p><i class="fa fa-spinner fa-spin"></i> <?php echo $_smarty_tpl->tpl_vars['LANG']->value['loading'];?>
</p>
    </div>
</div>
<?php }
}
