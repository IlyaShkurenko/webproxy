<?php

/**
 * Hook for WHMCS of "Large Client Suspension Notifications" Module
 *
 * @author Ruslan Ivanov
 */

use WHMCS\Database\Capsule;

add_hook('PreCronJob', 1, function($vars) {

    global $CONFIG;

    $date = new DateTime();
    $date = $date->modify('-1 day');

    $customfields_id = Capsule::table('tblcustomfields')
        ->where('fieldname', 'notification_email')->pluck('id');

    $services = Capsule::table('tblhosting')->where('overideautosuspend', 1)
        // where next due date past 1 day
        ->where('nextduedate', $date->format('Y-m-d'))
        // where custom filed value type == 'notification_email'
        ->whereIn('tblcustomfieldsvalues.fieldid', $customfields_id)
        // where set email notification
        ->where('tblcustomfields.fieldname', 'notification_email')
        ->leftJoin('tblcustomfieldsvalues', 'tblhosting.id', '=', 'tblcustomfieldsvalues.relid')
        ->leftJoin('tblcustomfields', 'tblhosting.packageid', '=', 'tblcustomfields.relid')
        ->select('tblhosting.id', 'userid', 'nextduedate', 'tblcustomfieldsvalues.value')->get();

    if(is_array($services)) {
        $services = json_decode(json_encode($services));
    }

    // get name of email template for notification
    $messagename = Capsule::table('tbladdonmodules')->where('module', 'large_client_suspension_notification')
        ->where('setting', 'messagename')->value('value');

    if(empty($messagename)) {
        logActivity('Error while get module parameters in Large Client Suspension Notification. 
        Email template must be not empty. Please set him in module settings. Module stopped.');
        return false;
    }

    // get default email for notification
    $defaultemail = Capsule::table('tbladdonmodules')->where('module', 'large_client_suspension_notification')
        ->where('setting', 'defaultemail')->value('value');

    if(empty($defaultemail) or !preg_match('%(?:[a-z0-9!#$\%&\'*+/=?^_`{|}~-]+(?:\.[a-z0-9!#$\%&\'*+/=?^_`{|}~-]+)*|"(?:[\x01-\x08\x0b\x0c\x0e-\x1f\x21\x23-\x5b\x5d-\x7f]|\\[\x01-\x09\x0b\x0c\x0e-\x7f])*")@(?:(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?|\[(?:(?:(2(5[0-5]|[0-4][0-9])|1[0-9][0-9]|[1-9]?[0-9]))\.){3}(?:(2(5[0-5]|[0-4][0-9])|1[0-9][0-9]|[1-9]?[0-9])|[a-z0-9-]*[a-z0-9]:(?:[\x01-\x08\x0b\x0c\x0e-\x1f\x21-\x5a\x53-\x7f]|\\[\x01-\x09\x0b\x0c\x0e-\x7f])+)\])%', $defaultemail)) {
        logActivity('Error while get module parameters in Large Client Suspension Notification. 
        Default email must be not empty and have correct format. Please set him in module settings. Module stopped.');
        return false;
    }

    // get template email
    $template = Capsule::table('tblemailtemplates')->where('name', $messagename)->first();

    if(empty($template->message)) {
        logActivity('Error while get module parameters in Large Client Suspension Notification. 
        Email message must be not empty. Please add email in WHMCS settings "Setup->Email Templates" 
        and set him in module settings. Module stopped.');
        return false;
    }

    // create Smarty object
    $smarty = new Smarty;

    foreach ($services as $service) {

        $email = $service->value;

        if(empty($email) or !preg_match('%(?:[a-z0-9!#$\%&\'*+/=?^_`{|}~-]+(?:\.[a-z0-9!#$\%&\'*+/=?^_`{|}~-]+)*|"(?:[\x01-\x08\x0b\x0c\x0e-\x1f\x21\x23-\x5b\x5d-\x7f]|\\[\x01-\x09\x0b\x0c\x0e-\x7f])*")@(?:(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?|\[(?:(?:(2(5[0-5]|[0-4][0-9])|1[0-9][0-9]|[1-9]?[0-9]))\.){3}(?:(2(5[0-5]|[0-4][0-9])|1[0-9][0-9]|[1-9]?[0-9])|[a-z0-9-]*[a-z0-9]:(?:[\x01-\x08\x0b\x0c\x0e-\x1f\x21-\x5a\x53-\x7f]|\\[\x01-\x09\x0b\x0c\x0e-\x7f])+)\])%', $email)) {
            $email = $defaultemail;
        }

        $email_merge_fields['company_name'] = $CONFIG['CompanyName'];
        $email_merge_fields['whmcs_url'] = $CONFIG['SystemURL'];
        $email_merge_fields['whmcs_link'] = "<a href=\"" . $CONFIG['SystemURL'] . "\">" . $CONFIG['SystemURL'] . "</a>";

        // assign variable for Smarty
        foreach ($email_merge_fields as $mergefield => $mergevalue) {
            $smarty->assign($mergefield, $mergevalue);
        }
        $smarty->assign([
            'userid'    => $service->userid,
            'serviceid' => $service->id,
        ]);

        $mail_result = large_client_suspension_notification_send_email($email, $template->subject, $smarty->fetch('string:' . $template->message));

        if(!$mail_result) {
            logActivity('Failed send suspension email notify. Email body - ' . $smarty->fetch('string:' . $template->message) . '. Email - ' . $email);
        } else {
            logActivity('"Large Client Suspension Notifications" module. Caught "Override Auto-Suspend" product. Send notification to email - ' . $email);
        }
    }

});

/**
 * Send custom message by email
 *
 * @param string $email
 * @param string $message
 * @return bool
 */
function large_client_suspension_notification_send_email($email, $subject, $message) {
    // Always set content-type when sending HTML email
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";

    return mail($email, $subject, $message, $headers);
}