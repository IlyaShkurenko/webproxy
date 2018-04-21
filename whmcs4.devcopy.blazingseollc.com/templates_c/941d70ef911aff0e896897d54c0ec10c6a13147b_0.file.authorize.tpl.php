<?php
/* Smarty version 3.1.29, created on 2018-02-09 14:09:20
  from "/srv/www/whmcs4.devcopy.blazingseollc.com/templates/six/oauth/authorize.tpl" */

if ($_smarty_tpl->smarty->ext->_validateCompiled->decodeProperties($_smarty_tpl, array (
  'has_nocache_code' => false,
  'version' => '3.1.29',
  'unifunc' => 'content_5a7dab90e7c6d8_39927653',
  'file_dependency' => 
  array (
    '941d70ef911aff0e896897d54c0ec10c6a13147b' => 
    array (
      0 => '/srv/www/whmcs4.devcopy.blazingseollc.com/templates/six/oauth/authorize.tpl',
      1 => 1500305316,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_5a7dab90e7c6d8_39927653 ($_smarty_tpl) {
$template = $_smarty_tpl;
?><div class="content-container">

    <?php if ($_smarty_tpl->tpl_vars['appLogo']->value) {?>
        <div class="app-logo">
            <img src="<?php echo $_smarty_tpl->tpl_vars['appLogo']->value;?>
" />
        </div>
    <?php }?>

    <h2 class="text-center"><?php echo WHMCS\Smarty::langFunction(array('key'=>'oauth.authoriseAppToAccess','appName'=>$_smarty_tpl->tpl_vars['appName']->value),$_smarty_tpl);?>
</h2>

    <div class="content-padded">
        <div class="permission-grants">
            <p><?php echo WHMCS\Smarty::langFunction(array('key'=>'oauth.willBeAbleTo'),$_smarty_tpl);?>
:</p>
            <ul>
                <?php
$_from = $_smarty_tpl->tpl_vars['requestedPermissions']->value;
if (!is_array($_from) && !is_object($_from)) {
settype($_from, 'array');
}
$__foreach_permission_0_saved_item = isset($_smarty_tpl->tpl_vars['permission']) ? $_smarty_tpl->tpl_vars['permission'] : false;
$_smarty_tpl->tpl_vars['permission'] = new Smarty_Variable();
$_smarty_tpl->tpl_vars['permission']->_loop = false;
foreach ($_from as $_smarty_tpl->tpl_vars['permission']->value) {
$_smarty_tpl->tpl_vars['permission']->_loop = true;
$__foreach_permission_0_saved_local_item = $_smarty_tpl->tpl_vars['permission'];
?>
                    <li><?php echo $_smarty_tpl->tpl_vars['permission']->value;?>
</li>
                <?php
$_smarty_tpl->tpl_vars['permission'] = $__foreach_permission_0_saved_local_item;
}
if ($__foreach_permission_0_saved_item) {
$_smarty_tpl->tpl_vars['permission'] = $__foreach_permission_0_saved_item;
}
?>
            </ul>
        </div>
    </div>

    <form method="post" action="#" role="form">
        <?php
$_from = $_smarty_tpl->tpl_vars['requestedAuthorizations']->value;
if (!is_array($_from) && !is_object($_from)) {
settype($_from, 'array');
}
$__foreach_auth_1_saved_item = isset($_smarty_tpl->tpl_vars['auth']) ? $_smarty_tpl->tpl_vars['auth'] : false;
$_smarty_tpl->tpl_vars['auth'] = new Smarty_Variable();
$_smarty_tpl->tpl_vars['auth']->_loop = false;
foreach ($_from as $_smarty_tpl->tpl_vars['auth']->value) {
$_smarty_tpl->tpl_vars['auth']->_loop = true;
$__foreach_auth_1_saved_local_item = $_smarty_tpl->tpl_vars['auth'];
?>
            <input type="hidden" name="authz[]" value="<?php echo $_smarty_tpl->tpl_vars['auth']->value;?>
" />
        <?php
$_smarty_tpl->tpl_vars['auth'] = $__foreach_auth_1_saved_local_item;
}
if ($__foreach_auth_1_saved_item) {
$_smarty_tpl->tpl_vars['auth'] = $__foreach_auth_1_saved_item;
}
?>
        <div class="action-buttons">
            <button name="userAuthorization" id="userAuthorizationAccepted" value="yes" type="submit" class="btn btn-primary">
                <?php echo WHMCS\Smarty::langFunction(array('key'=>'oauth.authorise'),$_smarty_tpl);?>

            </button>
            <button name="userAuthorization" id="userAuthorizationDeclined" value="no" type="submit" class="btn btn-default">
                <?php echo WHMCS\Smarty::langFunction(array('key'=>'cancel'),$_smarty_tpl);?>

            </button>
        </div>
    </form>

</div>
<?php }
}
