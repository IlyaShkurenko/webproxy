# Email on teminate #

This module serves for notifying user after his service has been terminated.

Note: product must be linked with any module. 

### Usage ###

Enter name of a template in addon config.
Template will be used for emailing users after their service is terminated.
Template must be of general type.

##### Also additional variables can be used in email template:

* {$service_name} - name of a terminated service.
* {$service_groupname} - group name of a terminated service.
* {$service_domain} - a terminated service domain name.

### How to set up ###

* Copy module files into whmcs/modules/addons/module_terminate_email folder.
* Enable addon
