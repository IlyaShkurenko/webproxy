<?php

namespace Blazing\Vpn\Client\Vendor\ApiRequestHandler;

use Blazing\Vpn\Client\Vendor\Monolog\Logger;
use ErrorException;

class ApiConfiguration
{

    protected $protocol = 'http';

    protected $host = '';

    protected $url = '/api/path/should/be/defined/in/configuration';

    protected $apiToken;

    protected $logger;

    protected $requestOptions = [
        'timeout'      => 30,
        'verifyHost'   => false,
        'verifyPeer'   => false,
        'maxRedirects' => 5,
        'retry'        => 3
    ];

    /**
     * @param string $host
     * @param string $url
     * @param null $protocol
     * @return static
     */
    public static function build($host, $url, $protocol = null)
    {
        $self = new static();

        if ($host) {
            $self->setHost($host);
        }

        if ($url) {
            $self->setUrl($url);
        }

        if ($protocol) {
            $self->setProtocol($protocol);
        }

        return $self;
    }

    /**
     * Get protocol
     *
     * @return string
     * @throws ErrorException
     */
    public function getProtocol()
    {
        if (!$this->protocol) {
            throw new ErrorException('Protocol is not configured!');
        }

        return $this->protocol;
    }

    /**
     * Set protocol
     *
     * @param string $protocol
     * @return $this
     */
    public function setProtocol($protocol)
    {
        $this->protocol = $protocol;

        return $this;
    }

    /**
     * Get host
     *
     * @return string
     * @throws ErrorException
     */
    public function getHost()
    {
        if (!$this->host) {
            throw new ErrorException('Host is not configured!');
        }

        return $this->host;
    }

    /**
     * Set host
     *
     * @param string $host
     * @return $this
     */
    public function setHost($host)
    {
        $this->host = $host;

        return $this;
    }

    /**
     * Get url
     *
     * @return string
     * @throws ErrorException
     */
    public function getUrl()
    {
        if (!$this->url) {
            throw new ErrorException('Url is not configured!');
        }

        return $this->url;
    }

    /**
     * Set url
     *
     * @param string $url
     * @return $this
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Get apiToken
     *
     * @return mixed
     * @throws ErrorException
     */
    public function getApiToken()
    {
        if (!$this->apiToken) {
            throw new ErrorException('API token is not configured!');
        }

        return $this->apiToken;
    }

    /**
     * Set apiToken
     *
     * @param mixed $apiToken
     * @return $this
     */
    public function setApiToken($apiToken)
    {
        $this->apiToken = $apiToken;

        return $this;
    }

    /**
     * Get logger
     *
     * @return Logger
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * Set logger
     *
     * @param Logger $logger
     * @return $this
     */
    public function setLogger(Logger $logger)
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * Get request options
     *
     * @return array
     */
    public function getRequestOptions()
    {
        return $this->requestOptions;
    }

    /**
     * Get request option or default value
     *
     * @param $option
     * @param null $default
     * @return mixed|null
     */
    public function getRequestOption($option, $default = null)
    {
        return isset($this->requestOptions[ $option ]) ? $this->requestOptions[ $option ] : $default;
    }

    /**
     * @param $option
     * @param $value
     * @return $this
     */
    public function setRequestOption($option, $value)
    {
        if (isset($this->requestOptions[ $option ])) {
            $this->requestOptions[ $option ] = $value;
        }

        return $this;
    }
}
