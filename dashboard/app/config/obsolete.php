<?php

if (!defined('WHMCS_PAYMENT_MODULE')) {
    return [];
}

return [
    'whmcs.paymentModule' => WHMCS_PAYMENT_MODULE,
    'userType'            => USER_TYPE,

    'db.reseller.user' => RS_DB_USER,
    'db.reseller.pass' => RS_DB_PASS,
    'db.reseller.name' => RS_DB_NAME,
    'db.reseller.host' => RS_DB_HOST,
];