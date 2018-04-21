<?php

namespace WHMCS\Module\Blazing\Proxy\Seller\EmitterEvents;

use League\Event\AbstractEvent as BaseAbstractEvent;

abstract class AbstractEvent extends BaseAbstractEvent
{
    protected static $name = '';

    public function getName()
    {
        return static::name();
    }

    public static function name()
    {
        return static::$name ? static::$name : str_replace('Event', '', get_called_class());
    }
}