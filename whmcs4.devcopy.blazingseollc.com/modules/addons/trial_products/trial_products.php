<?php

/**
 * "Trial Products" Module for WHMCS
 *
 * This addon allows you to sell trial products with a custom time trial time.
 * If the user wants to re-order the trial product from another account, he will be added to the banlist, and his order will be canceled and the funds returned.
 *
 * @author Ruslan Ivanov
 */

use WHMCS\Database\Capsule;

/**
 * @return array
 */
function trial_products_config()
{
    $configarray = [
        "name"        => "Trial Products",
        "description" => "This addon allows you to sell trial products with a custom time trial time",
        "version"     => "1.0 <sup style='color: #46a546'>stable</sup>",
        "author"      => "Ruslan Ivanov",
        "fields"      => [
            "messagename" => [
                "FriendlyName" => "Email template",
                "Type"         => "text",
                "Description"  => "The name of the client email template to send",
            ],
            "sandbox" => [
                "FriendlyName" => "Enable sandbox mode?",
                "Type"         => "yesno",
                "Description"  => "If now you using sandbox PayPal mode in you WHMCS please tick it",
            ],
        ],
    ];

    return $configarray;
}

/**
 * Add is_trial column into tblhosting
 */
function trial_products_activate() {

    $query = "ALTER TABLE `tblhosting` ADD `is_trial` TINYINT(1) NULL DEFAULT 0 AFTER `updated_at`;";
    $result = full_query($query);

    $query = "CREATE TABLE `tbltrialproductsemailpayers` ( `id` INT NOT NULL AUTO_INCREMENT , `payeremail` VARCHAR(255) NOT NULL , `created_at` TIMESTAMP NOT NULL , PRIMARY KEY (`id`)) ENGINE = InnoDB";
    $result = full_query($query);

    $query = "ALTER TABLE `tblhosting` ADD `payeremailid` INT NULL DEFAULT NULL AFTER `updated_at`";
    $result = full_query($query);

}

/**
 * Drop is_trial column into tblhosting
 */
function trial_products_deactivate() {

    /*$query = "ALTER TABLE `tblhosting` DROP `is_trial`";
    $result = full_query($query);

    $query = "ALTER TABLE `tblhosting` DROP `payeremailid`";
    $result = full_query($query);

    $query = "DROP TABLE tbltrialproductsemailpayers";
    $result = full_query($query);*/

}