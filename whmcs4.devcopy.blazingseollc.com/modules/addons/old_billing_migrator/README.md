# README #

## "Old billing migrator" ##

This addon module migrate Amember Users to WHMCS


## ATTENTION! ##
Save database backup before every migrate!
Please don't change products names of "Old billing dashboard" group and group name. If changed categories on production "Amember" please change module config!

### How set up? ###

* Put "old_billing_migrator" folder to {YOU WHMCS DIR}/modules/addons

* Install module in admin area

* Configure module configuration

* Migrate workflow:

  1. Enable "Maintenance mode" in WHMCS,

  2. Necessarily create WHMCS database backup,

  3. Go to module admin page,

  4. If you see errors, fix it,

  5. Start migration process,

  6. After migration please check it result,

  7. If all is ok - disable "Maintenance mode". If not - Load you WHMCS backup, disable "Maintenance mode" and will write to developer about errors.