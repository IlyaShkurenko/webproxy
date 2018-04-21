<?php

/** @var Application $app */

use Silex\Application;

$config = [
    'view.theme' => defined('THEME') ? THEME : 'default',

    // Various links
    'view.link.help' => defined('LINK_HELP') ? LINK_HELP : false,
    'view.link.pp' => defined('LINK_PP') ? LINK_PP : false,
    'view.link.checkout.tos' => defined('LINK_TOS') ? LINK_TOS : false,
    'view.link.checkout.aup' => defined('LINK_AUP') ? LINK_AUP : false,
    'view.link.checkout.no_refund' => defined('LINK_NO_REFUND') ? LINK_NO_REFUND : false,

    // Put these files in app/config
    'view.layout.prepend_head' => ($file = __DIR__ . '/view_head_prepend.html.twig' AND is_file($file)) ? function($app) use ($file) {
        return $app['twig']->render(preg_replace('~^.+/app/~', '', str_replace('\\', '/', $file)));
    } : false,
    'view.layout.append_head' => ($file = __DIR__ . '/view_head_append.html.twig' AND is_file($file)) ? function($app) use ($file) {
        return $app['twig']->render(preg_replace('~^.+/app/~', '', str_replace('\\', '/', $file)));
    } : false,
    'view.layout.prepend_content' => ($file = __DIR__ . '/view_body_prepend.html.twig' AND is_file($file)) ? function($app) use ($file) {
        return $app['twig']->render(preg_replace('~^.+/app/~', '', str_replace('\\', '/', $file)));
    } : false,
    'view.layout.append_content' => ($file = __DIR__ . '/view_body_append.html.twig' AND is_file($file)) ? function($app) use ($file) {
        return $app['twig']->render(preg_replace('~^.+/app/~', '', str_replace('\\', '/', $file)));
    } : false,

    // Affects only app in simple view mode
    'view.layout.include_scripts' => true,
    'view.layout.include_scripts_app' => true,

    // Sidebar urls
    'view.sidebar.contact' => true,
    'view.sidebar.admin'   => false,

    // Words
    'view.text.company_title' => defined('TEXT_COMPANY_TITLE') ? TEXT_COMPANY_TITLE : 'Blazing Proxies',

    // Aliases
    'view.feature.amember' => isset($app['config.amember.loginUrl'])
];

// Copy captcha variables
$configCommon = require __DIR__ . '/common.php';
foreach ($configCommon as $key => $value) {
    if (0 === strpos($key, 'captcha.')) {
        $config["view.$key"] = $value;
    }
}

return $config;