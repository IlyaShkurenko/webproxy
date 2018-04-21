<?php

namespace WHMCS\Module\Blazing\Proxy\Seller\Events;

use WHMCS\Module\Framework\Events\AbstractHookListener;

class EmailSilencer extends AbstractHookListener
{
    protected $name = 'EmailPreSend';

    protected $enabled = false;
    protected static $blacklist = [];

    public static function enableSilence(array $blacklist = [])
    {
        static::disable();
        static::$blacklist = $blacklist;
    }

    public static function disableSilence()
    {
        static::enable();
        static::$blacklist = [];
    }

    protected function execute(array $args = null)
    {
        $messageKey = $args['messagename'];

        // Allowed by blacklist
        if (self::$blacklist and !in_array(self::$blacklist, $messageKey)) {
            return [];
        }

        // Any other case - stop sending
        return ['abortsend' => true];
    }
}
