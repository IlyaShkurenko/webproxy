<?php

namespace Vendor\ConfigurableClass;

class Util
{

    public static function arrayDiffRecursive(array $arr1, array $arr2)
    {
        // http://php.net/manual/en/function.array-diff.php#91756
        $result = [];

        foreach ($arr1 as $key => $value) {
            if (array_key_exists($key, $arr2)) {
                if (is_array($value)) {
                    $diff = self::arrayDiffRecursive($value, $arr2[ $key ]);
                    if (count($diff)) {
                        $result[ $key ] = $diff;
                    }
                }
                else {
                    if ($value != $arr2[ $key ]) {
                        $result[ $key ] = $value;
                    }
                }
            }
            else {
                $result[ $key ] = $value;
            }
        }

        return $result;
    }

    public static function arrayDiffKeysRecursive(array $arr1, array $arr2)
    {
        // http://php.net/manual/en/function.array-diff.php#91756
        $result = [];

        foreach ($arr1 as $key => $value) {
            // Skip numeric keys
            if (ctype_digit($key) or is_numeric($key)) {
                continue;
            }

            if (array_key_exists($key, $arr2)) {
                if (is_array($value)) {
                    $diff = self::arrayDiffKeysRecursive($value, $arr2[ $key ]);
                    if (count($diff)) {
                        $result[ $key ] = $diff;
                    }
                }
            }
            else {
                $result[ $key ] = $value;
            }
        }

        return $result;
    }

    public static function arrayRemoveDiffKeysRecursive(array $arr1, $arr2)
    {
        $diff = array_diff_key($arr2, $arr1);
        $intersect = array_intersect_key($arr2, $arr1);

        // Remove new keys
        foreach (array_keys($diff) as $key) {
            // Skip number keys
            if (is_int($key) or ctype_digit($key)) {
                continue;
            }
            unset($arr2[$key]);
        }

        foreach ($intersect as $k => $v) {
            if (is_array($arr1[ $k ]) && is_array($arr2[ $k ])) {
                $d = self::arrayRemoveDiffKeysRecursive($arr1[ $k ], $arr2[ $k ]);

                $arr2[ $k ] = $d;
            }
        }

        return $arr2;
    }

    public static function getArrayDepth(array $arr)
    {
        // https://stackoverflow.com/questions/262891/is-there-a-way-to-find-out-how-deep-a-php-array-is
        $maxDepth = 1;

        foreach ($arr as $value) {
            if (is_array($value)) {
                $depth = static::getArrayDepth($value) + 1;

                if ($depth > $maxDepth) {
                    $maxDepth = $depth;
                }
            }
        }

        return $maxDepth;
    }
}
