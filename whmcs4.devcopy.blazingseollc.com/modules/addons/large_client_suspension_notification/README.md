# "Large Client Suspension Notification" Module for WHMCS #

This addon watching when someone’s ‘Next Due Date’ is past by 1 day and "Override Auto-Suspend" checkbox is ticked, then an will send email notification to the email which setted on service page.


### About ###

* Version: 1.0
* Author: Ruslan Ivanov
* Date: June 2017

### How do set up ###

1. Put folder "large_client_suspension_notification" to {YOU WHMCS PATH}/modules/addons/.

2. In admin panel on top menu go to "Setup"->"Email Templates" and create new email template for module notifications. You can operate two variables in template - $userid and $serviceid. For example: User ID - {$userid} Service ID - {$serviceid}.

3. In admin panel on top menu click "Setup" and click "Addon Modules". Find "Large Client Suspension Notification" module and click green "Activate" button. After module activating click button "Configure" and configure it.

4. Required will set email for notification and email template in module settings, otherwise module not be working!

5. After install module added "notification_email" custom field for all products. If you want get notify email for certain user service please set email in input "notification_email" on user product page, otherwise notify will be send to email which setted in module settings.

Thx for using!