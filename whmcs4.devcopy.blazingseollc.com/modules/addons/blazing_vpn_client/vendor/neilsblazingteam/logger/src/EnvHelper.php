<?php

namespace Blazing\Vpn\Client\Vendor\Blazing\Logger;

use RuntimeException;
use Blazing\Vpn\Client\Vendor\Symfony\Component\Yaml\Yaml;
class EnvHelper
{
    public static function getAppEnv($composerJsonPath = null)
    {
        $composerConfigPath = !$composerJsonPath ? self::findComposerConfig() : $composerJsonPath;
        if (!is_file($composerConfigPath)) {
            throw new RuntimeException('Cannot initialize logger since config cannot be loaded from ' . $composerConfigPath);
        }
        $envConfigPath = dirname($composerConfigPath) . '/.logger.env.yml';
        $configScheme = ['name' => null, 'env' => 'dev', 'owner' => 'any'];
        $config = $configScheme;
        // The easiest option, use predefined file
        if (is_file($envConfigPath)) {
            $content = file_get_contents($envConfigPath);
            $data = Yaml::parse($content);
            foreach (array_keys($configScheme) as $key) {
                if (!empty($data[$key])) {
                    $config[$key] = $data[$key];
                }
            }
        }
        // Config is filled, return it
        if (count(array_keys($configScheme)) == count(array_filter($config))) {
            if (!empty($data) and count(array_keys($configScheme)) != count(array_keys($data))) {
                file_put_contents($envConfigPath, str_replace('null', '~', Yaml::dump($config)));
            }
            return $config;
        }
        $data = json_decode(file_get_contents($composerConfigPath), true);
        // Use defined app name
        if (!empty($data['config']['logger']['appName'])) {
            $config['name'] = $data['config']['logger']['appName'];
        } elseif (!empty($data['name'])) {
            $config['name'] = $data['name'];
        }
        // Use env from composer file
        if (!empty($data['config']['logger']['defaultEnv'])) {
            $config['env'] = $data['config']['logger']['defaultEnv'];
        }
        // Save as a file once something is determined
        file_put_contents($envConfigPath, str_replace('null', '~', Yaml::dump($config)));
        if (count(array_keys($configScheme)) != count(array_filter($config))) {
            $missedAttributes = [];
            foreach ($configScheme as $key) {
                if (empty($config[$key])) {
                    $missedAttributes[] = $key;
                }
            }
            throw new RuntimeException('Neither .logger.env.yml nor config in composer.json is found. ' . 'Please fill name (easier) or config.logger.appName value (better) in composer.json, ' . "or define app name in \"{$envConfigPath}\"");
        }
        // Config is determined, return it
        return $config;
    }
    protected static function findComposerConfig()
    {
        $foundPath = '';
        $startDirectory = __DIR__;
        $commonPaths = ['/../../../../composer.json'];
        foreach ($commonPaths as $path) {
            $path = ltrim($path, '/');
            $path = "{$startDirectory}/{$path}";
            if (is_file($path)) {
                $foundPath = $path;
            }
        }
        if ($foundPath) {
            return $foundPath;
        }
        // Go up until find that
        $dir = dirname($startDirectory);
        while ($dir = dirname($dir)) {
            $dir = rtrim($dir, '/');
            $path = "{$dir}/composer.json";
            if (is_file($path)) {
                $foundPath = $path;
                break;
            }
        }
        if ($foundPath) {
            return $foundPath;
        }
        return false;
    }
}