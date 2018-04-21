<?php

namespace Common\Events\Events;

use League\Event\AbstractEvent as BaseAbstractEvent;

class AbstractEvent extends BaseAbstractEvent
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
