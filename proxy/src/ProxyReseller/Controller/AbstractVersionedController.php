<?php

namespace ProxyReseller\Controller;

use Silex\Application;

class AbstractVersionedController extends AbstractApiController
{
    protected $logPathTemplate = '/api/requests-v{version}.log';

    protected $apiVersion;

    public function __construct(Application $app)
    {
        $this->logPath = str_replace('{version}', $this->getApiVersion(), $this->logPathTemplate);

        parent::__construct($app);

        if ($this->logger) {
            $this->logger->setAppName(str_replace('/all', '/reseller-api-' . $this->getApiVersion(), $this->logger->getAppName()));
        }
    }

    /**
     * Get API version
     * @return string
     */
    protected function getApiVersion()
    {
        if (!$this->apiVersion) {
            $className = get_class($this);
            if (preg_match('~ApiV(\d+)~i', $className, $match)) {
                $intVersion = $match[ 1 ];
                $version = [];
                // Iterate over characters
                for ($i = 0; $i < strlen($intVersion); $i++) {
                    $version[] = substr($intVersion, $i, 1);
                }

                // The final check, do parsing is successful
                if ($version) {
                    $this->apiVersion = join('.', $version);
                }
            }

            // Fallback
            if (!$this->apiVersion) {
                $this->apiVersion = '0.1';
            }
        }

        return $this->apiVersion;
    }
}
