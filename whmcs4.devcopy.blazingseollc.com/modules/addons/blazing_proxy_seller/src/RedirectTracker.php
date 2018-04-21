<?php

namespace WHMCS\Module\Blazing\Proxy\Seller;

class RedirectTracker
{
    public static function trackData(array $data = [], $key = '')
    {
        $key = "redirect_track_data_$key";

        setcookie($key, http_build_query($data), time() + 24 * 60 * 60, '/');
    }

    public static function getTrackedData($default = [], $key = '', $removeAfter = true)
    {
        $key = "redirect_track_data_$key";
        if (!empty($_COOKIE[$key])) {
            parse_str($_COOKIE['redirect_track_data'], $data);

            if ($removeAfter) {
                setcookie($key, '', time() - 1, '/');
            }

            return $data;
        }

        return $default;
    }
}
