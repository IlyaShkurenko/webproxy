<?php

/**
 * "Large clients" Module for WHMCS
 *
 * Display large payments in WHMCS using domain email black list for customer email
 *
 * @author Ruslan Ivanov
 */

use WHMCS\Database\Capsule;

/*// debug
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);*/

/**
 * @return array
 */
function large_customers_config()
{
    $configarray = [
        "name"        => "Large customers",
        "description" => "Display latest payments of large customers",
        "version"     => "1.0 <sup style='color: #46a546'>stable</sup>",
        "author"      => "Ruslan Ivanov",
        "fields"      => [
            "min_amount" => [
                "FriendlyName" => "Min amount",
                "Type"         => "text",
                "Size"         => "6",
                "Default"      => "200",
                "Description"  => "Min invoice amount for display in module",
            ],
        ],
    ];

    return $configarray;
}

/**
 * Add module table
 */
function large_customers_activate() {

    // Create a new table.
    try {
        if (!Capsule::hasTable('large_customers_email_domains_blacklist')) {
            Capsule::schema()->create(
                'large_customers_email_domains_blacklist',
                function ($table) {
                    /** @var \Illuminate\Database\Schema\Blueprint $table */
                    $table->increments('id');
                    $table->string('domain')->unique();
                }
            );
        }
    } catch (\Exception $e) {
        echo "Unable to create table 'large_customers_email_domains_blacklist' : {$e->getMessage()}";
    }

    require_once('blacklist.php');

    Capsule::table('large_customers_email_domains_blacklist')->insert(large_customers_get_email_domains_blacklist());
}

/**
 * Drop module table
 */
function large_customers_deactivate() {

    // Delete module table.
    try {
        Capsule::schema()->dropIfExists('large_customers_email_domains_blacklist');
    } catch (\Exception $e) {
        echo "Unable to create my_table: {$e->getMessage()}";
    }

}

function large_customers_output($vars) {

    // set min payment amount for displaying
    $min_amount = 200;
    if((int)$vars['min_amount'] >= 0) {
        $min_amount = (int)$vars['min_amount'];
    }

    // set items per page amount for displaying
    if(empty($vars['itemsperpage']) or !is_int((int)$vars['itemsperpage'])) {
        $vars['itemsperpage'] = 30;
    }

    // handle post requests
    if(isset($_POST["action"])) {
        $message = large_customers_post_request_handler($_POST);
    }

    // default tab
    $tab = 'paymentslist';

    // if need get black list tab
    if(isset($_GET['tab']) and $_GET['tab'] == 'blacklist') {

        // set tab
        $tab = 'blacklist';

        $query = Capsule::table('large_customers_email_domains_blacklist');

        // start paginator logic
        $paginator['per_page'] = (int)$vars['itemsperpage'];
        $paginator['current_page'] = 1;
        $paginator['total'] = $query->count();

        if(isset($_GET['page']) and is_int((int)$_GET['page']) and $_GET['page'] != '1') {
            $_GET['page'] = (int)$_GET['page'];
            $domains = $query->offset($paginator['per_page'] * $_GET['page'] - $paginator['per_page']);

            $paginator['current_page'] = $_GET['page'];
        }

        // get domains
        $domains = $query->limit($paginator['per_page'])->orderBy('domain')->get();

        $paginator['last_page'] = ceil($paginator['total'] / $paginator['per_page']);
        // end paginator logic

        $smarty_vars = [
            'domains' => $domains,
        ];

    // by default displaying payments tab
    } else {

        // query string
        $notlike = '';

        $distinct = '';
        if($_GET['uniquefilter'] == 'on') {

            $groupby = 'GROUP BY client.email';

        }

        if($_GET['blacklist'] == 'on') {

            // get domains in black list
            $domains = Capsule::table('large_customers_email_domains_blacklist')->get();

            foreach ($domains as $domain) {
                $notlike .= " AND client.email NOT LIKE '%@{$domain->domain}'";
            }

        }

        // set default order by priority
        $priority = 'DESC';

        // set other order by priority
        if(isset($_GET['priority'])) {
            if($_GET['priority'] == 'DESC' or $_GET['priority'] == 'ASC') {
                $priority = $_GET['priority'];
            }
        }

        // set default order by param
        $orderby = 'invoice.datepaid';

        // set other order by param
        if(isset($_GET['orderby'])) {
            switch ($_GET['orderby']) {
                case 'invoiceid':
                    $orderby = 'invoice.id';
                    break;

                case 'name':
                    $orderby = 'client.firstname';
                    break;

                case 'email';
                    $orderby = 'client.email';
                    break;

                case 'datepaid';
                    $orderby = 'invoice.datepaid';
                    break;

                case 'amount';
                    $orderby = 'invoice.total';
                    break;
            }
        } else {
            $_GET['orderby'] = 'datepaid';
        }

        $sql = "
          FROM tblinvoices invoice
          JOIN tblclients client ON client.id = invoice.userid 
          WHERE invoice.status = 'Paid' AND invoice.total >= $min_amount $notlike $groupby
          ORDER BY $orderby $priority
        ";

        // get payments count
        $count = Capsule::select("SELECT COUNT(*) " . $sql);
        $unique_counts = 0;

        foreach ($count as $el) {
            foreach ($el as $el2) {
                $unique_counts += 1;
                $count = $el2;
            }
        }

        if($_GET['uniquefilter'] == 'on') {
            $count = $unique_counts;
        }

        // start paginator logic
        $paginator = [
            'total'        => $count,
            'per_page'     => $vars['itemsperpage'],
            'current_page' => (isset($_GET['page']) and is_int((int)$_GET['page']) and $_GET['page'] != '1') ? $_GET['page'] : 1,
        ];

        if(isset($_POST['action']) == 'gotopage' and (int)$_POST['page'] > 0) {
            $paginator['current_page'] = (int)$_POST['page'];
        }

        $paginator['last_page'] = ceil($paginator['total'] / $paginator['per_page']);
        // end paginator logic

        $sql = "SELECT client.email, client.firstname, client.lastname, invoice.id, invoice.total, invoice.datepaid "
            . $sql . " LIMIT " . $paginator['per_page'] .
            " OFFSET " . ($paginator['per_page'] * $paginator['current_page'] - $paginator['per_page']);

        // get payments
        $invoices = Capsule::select($sql);

        // assign variable for Smarty
        $smarty_vars = [
            'invoices'     => $invoices,
            'priority'     => $priority,
            'blacklist'    => $_GET['blacklist'],
            'uniquefilter' => $_GET['uniquefilter'],
            'orderby'      => $_GET['orderby'],
        ];
    }

    $smarty_vars['paginator'] = $paginator;
    $smarty_vars['message'] = $message;
    $smarty_vars['tab'] = $tab;
    $smarty_vars['vars'] = $vars;

    // display template
    return large_customers_display_smarty_template($smarty_vars);
}

/**
 * Delete email domain by id
 *
 * @param $id int
 * @return bool
 */
function large_customers_delete_email_domain($id) {
    return Capsule::table('large_customers_email_domains_blacklist')->where('id', $id)->delete();
}

/**
 * Insert new email domain
 *
 * @param $domain string
 * @return bool
 */
function large_customers_add_email_domain($domain) {
    return Capsule::table('large_customers_email_domains_blacklist')
        ->insert(['domain' => $domain]);
}

/**
 * Handle POST request
 *
 * @param $post array
 * @return array|bool
 */
function large_customers_post_request_handler($post) {

    switch ($post["action"]) {
        case 'domaindelete':
            if(isset($post["id"]) and (int)$post["id"] > 0) {
                $result = large_customers_delete_email_domain((int)$post["id"]);
            } else {
                $result = false;
            }

            return [
                'result' => $result,
                'msg' => $result ? 'Email domain success delete' : 'Error while deleting email domain'
            ];
            break;

        case 'addemaildomain':

            if(preg_match('/^(.)*\.(.){2,7}$/', $post["emaildomain"])) {
                $result = large_customers_add_email_domain($post["emaildomain"]);
            } else {
                $result = false;
                $addstr = 'Please use correct domain format!';
            }

            return [
                'result' => $result,
                'msg' => $result ? 'Email domain success added' : 'Error while adding email domain. ' . $addstr
            ];

            break;
    }

    return false;
}

/**
 * Display output template
 *
 * @param $vars array
 */
function large_customers_display_smarty_template($vars) {

    // create Smarty object
    $smarty = new Smarty;

    // assign variable for Smarty
    $smarty->assign($vars);

    // off caching
    $smarty->caching = false;

    // set compile templates dir
    $smarty->compile_dir = $GLOBALS['templates_compiledir'];

    return $smarty->display(dirname(__FILE__) . '/templates/template.tpl');
}