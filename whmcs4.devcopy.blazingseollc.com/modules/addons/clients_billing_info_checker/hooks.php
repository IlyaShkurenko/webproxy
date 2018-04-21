<?php

/**
 * Hook for WHMCS of "Clients billing info checker" Module
 *
 * @author Ruslan Ivanov
 * @author And <and.webdev[at]gmail.com>
 */

use WHMCS\Database\Capsule;

/*ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);*/

$isUserDataFilledUp = function($userId) {
    $userinfo = Capsule::table('tblclients')->find($userId);

    if(is_array($userinfo)) {
        $userinfo = json_decode(json_encode($userinfo));
    }

    // Something wrong with that user, but don't care much
    if (empty($userinfo->id)) {
        return true;
    }

    // Check against all the required fields
    foreach (['address1', 'city', 'state', 'postcode', 'country'] as $field) {
        if (empty($userinfo->$field)) {
            return false;
        }
    }

    return true;
};

$showTemplate = function() {
    $systemURL = Capsule::table('tblconfiguration')->where('setting', 'SystemURL')->value('value');

    // show notification template
    include_once( dirname(__FILE__) . '/templates/notify.php' );

    // stop WHMCS
    die();
};

add_hook('ClientAreaHeaderOutput', 1, function($vars) use ($isUserDataFilledUp, $showTemplate) {

    if(!isset($_SESSION['clients_billing_info_checker_first_referer'])) {

        $_SESSION['clients_billing_info_checker_first_referer'] = $_SERVER['HTTP_REFERER'];

    }

    if($vars['loggedin']) {
        // if user have not the required billing information
        if(!$isUserDataFilledUp($_SESSION['uid'])) {

            if(!isset($_SESSION['clients_billing_info_checker_referer'])) {

                if(isset($_SESSION['clients_billing_info_checker_first_referer'])) {

                    $_SESSION['clients_billing_info_checker_referer'] = $_SESSION['clients_billing_info_checker_first_referer'];

                } else {

                    $_SESSION['clients_billing_info_checker_referer'] = $_SERVER['HTTP_REFERER'];

                }

            }

            // if current page not is a client details page and user logged in
            if(!preg_match('/\/clientarea\.php\?action\=details/', $vars['currentpagelinkback'])) {
                $showTemplate();
            }

        } else {

            if(isset($_SESSION['clients_billing_info_checker_referer'])) {

                $location = $_SESSION['clients_billing_info_checker_referer'];
                unset($_SESSION['clients_billing_info_checker_referer']);
                header("location: " . $location);

            }

        }

    }

});

add_hook('ClientLogin', 0, function($vars) {
    if (preg_match('~Validate~', $_SERVER['REQUEST_URI'])) {
        return;
    }

    // die(json_encode($_REQUEST));
});

add_hook('ClientAuthOnCheck', 0, function($vars) use ($isUserDataFilledUp, $showTemplate) {
    // Not authorized
    if (empty($vars['id'])) {
        return;
    }

    if (!$isUserDataFilledUp($vars['id'])) {
        $_SESSION['clients_billing_info_checker_referer'] = $vars['callbackUrl'];
        $showTemplate();
    }
});

add_hook('ClientAuthOnSignIn', 0, function($vars) use ($isUserDataFilledUp, $showTemplate) {
    // Not authorized
    if (empty($vars['id'])) {
        return;
    }

    if (!$isUserDataFilledUp($vars['id'])) {
        $_SESSION['clients_billing_info_checker_referer'] = $vars['callbackUrl'];
        $showTemplate();
    }
});