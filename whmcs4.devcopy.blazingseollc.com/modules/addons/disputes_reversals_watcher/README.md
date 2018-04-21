# "Disputes/Reversals Watcher" Module for WHMCS #

This addon watching of canceled transactions by reasons dispute/reversal and if catched it - cancel order.

Triggered while the transaction log is being written.

- Checks that the type of payment is PayPal and payment_status == Reversed.

- Checks that mc_gross < 0.

- If so, then does suspend services

- Adds a payer email to the ban list.

### About ###

* Version: 1.0
* Author: Ruslan Ivanov
* Date: April 2017

### How do set up ###

1. Put folder "disputes_reversals_watcher" to {YOU WHMCS PATH}/modules/addons/.

2. In admin panel on top menu click "Setup" and click "Addon Modules". Find "Disputes/Reversals Watcher" module and click green "Activate" button. After module activating click button "Configure" and checked Full Administrator checkbox and set items count on admin page of module.

4. In admin panel on top menu click "Addons" and click "Disputes/Reversals Watcher". Use module functional.

Thx for using!