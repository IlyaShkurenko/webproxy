<?php

/**
 * "Large Client Suspension Notification" Module for WHMCS
 *
 * This addon watching when someone’s ‘Next Due Date’ is past by 1 day and "Override Auto-Suspend" checkbox is ticked, then an will send email notification to the email which setted on service page.
 *
 * @author Ruslan Ivanov
 */

use WHMCS\Database\Capsule;

/**
 * @return array
 */
function large_client_suspension_notification_config()
{
    $configarray = [
        "name"        => "Large Client Suspension Notification",
        "description" => "This addon watching when someone’s ‘Next Due Date’ is past by 1 day and \"Override Auto-Suspend\" checkbox is ticked, then an will send email notification to the email which setted on service page",
        "version"     => "1.0 <sup style='color: #46a546'>stable</sup>",
        "author"      => "Ruslan Ivanov",
        "fields"      => [
            "messagename" => [
                "FriendlyName" => "Email template",
                "Type"         => "text",
                "Description"  => "Email template for notification",
                "Size"         => "255",
            ],
            "defaultemail" => [
                "FriendlyName" => "Default email",
                "Type"         => "text",
                "Description"  => "Default email for notification. Using if email not set in service",
                "Size"         => "255",
            ],
        ],
    ];

    return $configarray;
}

/**
 * Activate module
 */
function large_client_suspension_notification_activate()
{

    $products_id = Capsule::table('tblproducts')->pluck('id');

    $custom_fields_array = [];

    // add "notification_email" custom field for all products
    foreach ($products_id as $product_id) {
        $custom_fields_array[] = [
            'type'        => 'product',
            'relid'       => $product_id,
            'fieldname'   => 'notification_email',
            'fieldtype'   => 'text',
            'description' => 'Input if you want get client suspension notification to other email',
            'adminonly'   => 'on',
        ];
    }

    Capsule::table('tblcustomfields')->insert($custom_fields_array);

    # Return Result
    return [
        'status'      => 'success',
        'description' => '"Large Client Suspension Notification" module activated.',
    ];

}

/**
 * Deactivate module
 */
function large_client_suspension_notification_deactivate()
{

    // delete "notification_email" custom field for all products
    Capsule::table('tblcustomfields')->where('fieldname', 'notification_email')->delete();

    # Return Result
    return [
        'status'      => 'success',
        'description' => '"Large Client Suspension Notification" module deactivated.',
    ];

}