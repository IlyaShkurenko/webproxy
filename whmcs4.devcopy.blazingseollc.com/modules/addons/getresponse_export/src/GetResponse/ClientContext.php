<?php

namespace WHMCS\Module\Blazing\Export\GetResponse;

use WHMCS\Module\Blazing\Export\Compiler\CompileContext;

class ClientContext implements CompileContext
{
    /**
     * @var \WHMCS\User\Client
     */
    private $model;

    /**
     * @var array
     */
    private $response;

    /**
     * @var array
     */
    private $config;

    /**
     * UserContext constructor.
     *
     * @param \WHMCS\User\Client $model
     * @param array              $details
     * @param array              $config
     */
    public function __construct($model, array $details, array $config)
    {
        $this->model = $model;
        $this->response = $details;
        $this->config = $config;
    }

    /**
     * @return mixed
     */
    public function getClientModel()
    {
        return $this->model;
    }

    /**
     * @return array|null
     */
    public function getClientDetails()
    {
        return $this->response;
    }

    /**
     * @param null $key
     *
     * @return array|null|mixed
     */
    public function getConfig($key = null)
    {
        if ($key) {
            return isset($this->config[$key]) ? $this->config[$key] : null;
        }
        return $this->config;
    }
}

