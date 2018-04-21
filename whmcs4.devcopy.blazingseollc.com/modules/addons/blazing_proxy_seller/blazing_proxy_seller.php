<?php

use WHMCS\Module\Blazing\Proxy\Seller\Events;
use WHMCS\Module\Blazing\Proxy\Seller\ProxyTypes;
use WHMCS\Module\Framework\Addon;
use WHMCS\Module\Framework\Events\AbstractModuleListener;
use WHMCS\Module\Framework\Events\CallbackModuleListener;
use WHMCS\Module\Framework\Helper;

require_once __DIR__ . '/bootstrap.php';

return Addon::registerModuleByFile(__FILE__, [
    'name'        => 'Blazing Proxy Seller',
    'description' => 'Configures proxies pricing tiers, manages products with variable quantity, and much more other features',
    'version'     => '1.1',
    'author'      => 'Blazing',
    'fields'      => [
        'dashboardMode' => [
            'FriendlyName' => 'Dashboard Mode',
            'Type' => 'dropdown',
            'Options' => Addon::isModuleEnabled('blazing_proxy_billing_dashboard') ?
                'Internal,External' : 'External',
            'Description' => '<br>Choose <strong>External</strong> option if you use the Proxy Dashboard on separate domain.' .
                '<br>Otherwise you should choose <strong>Internal</strong>, ' .
                'and in this case you should have <strong>Blazing Proxy Billing Dashboard</strong> addon installed.' .
                '<br>If you are not sure what option is best for you contact with us',
            'Default' => Addon::isModuleEnabled('blazing_proxy_billing_dashboard') ? 'Internal' : 'External'
        ],
        'proxyDashboardUrl' => [
            'FriendlyName' => 'Proxy Dashboard Url',
            'Type' => 'text',
            'Size' => 75,
            'Description' => '<br>Should be configured if the Dashboard Mode is <strong>External</strong>. Otherwise please left this field empty' .
                '<br>The dashboard url, without trailing slash. Example: http://blazingseollc.com/proxy' .
                '',
            'Default' => 'http://blazingseollc.com/proxy'
        ]
    ],
    'config'      => [
        'logPath' => __DIR__ . '/logs/user-{user}.log',
    ]
])->registerModuleListeners([
    Events\DashboardController::class,
    CallbackModuleListener::createCallback('activate', function() {
        /** @var AbstractModuleListener $this */
        $moduleId = $this->getModule()->getId();

        // Rename previous tables
        foreach ([
            'mod_blazing_dashboard_proxy_pricing' => "mod_{$moduleId}_pricing",
            'mod_blazing_dashboard_proxy_services' => "mod_{$moduleId}_services",
            'mod_blazing_dashboard_proxy_settings' => "mod_{$moduleId}_settings",
        ] as $before => $after) {
            try {
                $this->db()->selectOne("SELECT 1 FROM `$before`");

                // Table is exists, rename it
                $this->db()->getPdo()->exec("RENAME TABLE `$before` TO `$after`");
            }
            catch (\Exception $e) {}
        }
        $this->db()->getPdo()->exec("UPDATE tblproducts SET servertype = '$moduleId' WHERE servertype = 'blazing_dashboard_proxy'");

        // Create module tables

        $tableName = "mod_{$moduleId}_pricing";
        try {
            $this->db()->selectOne("SELECT 1 FROM `$tableName`");
        }
        catch (\Exception $e) {
            $this->db()->getPdo()->exec("
            CREATE TABLE `$tableName` (
              `product_id` int(11) NOT NULL,
              `quantity_from` int(11) NOT NULL,
              `price` float NOT NULL,
              `country` varchar(32) NOT NULL,
              `category` varchar(32) NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
            ");
        }
        try {
            $this->db()->selectOne("SELECT sort_order FROM `$tableName`");
        }
        catch (\Exception $e) {
            $this->db()->getPdo()->exec("ALTER TABLE `$tableName` ADD `sort_order` int(4) DEFAULT 1");
        }
        try {
            $this->db()->selectOne("SELECT `data` FROM `$tableName`");
        }
        catch (\Exception $e) {
            $this->db()->getPdo()->exec("ALTER TABLE `$tableName` ADD `data` TEXT DEFAULT NULL");
        }
        try {
            $this->db()->selectOne("SELECT `type` FROM `$tableName`");
        }
        catch (\Exception $e) {
            $this->db()->getPdo()->exec("ALTER TABLE `$tableName` 
              ADD `ip_v` SET('4', '6') NOT NULL AFTER price, 
              ADD `type` VARCHAR(64) NOT NULL AFTER ip_v, 
              ADD ext TEXT DEFAULT NULL AFTER category,
              MODIFY `country` varchar(32),
              MODIFY `category` varchar(32)");
            $this->db()->exec("UPDATE `$tableName` SET ip_v = '4', type = 'single'");
        }

        $tableName = "mod_{$moduleId}_services";
        try {
            $this->db()->selectOne("SELECT 1 FROM `$tableName`");
        }
        catch (\Exception $e) {
            $this->db()->getPdo()->exec("
            CREATE TABLE `$tableName` (
              `id` int(11) NOT NULL,
              `user_id` int(11) NOT NULL,
              `product_id` int(11) NOT NULL,
              `quantity` int(11) NOT NULL,
              `data` text NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
            ");
            $this->db()->getPdo()->exec("ALTER TABLE `$tableName` ADD PRIMARY KEY (`id`);");
            $this->db()->getPdo()->exec("ALTER TABLE `$tableName` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;");
        }

        $tableName = "mod_{$moduleId}_settings";
        try {
            $this->db()->selectOne("SELECT 1 FROM `$tableName`");
        }
        catch (\Exception $e) {
            $this->db()->getPdo()->exec("
            CREATE TABLE `$tableName` (
              `key` varchar(32) NOT NULL,
              `data` text NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
            ");
            $this->db()->getPdo()->exec("ALTER TABLE `$tableName` ADD PRIMARY KEY (`key`);");
        }

        // Create proxy types (if not created yet)
        $groupName = 'Proxies';
        if (!$this->db()->selectOne("SELECT * FROM `tblproductgroups` WHERE `name` = ?", [$groupName])) {
            $result = $this->db()->insert("INSERT INTO `tblproductgroups` 
              (`name`, `headline`, `tagline`, `orderfrmtpl`, `disabledgateways`, `hidden`, `order`, 
              `created_at`, `updated_at`) 
              VALUES (?, NULL, NULL, '', '', '0', '0', 
              '0000-00-00 00:00:00.000000', '0000-00-00 00:00:00.000000')", [$groupName]);
            $groupId = $this->db()->getPdo()->lastInsertId();

            if ($groupId) {
                $available = ProxyTypes::expandAvailable();
                $defaultCurency = 1;
                $i = 0;
                foreach ([
                    ProxyTypes::COUNTRY_US      => [
                        ProxyTypes::CATEGORY_DEDICATED,
                        ProxyTypes::CATEGORY_SEMI_DEDICATED,
                        ProxyTypes::CATEGORY_ROTATING,
                        ProxyTypes::CATEGORY_SNEAKER
                    ],
                    ProxyTypes::COUNTRY_GERMANY => [ProxyTypes::CATEGORY_DEDICATED, ProxyTypes::CATEGORY_SEMI_DEDICATED, ProxyTypes::CATEGORY_ROTATING],
                    ProxyTypes::COUNTRY_BRAZIL  => [ProxyTypes::CATEGORY_DEDICATED, ProxyTypes::CATEGORY_SEMI_DEDICATED, ProxyTypes::CATEGORY_ROTATING],
                    ProxyTypes::COUNTRY_UK      => [ProxyTypes::CATEGORY_DEDICATED, ProxyTypes::CATEGORY_SNEAKER],
                ] as $country => $categories) {
                    foreach ($categories as $category) {
                        $found = false;
                        foreach ($available as $item) {
                            if ($item['country'] == $country and $item['category'] == $category) {
                                $found = true;
                                break;
                            }
                        }
                        if ($found) {
                            $dict = ProxyTypes::HUMAN_DICT;
                            if (!isset($dict[ 'country' ][ $country ]) or !isset($dict[ 'category' ][ $category ])) {
                                continue;
                            }

                            $name = sprintf('%s %s Proxies', $dict[ 'country' ][ $country ], $dict[ 'category' ][ $category ]);
                            Helper::api('addProduct', [
                                'type'        => 'other',
                                'module'      => 'blazing_proxy_seller',
                                'gid'         => $groupId,
                                'name'        => $name,
                                'paytype'     => 'recurring',
                                'hidden'      => 0,
                                'order'       => $i++,
                                'pricing'     => [$defaultCurency => ['monthly' => 0]],
                                'description' => ''
                            ]);
                        }
                    }
                }

            }

        }
    })
]);