<?php

require __DIR__ . '/bootstrap.php';

try {
	$data = EasyAuth\getRequestData(true);
	EasyAuth\assertHasData($data, 'username', 'Wrong request: no username passed');
	EasyAuth\assertHasData($data, 'password', 'Wrong request: no password passed');
	EasyAuth\assertHasData($data, 'url.success', 'Wrong request: no success redirect url passed');
}
catch (\Exception $e) {
	EasyAuth\handleException($e);
}

$scriptPath = $_SERVER['SCRIPT_NAME'];
$webrootPath = preg_replace('~/modules/.+~', '', $scriptPath);

?>
<h2><center><?= $data['text']['pending'] ?></center></h2>
<form action="<?= $webrootPath ?>/dologin.php" id="auth" method="POST">
	<input type="hidden" name="token" value="<?= generate_token('plain') ?>"/>
	<input type="hidden" name="username" value="<?= $data['username']  ?>"/>
	<input type="hidden" name="password" value="<?= $data['password'] ?>" />
	<input type="hidden" name="rememberme" value="1" />
	<input type="hidden" name="goto" value="<?= $webrootPath ?>/modules/addons/easy_auth/redirect.php?<?= http_build_query(['data' => $_REQUEST['data']]) ?>" />
</form>
<script type="text/javascript">
	document.getElementById('auth').submit();
</script>