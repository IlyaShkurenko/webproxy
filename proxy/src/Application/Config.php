<?php

namespace Application;

use Axelarge\ArrayTools\Arr;
use ErrorException;
use Gears\Arrays;
use Symfony\Component\Yaml\Yaml;

class Config
{
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
            $instances[$name] = new self($name);
        }

        return $instances[$name];
    }

    protected function __construct($name) {
        $ds = DIRECTORY_SEPARATOR;
        $appPath = $this->getAppPath();
        $this->filePath = "$appPath{$ds}config{$ds}{$name}.yml";
        $this->fileDefaultPath = "$appPath{$ds}config{$ds}{$name}.default.yml";
    }

    public function get($key, $default = null)
    {
        $this->loadData();

        return Arr::getNested($this->data, $key, $default);
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

        file_put_contents($this->filePath, Yaml::dump($this->data, 2, 4));

        return $this;
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

    protected function getAppPath()
    {
        $ds = DIRECTORY_SEPARATOR;
        $dir = preg_replace('~[\\\\/]src[\\\\/].*$~', '', __DIR__) . "{$ds}app";

        if (!is_dir($dir)) {
            throw new ErrorException("App dir is not exists by path \"$dir\"");
        }

        return $dir;
    }
}