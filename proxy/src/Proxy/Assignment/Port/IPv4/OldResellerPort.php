<?php

namespace Proxy\Assignment\Port\IPv4;

class OldResellerPort extends Port
{

    const DICT_OLD_CATEGORY = [
        // self::CATEGORY_DEDICATED => 'static',
        self::CATEGORY_ROTATING  => 'rotate',
        // 'semi-dedicated' => static::CATEGORY_SEMI_DEDICATED
    ];

    public static function getValidCategories()
    {
        return array_diff(parent::getValidCategories(), [
            self::CATEGORY_KUSHANG,
            self::CATEGORY_MAPPLE,
            self::CATEGORY_GOOGLE
        ]);
    }
}
