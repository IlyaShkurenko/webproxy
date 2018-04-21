<?php
/* Smarty version 3.1.29, created on 2018-02-09 16:20:36
  from "/srv/www/whmcs4.devcopy.blazingseollc.com/templates/six/oauth/login.tpl" */

if ($_smarty_tpl->smarty->ext->_validateCompiled->decodeProperties($_smarty_tpl, array (
  'has_nocache_code' => false,
  'version' => '3.1.29',
  'unifunc' => 'content_5a7dca54ddcd21_35832224',
  'file_dependency' => 
  array (
    '3d78509d3d5e1eeb47bbb7a5a9b539ebd75172fb' => 
    array (
      0 => '/srv/www/whmcs4.devcopy.blazingseollc.com/templates/six/oauth/login.tpl',
      1 => 1500305316,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_5a7dca54ddcd21_35832224 ($_smarty_tpl) {
$template = $_smarty_tpl;
?><div class="content-container">

    <?php if ($_smarty_tpl->tpl_vars['appLogo']->value) {?>
        <div class="app-logo">
            <img src="<?php echo $_smarty_tpl->tpl_vars['appLogo']->value;?>
" />
        </div>
    <?php }?>

    <h2 class="text-center"><?php echo WHMCS\Smarty::langFunction(array('key'=>'oauth.loginToGrantApp','appName'=>$_smarty_tpl->tpl_vars['appName']->value),$_smarty_tpl);?>
</h2>

    <form method="post" action="<?php echo $_smarty_tpl->tpl_vars['issuerurl']->value;?>
dologin.php" role="form">
        <div class="content-padded">

            <?php if ($_smarty_tpl->tpl_vars['incorrect']->value) {?>
                <?php $_smarty_tpl->smarty->ext->_subtemplate->render($_smarty_tpl, ((string)$_smarty_tpl->tpl_vars['template']->value)."/includes/alert.tpl", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array('type'=>"error",'msg'=>$_smarty_tpl->tpl_vars['LANG']->value['loginincorrect'],'textcenter'=>true), 0, true);
?>

            <?php }?>

            <div class="form-group">
                <label for="inputEmail"><?php echo $_smarty_tpl->tpl_vars['LANG']->value['clientareaemail'];?>
</label>
                <input type="email" name="username" class="form-control" id="inputEmail" placeholder="<?php echo $_smarty_tpl->tpl_vars['LANG']->value['enteremail'];?>
" autofocus>
            </div>

            <div class="form-group">
                <label for="inputPassword"><?php echo $_smarty_tpl->tpl_vars['LANG']->value['clientareapassword'];?>
</label>
                <input type="password" name="password" class="form-control" id="inputPassword" placeholder="<?php echo $_smarty_tpl->tpl_vars['LANG']->value['clientareapassword'];?>
" autocomplete="off" >
            </div>

        </div>

        <div class="action-buttons">
            <div class="pull-left">
                <div class="checkbox">
                    <label>
                        <input type="checkbox" name="rememberme" /> <?php echo $_smarty_tpl->tpl_vars['LANG']->value['loginrememberme'];?>

                    </label>
                    &bull;
                    <a href="<?php echo $_smarty_tpl->tpl_vars['issuerurl']->value;?>
pwreset.php"><?php echo WHMCS\Smarty::langFunction(array('key'=>'forgotpw'),$_smarty_tpl);?>
</a>
                </div>
            </div>
            <button type="submit" class="btn btn-primary" id="btnLogin">
                <?php echo WHMCS\Smarty::langFunction(array('key'=>'login'),$_smarty_tpl);?>

            </button>
            <button type="button" class="btn btn-default" id="btnCancel" onclick="jQuery('#frmCancelLogin').submit()">
                <?php echo WHMCS\Smarty::langFunction(array('key'=>'cancel'),$_smarty_tpl);?>

            </button>
        </div>

    </form>

</div>

<form method="post" action="<?php echo $_smarty_tpl->tpl_vars['issuerurl']->value;?>
oauth/authorize.php" id="frmCancelLogin">
    <input type="hidden" name="login_declined" value="yes"/>
    <input type="hidden" name="request_hash" value="<?php echo $_smarty_tpl->tpl_vars['request_hash']->value;?>
"/>
</form>
<?php }
}
