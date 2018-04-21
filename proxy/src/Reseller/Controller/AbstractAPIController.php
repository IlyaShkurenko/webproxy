<?php

namespace Reseller\Controller;

use Doctrine\DBAL\Connection;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

abstract class AbstractAPIController
{

    /**
     * @var Application
     */
    protected $app;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var array
     */
    protected $reseller;

    protected $convertJsonResponse = true;

    protected $dbConnMap = [
        'default' => 'proxy'
    ];

    public function __construct(Application $app)
    {
        $this->app = $app;

        $app->on(KernelEvents::CONTROLLER, function (FilterControllerEvent $evt) {
            $this->request = $evt->getRequest();
        });

        $app->on(KernelEvents::VIEW, function (GetResponseForControllerResultEvent $evt) {
            if ($this->convertJsonResponse and is_array($evt->getControllerResult())) {
                $evt->setResponse($this->app->json($evt->getControllerResult()));
            }
        });

        $app->on(KernelEvents::EXCEPTION, function(GetResponseForExceptionEvent $evt) {
            if ($this->convertJsonResponse) {
                $evt->setResponse($this->app->json([
                    'error'   => true,
                    'message' => $evt->getException()->getMessage(),
                    'debug' => [
                        'file' => pathinfo($evt->getException()->getFile(), PATHINFO_FILENAME),
                        'line' => $evt->getException()->getLine(),
                    ]
                ]));
            }
        });
    }

    protected function getApiKey($throwIfNotFound = true)
    {
        $apiKey = $this->request->get('api_key');

        if (!$apiKey) {
            if ($throwIfNotFound) {
                $this->throwException('No API Key passed');
            }
        }

        return $apiKey ? $apiKey : false;
    }

    /**
     * Get reseller or throw exception + write that to user log
     * @param bool $throwIfNotFound
     * @return array|bool
     * @throws \ErrorException
     * @throws \Exception
     */
    protected function getReseller($throwIfNotFound = true)
    {
        if (!$this->reseller) {
            $apiKey   = $this->getApiKey(true);
            $reseller = $this->app[ 'app.user_management' ]->getReseller($apiKey);

            if (!$reseller) {
                if ($throwIfNotFound) {
                    $this->throwException('Invalid API Key', $apiKey);
                } else {
                    return false;
                }
            }

            $this->reseller = $reseller;
        }

        return $this->reseller;
    }

    /**
     * Dump data to user log
     *
     * @param string $text
     * @param null|mixed $data
     * @param null|int|array $resellerId
     * @param null|string $actionId
     * @return $this
     * @throws \Exception
     */
    protected function userLog($text, $data = null, $resellerId = null, $actionId = null)
    {
        if (!$actionId) {
            $actionId = $this->getActionId();
        }

        // Get reseller id (only if null, to prevent indefinite loop from getReseller method)
        if (is_null($resellerId)) {
            $resellerId = $this->getReseller(false);
        }

        // Not received so far
        if (!$resellerId) {
            $resellerId = null;
        }

        // Get the value from the reseller array
        if (is_array($resellerId) and !empty($resellerId[ 'id' ])) {
            $resellerId = $resellerId[ 'id' ];
        }

        if (ctype_digit($resellerId)) {
            $resellerId = (int) $resellerId;
        }

        if (!is_null($resellerId) and !is_int($resellerId)) {
            throw new \ErrorException('Reseller id is a wrong value - expected int/null, passed: ' . gettype($resellerId));
        }

        $this->app[ 'app.user_management' ]->writeLog($actionId, $text, $resellerId, $data);

        return $this;
    }

    protected function getActionId()
    {
        foreach (debug_backtrace() as $trace) {
            if (!empty($trace[ 'function' ]) and
                // function name ends by Action (balanceAction)
                (strlen($trace[ 'function' ]) - strlen('Action')) === strpos($trace[ 'function' ], 'Action')
            ) {
                return str_replace('Action', '', $trace[ 'function' ]);
            }
        }

        return 'unknown-method';
    }

    /**
     * Throw an exception and log that to user log
     *
     * @param $message
     * @param null $variables
     * @param string $logMessage If empty, the same message is used
     * @param string $class Exception class
     * @throws \ErrorException
     */
    protected function throwException($message, $variables = null, $logMessage = null, $class = \ErrorException::class)
    {
        $this->userLog($logMessage ? $logMessage : $message, $variables, false);

        throw new $class($message);
    }

    /**
     * Throw an exception if assert fails
     *
     * @param $assertion
     * @param $message
     * @param $variables
     * @param string $logMessage If empty, the same message is used
     * @param string $class Exception class
     * @return $this
     */
    protected function assertOrException($assertion, $message, $variables, $logMessage = null, $class = \ErrorException::class)
    {
        if (!$assertion) {
            $this->throwException($message,$variables, $logMessage, $class);
        }

        return $this;
    }

    /**
     * Throw an exception if assert not fails
     *
     * @param $assertion
     * @param $message
     * @param $variables
     * @param string $logMessage If empty, the same message is used
     * @param string $class Exception class
     * @return $this
     */
    protected function assertFalseOrException($assertion, $message, $variables, $logMessage = null, $class = \ErrorException::class)
    {
        if ($assertion) {
            $this->throwException($message,$variables, $logMessage, $class);
        }

        return $this;
    }

    // Shortcuts

    /**
     * Get database connection
     *
     * @param string $conn
     * @return Connection
     */
    protected function getConn($conn = '')
    {
        return $this->app[ 'dbs' ][ !empty($this->dbConnMap[ $conn ]) ?
            $this->dbConnMap[ $conn ] :
            $this->dbConnMap[ 'default' ]
        ];
    }
}
