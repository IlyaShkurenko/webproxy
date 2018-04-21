<?php

require_once __DIR__ . '/bootstrap.php';

\WHMCS\Module\Framework\Addon::registerModuleByFile(__FILE__,
    [
        'name' => 'Module terminate email',
        'description' => 'Emails users after their module has been terminated.<br>'
            . 'Next variables can be used inside an email template for inserting service data:'
            . '<ul>'
            . '<li>{$service_name} - name of a terminated service.</li>'
            . '<li>{$service_groupname} - group name of a terminated service.</li>'
            . '<li>{$service_domain} - a terminated service domain name.</li>'
            . '</ul>',
        'version' => '1.0',
        'author' => 'Blazing',
        'fields' => [
            'templateName' => [
                'FriendlyName' => 'Template name',
                'Type' => 'text',
                'Size' => '255',
                'Description' => 'Template name which will be used for mailing users',
                'Default' => '',
            ],
        ]
    ]
);
