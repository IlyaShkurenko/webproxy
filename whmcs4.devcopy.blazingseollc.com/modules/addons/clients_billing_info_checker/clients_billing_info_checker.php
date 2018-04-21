<?php

/**
 * "Clients billing info checker" Module for WHMCS
 *
 * Check billing info of the client. If it not filled, show info message for client and does not let him to site, only to client details page
 *
 * @author Ruslan Ivanov
 */

/**
 * @return array
 */
function clients_billing_info_checker_config()
{
    $configarray = [
        "name"        => "Clients billing info checker",
        "description" => "Check billing info of the client. If it not filled, show info message for client and does not let him to site, only to client details page",
        "version"     => "0.1",
        "author"      => "Ruslan Ivanov",
    ];

    return $configarray;
}