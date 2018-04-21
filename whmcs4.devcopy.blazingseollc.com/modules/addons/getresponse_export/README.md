# Getresponse export 

## Prerequisites

This plugin serves for exporting user data to 
[GetResponse contacts](https://apidocs.getresponse.com/v3/resources/contacts).

## Configuration

GetResponse custom fields need to иe configured: 
 * full_service_list - type text, format text
 * total_payments - type number, format number
 * total_recurring - type number, format number
 * proxy_state - type text, format text
 * dedicated_state - type text, format text
 * vps_state - type text, format text
 * all_state - type text, format text

Create application (in section api & integration) and paste it into config field `API key`. 
Set plugin permission to full administrator and visit admin area of the plugin. 
Copy an id of newly created list(campaign) and past it into `Campaign id` config field.

Export is running daily by cron or can be run with worker.php script in addon directory.
Worker can be run with command`php -q worker.php getresponse:export`.
Also additional flags can be used:
 * --limit=n for limiting processing users
 * --group=n for selecting users from specified group

## Tech guidelines

User data formats with compilers which use context for accessing data.
Formatted user data sends to GetResponse via commands run via cron
after new user has appeared or an existing one has been changed.

To call new users and updated user ids with status stores in separate
table called `getresponseexport_exported_users`.


