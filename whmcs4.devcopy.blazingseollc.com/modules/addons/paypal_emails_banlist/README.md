# "PayPal Emails Banlist" Module for WHMCS #

This addon baning PayPal emails, so that they can't make purchases.

**The first step** in checking the payer email occurs before paying the invoice on invoice page. User must enter self PayPal email and check it for availability in the ban list for continue pay invoice. A fraudster can enter another email, but he will catched after paying an invoice for a callback from Pay Pal.

**The second step** start when user paid invoice and WHMCS was logged transaction.

Verification process:

- When a transaction log is written, the hook intercepts it.

- It checks that the payment type = PayPal and the result is a successful or subscription signup.

- After he parses the payer's email and checks its availability in the ban list.

- If the email address is in the ban list, then the module suspend all services.

- after, module refunding amount to client.

- If the refund is successful, then creates a new tranche about the refund.

- Cancels the order and transfers it to the refunded status.

- sends an email to the user whose template is added to the admin panel and specified in the module settings.

Email addresses can be added to the ban list via the admin panel of the module. Also there you can delete emails from the ban list and perform a search them.

### About ###

* Version: 0.7
* Author: Ruslan Ivanov
* Date: March 2017

### How do set up ###

Attention!
If you use other oauth proxy server --> open paypal_emails_banlist/guardianAPI/GuardianApiCaller.php, find line 17 and change service URL!

1. Put folder "paypal_emails_banlist" to {YOU WHMCS PATH}/modules/addons/.

2. Put "guard_api.php" to you you home whmcs directory.

3. Open {YOU WHMCS PATH}/lang/english.php. If you have setted other language please open {YOU WHMCS PATH}/lang/{YOU LANG}.php

    Find:

        $_LANG['invoicesrefunded'] = "Refunded";

    Insert after:

        // PayPal Emails Banlist Module
        $_LANG['invoicesrefundedspantext1'] = "You must first verify your PayPal address before ordering. If you are banned from purchasing from ";
        $_LANG['invoicesrefundedspantext2'] = ", your order will be cancelled.";
        $_LANG['invoicesrefundedplaceholder'] = "Enter you real PayPal email";
        $_LANG['paypalbanlistmodcheckbutton'] = "Verify";
        $_LANG['paypalbanlistmodentervalidemail'] = "Enter a valid Email address!";
        $_LANG['paypalbanlistmodemailinbanlist'] = "You PayPal email address in ban list!";
        // End PayPal Emails Banlist Module


4. Put folder "paypal_banlist" to {YOU WHMCS PATH}/templates/{YOU TEMPLATE}/includes/.

5. Open you {YOU WHMCS PATH}/templates/{YOU TEMPLATE}/viewinvoice.tpl.

    Find:

        <body>

    Insert after:

        {include file="$template/includes/paypal_banlist/block.tpl"}

    Find:

        </body>

    Insert before:

        {include file="$template/includes/paypal_banlist/script.tpl"}

6. Open WHMCS admin panel. On top menu click "Setup" and click "Email Templates". Create new email template which will send if PayPal payer email in ban list.

7. In admin panel on top menu click "Setup" and click "Addon Modules". Find "PayPal Emails Banlist" module and click green "Activate" button. After module activating click button "Configure" and checked Full Administrator checkbox and insert name of email template which you was created in point 5. And if you use sanbdox mode of PayPal please tick checkbox.

8. In admin panel on top menu click "Addons" and click "PayPal Emails Banlist". Use module functional.

Thx for using!