<?php

namespace Application;

use ErrorException;
use Vendor\ConfigurableClass\YamlConfig;

class AppConfig extends YamlConfig
{

    protected function getConfigDirPath($name)
    {
        $ds = DIRECTORY_SEPARATOR;
        $dir = preg_replace('~[\\\\/]src[\\\\/].*$~', '', __DIR__) . "{$ds}app";

        if (!is_dir($dir)) {
            throw new ErrorException("App dir is not exists by path \"$dir\"");
        }

        return "$dir{$ds}config";
    }
}
