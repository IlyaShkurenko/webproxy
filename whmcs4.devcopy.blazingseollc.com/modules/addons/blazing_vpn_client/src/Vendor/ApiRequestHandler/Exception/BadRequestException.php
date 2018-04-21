<?php

namespace Blazing\Vpn\Client\Vendor\ApiRequestHandler\Exception;

use ErrorException;

class BadRequestException extends ErrorException
{

    /**
     * @var array
     */
    protected $data = [];
    /**
     * @var string
     */
    protected $response;

    public function __construct(
        $message = "",
        array $data = [],
        $response = ''
    ) {
        parent::__construct($message);
        $this->data = $data;
        $this->response = $response;
    }

    /**
     * Get response
     *
     * @return string
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * Get data
     *
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    public function getErrorCode()
    {
        return !empty($this->data['code']) ? $this->data['code'] : 'UNKNOWN';
    }
}
