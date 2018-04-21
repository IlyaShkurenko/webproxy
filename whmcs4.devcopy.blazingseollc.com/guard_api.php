<?php

include('init.php');

use WHMCS\Database\Capsule;

try {
    // check referer set in headers and request method is post
    if(!isset($_SERVER["HTTP_REFERER"]) or $_SERVER['REQUEST_METHOD'] !== 'POST') {
        header("Location: /");
        die();
    }

    $caller = new GuardianApiCaller();

    $url = preg_quote('/viewinvoice.php?id=');
    // if referer a paid invoice point - check email in banlist on guardian server
    if(preg_match('%' . $url . '%', $_SERVER["HTTP_REFERER"])) {
        if(preg_match('/^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/i', $_POST['email'])) {
            $result = $caller->checkEmailInBanList($_POST['email']);

            $check = json_decode($result);

            if($check->result) {

                $adminUsername = Capsule::table('tbladmins')->where('disabled', 0)->where('roleid', 1)->value('username');

                $postData = [
                    'ip' => $_SERVER['REMOTE_ADDR'],
                    'reason' => 'Ban by reason: PayPal in emails ban list',
                    'days' => '38600',
                ];

                $results = localAPI('AddBannedIp', $postData, $adminUsername);
            }

            header('Content-Type: application/json');
            echo $result;
        }
    }
} catch (Exception $e) {
    // send warn email to admin or developer of the module (fail in module code)
}
