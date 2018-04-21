<?php

namespace WHMCS\Module\Blazing\Proxy\Seller;

use WHMCS\Module\Framework\Helper;

class Settings
{

    protected $data = [];
    protected $loaded = false;
    protected $dataChanged = [];

    public static function getInstance()
    {
        static $instance;

        if (!$instance) {
            $instance = new self();
        }

        return $instance;
    }

    protected function __construct() { }

    public function get($key, $default = null)
    {
        $this->loadData();

        return isset($this->data[ $key ]) ? $this->data[ $key ] : $default;
    }

    public function getAll()
    {
        $this->loadData();

        return $this->data;
    }

    public function set($key, $value)
    {
        $this->loadData();

        $this->data[ $key ] = $value;

        // Mark value as changed
        if (!in_array($key, $this->dataChanged)) {
            $this->dataChanged[] = $key;
        }

        return $this;
    }

    public function persist()
    {
        foreach ($this->dataChanged as $key) {
            $encoded = json_encode($this->data[ $key ]);
            Helper::db()->insert('INSERT INTO mod_blazing_proxy_seller_settings 
              (`key`, `data`) VALUES(?, ?)
              ON DUPLICATE KEY UPDATE `data` = ?', [$key, $encoded, $encoded]);
        }

        return $this;
    }

    protected function loadData($force = false)
    {
        // Dont load if already loaded
        if ($this->loaded or $force) {
            return $this;
        }

        $loadedData = Helper::conn()->select('SELECT * FROM mod_blazing_proxy_seller_settings');
        Helper::restoreDb();

        $data = [];
        foreach ($loadedData as $row) {
            $data[ $row[ 'key' ] ] = json_decode($row[ 'data' ]);
        }
        $this->data = $data;
        $this->loaded = true;

        return $this;
    }
}
