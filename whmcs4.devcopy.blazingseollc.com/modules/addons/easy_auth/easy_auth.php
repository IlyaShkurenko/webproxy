<?php


use WHMCS\Module\Framework\Addon;

require_once __DIR__ . '/bootstrap.php';

return Addon::registerModuleByFile(__FILE__, [
    'name'        => 'Easy Auth',
    'description' => 'This addon utily allows customers be authorized by email/password pair and then be redirected to success/fail page',
    'version'     => '1.0',
    'author'      => 'And <and.webdev@gmail.com>',
    'fields'      => [],
    'requestScheme' => [
        'username' => '',
        'password' => '',
        'url' => [
            'success' => '',
            'fail' => ''
        ],
        'text' => [
            'pending' => 'Signing In...',
            'error' => 'Credentials are incorrect!'
        ]
    ]
]);