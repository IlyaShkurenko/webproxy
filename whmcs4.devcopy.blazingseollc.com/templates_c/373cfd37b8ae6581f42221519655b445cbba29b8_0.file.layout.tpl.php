<?php
/* Smarty version 3.1.29, created on 2018-02-09 14:09:20
  from "/srv/www/whmcs4.devcopy.blazingseollc.com/templates/six/oauth/layout.tpl" */

if ($_smarty_tpl->smarty->ext->_validateCompiled->decodeProperties($_smarty_tpl, array (
  'has_nocache_code' => false,
  'version' => '3.1.29',
  'unifunc' => 'content_5a7dab90f1fd32_94640176',
  'file_dependency' => 
  array (
    '373cfd37b8ae6581f42221519655b445cbba29b8' => 
    array (
      0 => '/srv/www/whmcs4.devcopy.blazingseollc.com/templates/six/oauth/layout.tpl',
      1 => 1500305316,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_5a7dab90f1fd32_94640176 ($_smarty_tpl) {
$template = $_smarty_tpl;
?><!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="<?php echo $_smarty_tpl->tpl_vars['charset']->value;?>
">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $_smarty_tpl->tpl_vars['requestedAction']->value;?>
 - <?php echo $_smarty_tpl->tpl_vars['companyname']->value;?>
</title>

    <link href="<?php echo $_smarty_tpl->tpl_vars['WEB_ROOT']->value;?>
/templates/<?php echo $_smarty_tpl->tpl_vars['template']->value;?>
/css/all.min.css" rel="stylesheet">
    <link href="<?php echo $_smarty_tpl->tpl_vars['WEB_ROOT']->value;?>
/templates/<?php echo $_smarty_tpl->tpl_vars['template']->value;?>
/css/custom.css" rel="stylesheet" >
    <link href="<?php echo $_smarty_tpl->tpl_vars['WEB_ROOT']->value;?>
/templates/<?php echo $_smarty_tpl->tpl_vars['template']->value;?>
/oauth/css/style.css" rel="stylesheet">

    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
      <?php echo '<script'; ?>
 src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"><?php echo '</script'; ?>
>
      <?php echo '<script'; ?>
 src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"><?php echo '</script'; ?>
>
    <![endif]-->
  </head>
  <body>

    <section id="header">
        <div class="container">
            <img src="<?php echo $_smarty_tpl->tpl_vars['logo']->value;?>
" />
            <div class="pull-right text-right">
                <?php if ($_smarty_tpl->tpl_vars['loggedin']->value) {?>
                    <form method="post" action="<?php echo $_smarty_tpl->tpl_vars['issuerurl']->value;?>
oauth/authorize.php" id="frmLogout">
                        <input type="hidden" name="logout" value="1"/>
                        <input type="hidden" name="request_hash" value="<?php echo $_smarty_tpl->tpl_vars['request_hash']->value;?>
"/>
                        <p>
                            <?php echo WHMCS\Smarty::langFunction(array('key'=>'oauth.currentlyLoggedInAs','firstName'=>$_smarty_tpl->tpl_vars['loggedinuser']->value['firstname'],'lastName'=>$_smarty_tpl->tpl_vars['loggedinuser']->value['lastname']),$_smarty_tpl);?>
.
                            <a href="#" onclick="jQuery('#frmLogout').submit()"><?php echo WHMCS\Smarty::langFunction(array('key'=>'oauth.notYou'),$_smarty_tpl);?>
</a>
                        </p>
                    </form>
                <?php }?>
                <form method="post" action="<?php echo $_smarty_tpl->tpl_vars['issuerurl']->value;?>
oauth/authorize.php" id="frmCancelLogin">
                    <input type="hidden" name="return_to_app" value="1"/>
                    <input type="hidden" name="request_hash" value="<?php echo $_smarty_tpl->tpl_vars['request_hash']->value;?>
"/>
                    <button type="submit" class="btn btn-default">
                        <?php echo WHMCS\Smarty::langFunction(array('key'=>'oauth.returnToApp','appName'=>$_smarty_tpl->tpl_vars['appName']->value),$_smarty_tpl);?>

                    </button>
                </form>
            </div>
        </div>
    </section>

    <section id="content">
        <?php echo $_smarty_tpl->tpl_vars['content']->value;?>

    </section>

    <section id="footer">
        <?php echo WHMCS\Smarty::langFunction(array('key'=>'oauth.copyrightFooter','dateYear'=>$_smarty_tpl->tpl_vars['date_year']->value,'companyName'=>$_smarty_tpl->tpl_vars['companyname']->value),$_smarty_tpl);?>

    </section>

    <?php echo '<script'; ?>
 src="<?php echo $_smarty_tpl->tpl_vars['WEB_ROOT']->value;?>
/templates/<?php echo $_smarty_tpl->tpl_vars['template']->value;?>
/js/scripts.min.js"><?php echo '</script'; ?>
>
  </body>
</html>
<?php }
}
