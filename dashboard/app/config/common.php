<?php

return call_user_func(function() {
    // 1st pass
    $config = [
        'debug'    => defined('DEBUG') ? DEBUG : false,
        'port.ip'  => defined('IP_PORT') ? IP_PORT : 3128,
        'port.pwd' => defined('PW_PORT') ? PW_PORT : 4444,
        'log.path' => __DIR__ . '/../../logs/dashboard.log',

        'maintenance.login'   => false,
        'maintenance.message' => "<strong>Maintenance</strong><br><br>We are performing maintenance on our proxy backend. Rest assured, your actual proxies will continue to work without any issues. This maintenance is only to update some user interface in the dashboard that we need to keep people out. We expect this maintenance to be concluded in the next 2-5 hours.<br><br>Thank you for your patience",

        'captcha.enabled'  => defined('CAPTCHA_KEY') and defined('CAPTCHA_SECRET'),
    ];

    // 2nd pass
    if ($config['captcha.enabled']) {
        $config += [
            'captcha.key' => CAPTCHA_KEY,
            'captcha.secret' => CAPTCHA_SECRET,

            // All the available pages
            'captcha.page.signin' => true,
            'captcha.page.signup' => true,
        ];

        if (defined('CAPTCHA_PAGES')) {
            $pages = explode(',', CAPTCHA_PAGES);
            $config['captcha.page.signin'] = in_array('signin', $pages);
            $config['captcha.page.signup'] = in_array('signup', $pages);
        }
    }

    return $config;
});
