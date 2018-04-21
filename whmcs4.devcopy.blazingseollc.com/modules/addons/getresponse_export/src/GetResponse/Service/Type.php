<?php

namespace WHMCS\Module\Blazing\Export\GetResponse\Service;

class Type
{
    const PROXY = 'proxy';
    const SNEAKER_PROXY = 'sneaker-p';
    const SNEAKER_VPS = 'sneaker-v';
    const VPS = 'vps';
    const DEDICATED = 'dedi';

    public function detectServiceType($productName)
    {
        $productName = strtolower($productName);
        $proxy = strpos($productName, 'proxy') !== false
            || strpos($productName, 'proxies') !== false;
        $sneaker = strpos($productName, 'sneaker') !== false;
        $vps = strpos($productName, 'vps') !== false;
        $dedi = strpos($productName, 'dedicated server') !== false;

        $type = null;
        if ($proxy && !$sneaker) {
            $type = self::PROXY;
        } elseif ($vps && !$sneaker) {
            $type = self::VPS;
        } elseif ($proxy) {
            $type = self::SNEAKER_PROXY;
        } elseif ($vps) {
            $type = self::SNEAKER_VPS;
        } elseif ($dedi) {
            $type = self::DEDICATED;
        }
        return $type;
    }

    public function isProxy($name)
    {
        $type = $this->detectServiceType($name);
        return $type === self::PROXY || $type === self::SNEAKER_PROXY;
    }

    public function isVps($name)
    {
        $type = $this->detectServiceType($name);
        return $type === self::VPS || $type === self::SNEAKER_VPS;
    }

    public function isDedicated($name)
    {
        $type = $this->detectServiceType($name);
        return $type === self::DEDICATED;
    }
}
