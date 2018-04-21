<?php

namespace Application;

use ProxyReseller\Exception\ApiException;
use Axelarge\ArrayTools\Arr;
use Blazing\Logger\Logger;
use Symfony\Component\HttpKernel\HttpKernel;

class AbstractApiController extends AbstractController
{
    /** @var Logger */
    protected $logger;

    // Events

    protected function onControllerRequest(callable $controller)
    {
        if ($this->logger) {
            // Add logger index
            $this->logger->addSharedIndex('action', $this->getActionIdByController($controller));
        }
    }

    protected function onControllerResult($result)
    {
        // Do not log sub requests
        if (HttpKernel::SUB_REQUEST == $this->requestType) {
            return;
        }

        if ($this->logger) {
            $action = $this->logger->getSharedIndex('action') or $action = $this->getActionIdByController($this->controller);

            // Cut large data
            if (!empty($result[ 'list' ]) and 10 < count($result[ 'list' ])) {
                $result[ 'list' ] = array_merge(array_slice(array_values($result[ 'list' ]), 0, 10), ['large data...']);
            }

            $this->logger->info("$action: OK", [
                'parameters' => $this->getRequestParameters(),
                'response'   => is_string($result) ? '[html]' : $result
            ]);
        }
    }

    protected function onControllerException(\Exception $exception)
    {
        if ($this->logger) {
            $action = $this->logger->getSharedIndex('action');
            if (!$action and $this->controller) {
                $action = $this->getActionIdByController($this->controller);
            }
            if (!$action) {
                $action = 'unknown';
            }

            $method = 'err';
            if ($exception instanceof ApiException) {
                $method = Arr::getOrElse([
                    ApiException::LOG_WARN => 'warn',
                    ApiException::LOG_INFO => 'info',
                    ApiException::LOG_DEBUG => 'debug',
                ], $exception->getLogLevel(), $method);
            }

            $logData = [
                'parameters'     => $this->getRequestParameters(),
                'message'        => $exception->getMessage(),
                'exception' => [
                    'file'  => $exception->getFile(),
                    'line'  => $exception->getLine(),
                    'trace' => json_encode($exception->getTrace())
                ],
            ];
            if ($exception instanceof ApiException) {
                $logData[ 'data' ] = $exception->getVariables();
                $logData[ 'errorCode' ] = $exception->getErrorCode();
            }
            $this->logger->$method(sprintf("%s: EXCEPTION %s at %s:%s",
                $action, $exception->getMessage(), pathinfo($exception->getFile(), PATHINFO_FILENAME), $exception->getLine()
            ), $logData);
        }
    }

    // Validators

    /**
     * Throw an exception if assert fails
     *
     * @param $assertion
     * @param $message
     * @param array $variables
     * @param null $code
     * @param string $level
     * @return $this
     * @throws ApiException
     */
    protected function assertOrException($assertion, $message, $variables = [], $code = null, $level = ApiException::LOG_WARN)
    {
        if (!$assertion) {
            throw new ApiException($message, $variables, $level, $code);
        }

        return $this;
    }

    /**
     * Throw an exception if assert not fails
     *
     * @param $assertion
     * @param $message
     * @param array $variables
     * @param null $code
     * @param string $level
     * @return $this
     */
    protected function assertFalseOrException($assertion, $message, $variables = [], $code = null, $level = ApiException::LOG_WARN)
    {
        if ($assertion) {
            $this->assertOrException(false, $message, $variables, $code, $level);
        }

        return $this;
    }

    // Helpers

    protected function getActionIdByController(callable $controller)
    {
        /** @noinspection PhpParamsInspection */
        if (!is_array($controller) and 2 != count($controller)) {
            return 'Unknown#unknown-method';
        }

        $class = Helper::getClassBasename($controller[ 0 ]);
        $method = str_replace('Action', '', $controller[ 1 ]);

        return "$class#$method";
    }

    protected function getRequestParameters()
    {
        if ($this->request) {
            return array_merge(
                Arr::except($this->request->attributes->all(), ['_controller', '_route', '_route_params', '_route_request_id']),
                $this->request->query->all(),
                $this->request->request->all()
            );
        }

        return [];
    }
}
