<?php

/**
 * "Disputes/Reversals Watcher" Module for WHMCS
 *
 * This addon watching of canceled transactions by reasons dispute/reversal and if catched it - cancel order.
 *
 * @author Ruslan Ivanov
 */

use WHMCS\Database\Capsule;

/**
 * @return array
 */
function disputes_reversals_watcher_config()
{
    $configarray = [
        "name"        => "Disputes/Reversals Watcher",
        "description" => "This addon watching of canceled transactions by reasons dispute/reversal and if catched it - cancel order.",
        "version"     => "1.0 <sup style='color: #46a546'>stable</sup>",
        "author"      => "Ruslan Ivanov",
        "fields"      => [
            "itemsperpage" => [
                "FriendlyName" => "Items per page",
                "Type"         => "text",
                "Description"  => "Items count on admin page of module",
                "Size"         => "3",
                "Default"      => "10",
            ],
            "messagename" => [
                "FriendlyName" => "Email template",
                "Type"         => "text",
                "Description"  => "The name of the client email template to send",
            ],
        ],
    ];

    return $configarray;
}

/**
 * Activate module
 */
function disputes_reversals_watcher_activate()
{
    # Return Result
    return [
        'status'      => 'success',
        'description' => '"Disputes/Reversals Watcher" module activated.',
    ];
}

/**
 * Deactivate module
 */
function disputes_reversals_watcher_deactivate()
{
    # Return Result
    return [
        'status'      => 'success',
        'description' => '"Disputes/Reversals Watcher" module deactivated.',
    ];
}

/**
 * Display on admin page of module
 */
function  disputes_reversals_watcher_output($vars)
{
    $systemUrl = Capsule::table('tblconfiguration')->where('setting', 'SystemURL')->value('value');

    if(empty($vars['itemsperpage']) or !is_int((int)$vars['itemsperpage'])) {
        $vars['itemsperpage'] = 10;
    }
    $data['per_page'] = (int)$vars['itemsperpage'];
    $data['current_page'] = 1;

    $query = Capsule::table('tblactivitylog')->where('description', 'like', 'Error while canceling order in "Disputes/Reversals Watcher"%')
        ->orWhere('description', 'like', 'Suspended Product.%');
    $data['total'] = $query->count();

    if(isset($_GET['page']) and is_int((int)$_GET['page']) and $_GET['page'] != '1') {
        $query = $query->offset($data['per_page'] * $_GET['page'] - $data['per_page']);

        $data['current_page'] = $_GET['page'];
    }

    $data['data'] = $query->limit($data['per_page'])->orderBy('date')->get();

    $data['last_page'] = ceil($data['total'] / $data['per_page']);

    include_once(dirname(__FILE__) . '/templates/tpl_disputes_reversals_watcher_config.php');
}