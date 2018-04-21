<?php

/**
 * "Old billing migrator" module for WHMCS
 *
 * This addon module migrate Amember Users to WHMCS
 * @author Ruslan Ivanov
 */

use WHMCS\Database\Capsule;

/*ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);*/

/**
 * @return array
 */
function old_billing_migrator_config()
{

    $category = (int)Capsule::table('tblproducts')->where('name', 'USA Rotating Private Proxies')->value('id');
    if(!is_null($category)) {

        $categories_id = '<br><b>For you config:</b><code style="width:450px; word-wrap:break-word; display:inline-block;"> ';

        for ($i = 1; $i < 34; $i++) {

            $categories_id .= $i . ':' . $category . ',';
            $category++;
        }
    }

    $str = 'You categories special format for migrate process. Old category id : new category id in WHMCS and this groups must be coma separate. For example: 1:3,2:5,3:8 and etc.';
    if(isset($categories_id)) {
        
        $str .= $categories_id . '</code>';
    }

    $configarray = [
        "name" => "Old Billing Migrator",
        "description" => "This addon migrate users and their products to you whmcs for Email Marketer sending",
        "version" => "0.1",
        "author" => "Ruslan Ivanov",
        "fields" => [
            "host" => [
                "FriendlyName" => "host",
                "Type" => "text",
                "Size" => "15",
                "Description" => "You old billing mysql server host IP",
                "Default" => "127.0.0.1"
            ],
            "port" => [
                "FriendlyName" => "port",
                "Type" => "text",
                "Size" => "15",
                "Description" => "You old billing mysql server host port",
                "Default" => ""
            ],
            "dbuser" => [
                "FriendlyName" => "dbuser",
                "Type" => "text",
                "Size" => "15",
                "Description" => "You old billing mysql user name",
                "Default" => "db_user"
            ],
            "dbpassword" => [
                "FriendlyName" => "dbpassword",
                "Type" => "password",
                "Size" => "15",
                "Description" => "You old billing mysql user password",
                "Default" => "db_password"
            ],
            "dbname" => [
                "FriendlyName" => "dbname",
                "Type" => "text",
                "Size" => "15",
                "Description" => "You old billing mysql DB name",
                "Default" => "db_name"
            ],
            "limit" => [
                "FriendlyName" => "limit",
                "Type" => "text",
                "Size" => "15",
                "Description" => "Count users migrate for one step work module",
                "Default" => "10"
            ],
            "categories" => [
                "FriendlyName" => "categories",
                "Type" => "text",
                "Size" => "255",
                "Description" => $str,
                "Default" => "1:3,4:2"
            ],
        ]
    ];

    return $configarray;
}

/**
 * @param $vars
 * @return string
 */
function old_billing_migrator_output($vars)
{
    // check db config
    if(empty($vars['host']) or empty($vars['dbuser']) or empty($vars['dbpassword']) or empty($vars['dbname'])) {

        // error msg
        echo "<div class=\"infobox\"><strong><span class=\"title\">Will need module configure</span></strong><br>You must configure this module before using it. Please add DB host, username, password and DB name in module config.</div>";
        return '';
    }

    // try connect to DB server
    try {

        $db = new PDO('mysql:host=' . $vars['host'] . ';dbname=' . $vars['dbname'] . ';port=' . $vars['port'], $vars['dbuser'], $vars['dbpassword']);
    } catch (Exception $e) {

        echo "<div class=\"errorbox\"><strong><span class=\"title\">Error by try connect to database</span></strong><br>Please <a href='configaddonmods.php#old_billing_migrator' target='_blank' title='check'>check</a> you DB host, username, password and DB name to connect database</div>";
        return '';
    }

    // categories error
    $categories_errors = [];
    // parse categories
    $categories = [];

    // check categories format in config
    if(!preg_match('/^([0-9]+\:[0-9]+(\,)?)+$/', $vars['categories'])) {

        old_billing_migrator_print_error('Error in module config', 'Please <a href=\'configaddonmods.php#old_billing_migrator\' target=\'_blank\' title=\'check\'>correct</a> you categories list format - old_category_id:new_category_id! For example - 4:1,5:2,8:3');
        return '';
    }

    $categories_exist_errors = [];

    // check categories
    foreach (explode(',', $vars['categories']) as $category_group) {

        // parse categories config
        $category_group = explode(':', $category_group);

        // 0 - old category id, 1 - new category id
        $categories[$category_group[0]] = $category_group[1];

        // query to old billing db. get category info
        $cat_old = $db->prepare("SELECT * FROM `am_product_category` WHERE `product_category_id` = :id");
        $cat_old->bindValue(':id', (int)$category_group[0], PDO::PARAM_INT);
        $cat_old->execute();
        $cat_old = $cat_old->fetch();

        // query ti new billing db. get category info
        $cat_new = Capsule::table('tblproducts')->find((int)$category_group[1]);

        if(is_null($cat_new)) {
            $categories_exist_errors[] = 'Not exist new category ID - ' . $category_group[1] .  ' in WHMCS.';
        }

        if(is_null($cat_old)) {
            $categories_exist_errors[] = 'Not exist old category ID - ' . $category_group[0] .' in old billing.';
        }


        // if old category title != new category title add to errors
        if($cat_new->name != $cat_old['title']) {

            $categories_errors[] = $cat_new->name . ' != ' . $cat_old['title'] . ' (in config - ' . $category_group[0] . ':' . $category_group[1] .')';
        }
    }

    // if not exist categories - show error message
    if(!empty($categories_exist_errors)) {
        // create error message
        $err = "Categories IDs not exist. Please will <a href='configaddonmods.php#old_billing_migrator' target='_blank' title='check'>configure</a> categories in module config!<br><br><ul>";
        foreach ($categories_exist_errors as $error) {
            $err .= "<li>$error</li>";
        }
        $err .= "</ul>";

        old_billing_migrator_print_error('Attention!', $err);
        return '';
    }

    // if categories not match print error
    if(!empty($categories_errors)) {

        // create error message
        $err = "Categories names do not match. If this categories is match - don't worry about this error message, else please configure categories in module config!<br><br><ul>";
        foreach ($categories_errors as $error) {
            $err .= "<li>$error</li>";
        }
        $err .= "</ul>";

        old_billing_migrator_print_error('Attention!', $err);
    }

    if($_SERVER['REQUEST_METHOD'] == 'POST' and isset($_POST['skip'])) {

        $adminUsername = Capsule::table('tbladmins')->where('disabled', 0)->where('roleid', 1)->value('username');

        // set limit users count for one step of the migration process
        if(empty($vars['limit'])) {

            $limit = 10;
        } else {

            $limit = (int)$vars['limit'];
        }

        $skip = (int)$_POST['skip'];

        // if DB port not setted in config or it format is bad
        if(empty($vars['port']) or !preg_match('/^[0-9]{1,4}$/', $vars['port'])) {

            $vars['port'] = '3306';
        }

        $users_all = 0;
        if(empty($_POST['users_count'])) {
            $users_all = $db->prepare("SELECT COUNT(*) FROM `am_user`");
            $users_all->execute();
            $users_all = $users_all->fetch();
            $users_all = $users_all[0];
        }

        // build query get users
        $statement = $db->prepare("SELECT * FROM `am_user` LIMIT :lim OFFSET :skip");
        $statement->bindValue(':lim', $limit, PDO::PARAM_INT);
        $statement->bindValue(':skip', $skip, PDO::PARAM_INT);
        $statement->execute();

        // get users count for progress bar progress;
        $users = $statement->fetchAll();

        $users_count = 0;
        $products_count = 0;

        foreach($users as $user) {

            // check user exist
            $user_exist = Capsule::table('tblclients')->where('email', $user['email'])->first();

            if(empty($user_exist)) {

                // import user to WHMCS using API
                $postData = [
                    'firstname' => $user['name_f'],
                    'lastname' => $user['name_l'],
                    'email' => $user['email'],
                    'notes' => 'Imported from Amember',
                    /*'city' => 'Old Billing',
                    'address1' => 'Old Billing',
                    'state' => 'Old Billing',
                    'postcode' => 'Old Billing',
                    'country' => 'US',
                    'phonenumber' => '1234567',*/
                    'password2' => $user['pass'],
                    'noemail' => true,
                    'skipvalidation' => true,
                ];

                $results = localAPI('AddClient', $postData, $adminUsername);

                if($results['result'] == 'success') {

                    $userid = $results['clientid'];

                    $users_count++;

                    // update user signup date
                    Capsule::table('tblclients')->where('email', $user['email'])
                        ->update(['datecreated' => $user['added']]);
                } else {

                    continue;
                }

            } else {

                $userid = $user_exist->id;
            }

            // build  query get products  of the user
            $user_product = $db->prepare("SELECT * FROM am_access WHERE `user_id` = " . $user['user_id']);
            $user_product->execute();

            $user_product = $user_product->fetchAll();

            if(!empty($user_product)) {

                // import products to WHMCS
                $product_ids = [];

                foreach ($user_product as $product) {

                    // get category id
                    $category_id = $db->prepare("SELECT * FROM am_product_product_category WHERE `product_id` = " . $product['product_id']);
                    $category_id->execute();
                    $category_id = $category_id->fetch();

                    // check product exist
                    if(empty(Capsule::table('tblhosting')->where('userid', $userid)
                        ->where('packageid', $categories[$category_id['product_category_id']])->value('id'))) {

                        if(!is_null($categories[$category_id['product_category_id']])) {

                            // add product id to products ids array for mass import
                            $product_ids[] = $categories[$category_id['product_category_id']];
                        }
                    }
                }

                if(!empty($product_ids)) {

                    // import product using API
                    $postData = [
                        'clientid' => $userid,
                        'pid' => $product_ids,
                        'paymentmethod' => 'paypal',
                        'noinvoiceemail' => true,
                        'noinvoice' => true,
                        'noemail' => true,
                    ];

                    $results = localAPI('AddOrder', $postData, $adminUsername);

                    if($results['result'] == 'success') {
                        $products_count = count($product_ids);
                    }
                }
            }
        }

        // return json response
        header('Content-Type: application/json');
        echo json_encode([
            'users' => $users_count,
            'products' => $products_count,
            'skip' => $skip + $limit,
            'users_count' => $users_all
        ]);
        die();
    } else {

        // if is not a POST request - show output template
        include_once( dirname(__FILE__) . '/templates/output.php' );
    }
}

/**
 * Add is_trial column into tblhosting
 */
function old_billing_migrator_activate() {

    $group_id = Capsule::table('tblproductgroups')->where('name', 'Old billing dashboard')->value('id');

    // check exist products group
    if(empty($group_id)) {

        // create old billing group of the products
        $query = "INSERT INTO `tblproductgroups` (`name`, `headline`, `tagline`, `orderfrmtpl`, `disabledgateways`, `hidden`) VALUES
        ('Old billing dashboard', '', '', '', '', 1)";

        $result = Capsule::insert($query);

        if($result) {

            $group_id = Capsule::table('tblproductgroups')->where('name', 'Old billing dashboard')->value('id');
        }
    }

    $products = [
        'USA Rotating Private Proxies',
        'USA Static Private Proxies (Dedicated)',
        'USA Static Proxies (Semi-Dedicated)',
        'Blazing Tools',
        'Hosting',
        'USA Static SEMI-DEDICATED Private Proxies (4 Users)',
        'GERMAN Static Proxies (Semi-Dedicated)',
        'German Static - Dedicated',
        'German (Rotating)',
        'USA Static Dedicated - RESELLER',
        'Email Accounts',
        'Blazing ONLINE Tools',
        'Blazing Account Store',
        'Brazil Semi-Dedicated',
        'Brazil Dedicated',
        'Brazil Rotating',
        'South Africa Semi-Dedicated',
        'South Africa Dedicated',
        'South Africa Rotating',
        'Moz API Keys',
        'BlazingOCR',
        'Text Captcha',
        'Expired Domain',
        'Expired Domain Crawler',
        'Google Proxies',
        'International Proxies - Dedicated',
        'International - Semi-Dedicated',
        'International - Rotating',
        'Sneaker Proxies',
        'Hands Free Blogging',
        'Pokemon Go Proxies',
        'Supreme Proxies',
        'Sneaker Bundle',
    ];

    // add product to WHMCS if it not exist
    foreach ($products as $product) {

        $product_exist = Capsule::table('tblproducts')->where('name', $product)
            ->where('gid', $group_id)->first();

        if(empty($product_exist)) {

            $a = Capsule::table('tblproducts')->insert([
                'name' => $product,
                'gid' => $group_id
            ]);
        }
    }
}

/**
 * Print error message
 *
 * @param $title
 * @param $body
 * @return bool
 */
function old_billing_migrator_print_error($title, $body) {

    echo "<div class=\"errorbox\"><strong><span class=\"title\">$title</span></strong><br>$body</div>";
    
    return true;
}
