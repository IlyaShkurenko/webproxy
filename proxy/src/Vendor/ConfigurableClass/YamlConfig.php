<?php

namespace Vendor\ConfigurableClass;

use Gears\Arrays;
use RuntimeException;
use Symfony\Component\Yaml\Yaml;

abstract class YamlConfig
{
    protected static $rootPath;
    protected $filePath = '';
    protected $fileDefaultPath = '';
    protected $data = [];
    protected $loaded = false;

    /**
     * @param $name
     * @return self
     */
    public static function getInstance($name)
    {
        static $instances = [];

        if (empty($instances[$name])) {
            $instances[$name] = new static($name);
        }

        return $instances[$name];
    }

    protected function __construct($name) {
        if (!$name) {
            throw new RuntimeException('No name passed');
        }

        $ds = DIRECTORY_SEPARATOR;
        $rootPath = realpath($this->getConfigDirPath($name));
        $this->filePath = $rootPath . $ds . $this->convertNameToFile($name) . '.yml';
        $this->fileDefaultPath = $rootPath . $ds . $this->convertNameToDefaultFile($name) . '.yml';
    }

    public function get($key, $default = null)
    {
        $this->loadData();

        return Arrays::get($this->data, $key, $default);
    }

    public function getAll()
    {
        $this->loadData();

        return $this->data;
    }

    public function set($key, $value)
    {
        $this->loadData();

        $this->data = Arrays::set($this->data, $key, $value);

        return $this;
    }

    public function persist()
    {
        if (!is_dir(dirname($this->filePath))) {
            mkdir(dirname($this->filePath), 0777, true);
        }

        file_put_contents($this->filePath, Yaml::dump($this->data, max(Util::getArrayDepth($this->data), 2), 4));

        return $this;
    }

    protected function getConfigDirPath($name)
    {
        if (!static::$rootPath) {
            throw new RuntimeException('No root path defined');
        }

        return static::$rootPath;
    }

    protected function convertNameToFile($name)
    {
        return $name;
    }

    protected function convertNameToDefaultFile($name)
    {
        return "$name.default";
    }

    protected function loadData($force = false)
    {
        // Dont load if already loaded
        if ($this->loaded or $force) {
            return $this;
        }

        if (!is_file($this->filePath)) {
            $this->loaded = true;

            return $this;
        }

        $data = Yaml::parse(file_get_contents($this->filePath));
        if (is_file($this->fileDefaultPath)) {
            $defaultData = Yaml::parse(file_get_contents($this->filePath));
            $data = array_replace_recursive($defaultData, $data);
        }
        $this->data = $data;
        $this->loaded = true;

        return $this;
    }
}