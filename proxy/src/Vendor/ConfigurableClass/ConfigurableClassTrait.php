<?php

namespace Vendor\ConfigurableClass;

use Gears\Arrays;
use RuntimeException;

trait ConfigurableClassTrait
{
    protected static $configClassConfig = [];

    // To be overridden

    /**
     * Get exporter/importer class
     * @return string Class name
     */
    protected function getClassClassConfig()
    {
        return YamlConfig::class;
    }

    /**
     * File name
     *
     * @return string
     */
    abstract protected function getNameClassConfig();

    /**
     * Class name as well as property name in file
     *
     * @param string|object|null $class
     * @return string
     */
    protected function getKeyClassConfig($class = null)
    {
        $className = $class ? $class : $this;
        if (is_object($className)) {
            $className = get_class(($className));
        }

        $tmp = explode('\\', $className);

        return end($tmp);
    }

    // Accessors

    /**
     * Get data from config
     *
     * @param $key mixed If empty
     * @param bool $throwExceptionIfNotDefined
     * @return mixed
     */
    public function getFromConfig($key, $throwExceptionIfNotDefined = true)
    {
        $array = $this->getMergedDataClassConfig();
        if (!$key) {
            return $array;
        }
        $value = Arrays::get($array, $key, null);

        // No value is found
        if (is_null($value)) {
            if ($throwExceptionIfNotDefined) {
                throw new RuntimeException(sprintf('Config value "%s" is not exists', $key));
            }
            else {
                return null;
            }
        }

        return $value;
    }

    // Main handler

    /**
     * Get class config, compare it with file config, update class config or file config, depends on its content,
     * and return the updated class config
     *
     * @return array
     */
    protected function getMergedDataClassConfig()
    {
        $key = $this->getKeyClassConfig();
        if (empty(self::$configClassConfig[$key])) {
            return $this->getMergedDataForClassConfig(null, $this->getClassDataClassConfig());
        }

        return self::$configClassConfig[$key];
    }

    /**
     * Get config data for specified class, compare it with file config, update class config or file config,
     * depends on its content, and return the updated class config
     *
     * @param string|object|null $class
     * @param array $classConfig
     * @return array
     */
    protected function getMergedDataForClassConfig($class = null, array $classConfig)
    {
        $key = $this->getKeyClassConfig($class);

        if (empty(self::$configClassConfig[$key])) {
            $fileConfig = $this->loadFileDataClassConfig($class);

            $this->upgradeFileDataClassConfigIfNeeded($classConfig, $fileConfig, $class);
            self::$configClassConfig[$key] = array_replace_recursive($classConfig, $fileConfig);
        }

        return self::$configClassConfig[$key];
    }

    /**
     * Get config data for passed class, compare with overridden class config data and return merged result.
     * So it returns merge of: intermediate (class + file config) + top class config
     * Useful when you have abstract class and its subclass, and need to make some configuration in subclass,
     * but don't want abstract config to be exported as subclass config

     *
     * @param array $topClassConfig
     * @param array $attachedClassConfig
     * @param $attachedClass
     * @param callable|null $prepareTopClassConfig Prepare passed class config
     * @return array
     */
    protected function getMergedDataWithClassConfig(array $topClassConfig, array $attachedClassConfig,
        $attachedClass, callable $prepareTopClassConfig = null)
    {
        $attachedClassConfig = $this->getMergedDataForClassConfig($attachedClass, $attachedClassConfig);

        if ($prepareTopClassConfig) {
            $topClassConfig = call_user_func($prepareTopClassConfig, $topClassConfig);
        }

        return array_replace_recursive($attachedClassConfig, $topClassConfig);
    }

    /**
     * Return config data for passed class, where removed all non-unique config values from top class
     * So it returns unique values of: top class config - intermediate (class + file config)
     * Useful in prepareClassDataClassConfig when you have abstract class and its subclass,
     * and need to make some configuration in subclass,
     * but don't want abstract config to be exported as subclass config. Example:
     * <pre>
     * protected function prepareClassDataClassConfig(array $config)
        * {
            * return ($this->getDiffDataWithClassConfig(
                * parent::prepareClassDataClassConfig($config), __CLASS__, function (array $config) {
                    * return [
                        * 'settings' => $config
                    * ];
            * }));
        * }
     * </pre>

     *
     * @param array $topClassConfig
     * @param $attachedClass
     * @param callable|null $prepareConfig Prepare parent config to be compared with top class config
     * @return array
     */
    protected function getDiffDataWithClassConfig(array $topClassConfig, $attachedClass, callable $prepareConfig = null)
    {
        $key = $this->getKeyClassConfig($attachedClass);
        $fileConfig = !empty(self::$configClassConfig[$key]) ?
            self::$configClassConfig[$key] : $this->loadFileDataClassConfig($attachedClass);

        if ($prepareConfig) {
            $fileConfig = call_user_func($prepareConfig, $fileConfig);
        }

        return Util::arrayDiffRecursive($topClassConfig, $fileConfig);
    }

    // Loaders

    /**
     * Get class data/settings to exported/imported
     *
     * @return array
     */
    abstract protected function getClassDataClassConfig();

    /**
     * Load config from file
     *
     * @param string|object|null $class
     * @return array
     */
    protected function loadFileDataClassConfig($class = null)
    {
        /** @var YamlConfig $cls */
        $cls = $this->getClassClassConfig();
        if (!is_subclass_of($cls, YamlConfig::class)) {
            throw new RuntimeException(sprintf('classConfigClass should be inherited from "%s", "%s" passed',
                YamlConfig::class, $this->classConfigClass));
        }

        return $cls::getInstance($this->getNameClassConfig())->get($this->getKeyClassConfig($class), []);
    }

    /**
     * Persist class settings to file
     *
     * @param array $config
     * @param string|object|null $class
     */
    protected function saveFileDataClassConfig(array $config, $class = null)
    {
        /** @var YamlConfig $cls */
        $cls = $this->getClassClassConfig();
        if (!is_subclass_of($cls, YamlConfig::class)) {
            throw new RuntimeException(sprintf('classConfigClass should be inherited from "%s", "%s" passed',
                YamlConfig::class, $this->classConfigClass));
        }

        $cls::getInstance($this->getNameClassConfig())->set($this->getKeyClassConfig($class), $config)->persist();
    }

    // Handlers

    /**
     * To be overridden. Removes class config redundant data, that's how class config will be exported to file
     *
     * @param array $config
     * @return array
     */
    protected function prepareClassDataClassConfig(array $config)
    {
        return $config;
    }

    /**
     * Update file config if something have been changed in class config
     *
     * @param array $classConfig
     * @param array $fileConfig
     * @param string|object|null $class
     */
    protected function upgradeFileDataClassConfigIfNeeded(array $classConfig, array $fileConfig, $class = null)
    {
        $preparedClassConfig = !$class ? $this->prepareClassDataClassConfig($classConfig) : $classConfig;

        // Determine diff
        $newKeysInClass = Util::arrayDiffKeysRecursive($preparedClassConfig, $fileConfig);
        $newKeysInFile = Util::arrayDiffKeysRecursive($fileConfig, $classConfig);

        if ($newKeysInClass or $newKeysInFile) {
            // Remove not-existent keys
            $fileConfig = Util::arrayRemoveDiffKeysRecursive($classConfig, $fileConfig);
            // Add new keys
            $fileConfig = array_replace_recursive($fileConfig, $newKeysInClass);

            $this->saveFileDataClassConfig($fileConfig, $class);
        }
    }

    protected function removeDiffKeysClassConfig(array $classConfig, array $anotherConfig)
    {
        return array_replace_recursive($classConfig, Util::arrayRemoveDiffKeysRecursive($classConfig, $anotherConfig));
    }

    /**
     * Check if file config is outdated
     *
     * @param array $classConfig
     * @param array $fileConfig
     * @return bool
     */
    protected function isFileDataClassConfigUpgradeNeeded(array $classConfig, array $fileConfig)
    {
        return Util::arrayDiffRecursive($classConfig, $fileConfig)
            or Util::arrayDiffRecursive($fileConfig, $classConfig);
    }
}