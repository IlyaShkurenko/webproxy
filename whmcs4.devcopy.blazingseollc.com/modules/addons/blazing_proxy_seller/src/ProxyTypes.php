<?php

namespace WHMCS\Module\Blazing\Proxy\Seller;

class ProxyTypes
{

    const IP_V_4 = 4;
    const IP_V_6 = 6;

    const TYPE_SINGLE = 'single';
    const TYPE_BLOCK_N_PER_BLOCK = 'block_n_per_block';

    const COUNTRY_US = 'us';
    const COUNTRY_GERMANY = 'de';
    const COUNTRY_BRAZIL = 'br';
    const COUNTRY_UK = 'gb';
    const COUNTRY_FRANCE = 'ft';

    const CATEGORY_DEDICATED = 'dedicated';
    const CATEGORY_SEMI_DEDICATED = 'semi-3';
    const CATEGORY_ROTATING = 'rotate';
    const CATEGORY_SNEAKER = 'sneaker';
    const CATEGORY_KUSHANG = 'kushang';
    const CATEGORY_MAPPLE = 'mapple';
    const CATEGORY_SUPREME = 'supreme';
    const CATEGORY_BLOCK = 'block';

    const AVAILABLE = [
        // IPv4, single
        ['ipVersion' => self::IP_V_4, 'type' => self::TYPE_SINGLE,
         'country' => self::COUNTRY_US, 'category' => [
            self::CATEGORY_DEDICATED,
            self::CATEGORY_SEMI_DEDICATED,
            self::CATEGORY_ROTATING,
            self::CATEGORY_SNEAKER,
            self::CATEGORY_KUSHANG,
            self::CATEGORY_MAPPLE,
            self::CATEGORY_SUPREME,
            self::CATEGORY_BLOCK,
        ]
        ],
        ['ipVersion' => self::IP_V_4, 'type' => self::TYPE_SINGLE,
         'country' => [
             self::COUNTRY_GERMANY,
             self::COUNTRY_BRAZIL
         ], 'category' => [
            self::CATEGORY_DEDICATED,
            self::CATEGORY_SEMI_DEDICATED,
            self::CATEGORY_ROTATING
        ]],
        ['ipVersion' => self::IP_V_4, 'type' => self::TYPE_SINGLE,
         'country' => [
             self::COUNTRY_UK,
             self::COUNTRY_FRANCE
         ], 'category' => [
            self::CATEGORY_DEDICATED,
            self::CATEGORY_SEMI_DEDICATED,
            self::CATEGORY_SNEAKER
        ]],

        // IPv6, block
        ['ipVersion' => self::IP_V_6, 'type' => self::TYPE_BLOCK_N_PER_BLOCK, 'ext' => ['subnet' => 56, 'perSubnet' => 2]],
    ];

    const HUMAN_DICT = [
        'type' => [
            self::TYPE_SINGLE => 'Single',
            self::TYPE_BLOCK_N_PER_BLOCK => 'N per block'
        ],
        'country'  => [
            self::COUNTRY_US      => 'US',
            self::COUNTRY_GERMANY => 'German',
            self::COUNTRY_BRAZIL  => 'Brazil',
            self::COUNTRY_UK      => 'UK',
            self::COUNTRY_FRANCE  => 'France'
        ],
        'category' => [
            self::CATEGORY_DEDICATED      => 'Dedicated',
            self::CATEGORY_SEMI_DEDICATED => 'Semi-Dedicated',
            self::CATEGORY_ROTATING       => 'Rotating',
            self::CATEGORY_SNEAKER        => 'Sneaker',
            self::CATEGORY_KUSHANG        => 'Special Kushang',
            self::CATEGORY_MAPPLE         => 'Maple',
            self::CATEGORY_SUPREME        => 'Supreme',
            self::CATEGORY_BLOCK          => 'Hardcoded Block',
        ],
    ];

    public static function expandAvailable()
    {
        static $result = [];

        // Cache the result
        if ($result) {
            return $result;
        }

        foreach (self::AVAILABLE as $row) {
            $expandableKeys = ['ipVersion', 'type', 'country', 'category', 'type', 'ext'];
            $expanded = [];

            foreach ($expandableKeys as $key) {
                // Determine values
                $values = [];
                if (!empty($row[$key])) {
                    if (is_array($row[$key])) {
                        // Like ext
                        if (!isset($row[$key][0])) {
                            $values[] = $row[$key];
                        }
                        else {
                            $values = $row[$key];
                        }
                    }
                    else {
                        $values[] = $row[$key];
                    }
                }
                else {
                    $values[] = null;
                }

                // Multiple by values
                // Initial expand
                if (empty($expanded)) {
                    foreach ($values as $value) {
                        $expanded[] = [$key => $value];
                    }
                }
                // Expand the previous values
                else {
                    $source = $expanded;
                    $expanded = [];
                    foreach ($source as $i => $item) {
                        foreach ($values as $value) {
                            $item[$key] = $value;
                            $expanded[] = $item;
                        }
                    }
                }
            }

            foreach ($expanded as $i => $item) {
                $expanded[$i]['uid'] = md5(json_encode($item));
            }

            $result = array_merge($result, $expanded);
        }

        return $result;
    }

    public static function getItemByUid($uid)
    {
        $available = self::expandAvailable();
        foreach ($available as $item) {
            if ($item[ 'uid' ] == $uid) {
                return $item;
            }
        }

        return false;
    }

    public static function findSingleItemByCriterias(
        $ipVersion = null,
        $type = null,
        $country = null,
        $category = null,
        $ext = null
    ) {
        $available = self::expandAvailable();
        $found = [];

        foreach ($available as $item) {
            if ($ipVersion and $ipVersion != $item[ 'ipVersion' ]) {
                continue;
            }
            if ($type and $type != $item[ 'type' ]) {
                continue;
            }
            if ($country and (!$item[ 'country' ] or ($item[ 'country' ] and $country != $item[ 'country' ]))) {
                continue;
            }
            if ($category and (!$item[ 'category' ] or ($item[ 'category' ] and $category != $item[ 'category' ]))) {
                continue;
            }
            if ($ext and (!$item[ 'ext' ] or ($item[ 'ext' ] and json_encode($ext) != json_encode($item[ 'ext' ])))) {
                continue;
            }

            $found[] = $item;
        }

        if (1 == count($found)) {
            return $found[ 0 ];
        }
        else {
            // Since found more than 1 (2, 3) or no items are found
            return false;
        }
    }
}
