<?php

namespace Application;

use Application\Exception\ApiException;
use Blazing\Logger\Logger;
use Common\Events\Emitter;
use Doctrine\DBAL\Connection;
use Silex\Application;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGenerator;

class AbstractController
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
     * @var int
     */
    protected $requestType = HttpKernel::MASTER_REQUEST;

    /**
     * @var array
     */
    protected $controller;

    /**
     * @var bool|string Autoconvert response to one of supported types: "json", "text-rows"
     */
    protected $convertResponse = false;

    protected $dbConnMap = [
        'default' => 'proxy'
    ];

    // Inside /logs directory
    protected $logPath = '';

    /** @var Logger */
    protected $logger;

    protected $requestUid;

    public function __construct(Application $app)
    {
        $this->app = $app;

        $app->on(KernelEvents::CONTROLLER, function (FilterControllerEvent $evt) use (&$determinedController) {
            if ($this->request) {
                return;
            }

            if (!$this->convertResponse) {
                $this->app['config.no_error_handling'] = true;
            }

            $request = $this->request = $evt->getRequest();
            $this->requestType = $evt->getRequestType();

            // Fetch json post request
            if ('POST' == $request->getMethod() and !$request->request->all()) {
                $input = file_get_contents('php://input');
                $request->request->set('$raw', $input);
                if ($input) {
                    try {
                        $json = json_decode($input, true);
                        if ($json) {
                            foreach ($json as $key => $value) {
                                $request->request->set($key, $value);
                            }
                        }
                    }
                    catch (\Exception $e) {
                    }
                }
            }

            // Save determine controller and method
            $this->controller = $evt->getController();

            $this->requestUid = uniqid();
            $request->attributes->set('_route_request_id', $this->requestUid);

            $this->onControllerRequest($evt->getController());
        });

        $app->on(KernelEvents::VIEW, function (GetResponseForControllerResultEvent $evt) {
            if ($this->requestUid == $evt->getRequest()->attributes->get('_route_request_id')) {
                $this->onControllerResult($evt->getControllerResult());

                if ($this->convertResponse) {
                    if ('json' == $this->convertResponse and is_array($evt->getControllerResult())) {
                        $evt->setResponse($this->getJsonResponse($evt->getControllerResult()));
                    }
                    elseif ('text-rows' == $this->convertResponse and is_array($evt->getControllerResult())) {
                        $evt->setResponse($this->getTextPlainResponse($evt->getControllerResult()));
                    }
                }
            }
        });

        $app->on(KernelEvents::EXCEPTION, function (GetResponseForExceptionEvent $evt) {
            if ($this->requestUid == $evt->getRequest()->attributes->get('_route_request_id')) {
                $response = $this->onControllerException($evt->getException());
                if ($response instanceof Response) {
                    $evt->setResponse($response);
                }
                elseif ('json' == $this->convertResponse) {
                    $exception = $evt->getException();

                    $evt->setResponse($this->getJsonResponse(array_merge([
                        'status'  => 'error',
                        'error'   => true,
                        'message' => $exception->getMessage(),
                        'debug'   => [
                            'file'  => pathinfo($exception->getFile(), PATHINFO_FILENAME),
                            'line'  => $exception->getLine(),
                        ]
                    ], $exception instanceof ApiException ? [
                        'code' => $exception->getErrorCode()
                    ] : [])));
                }
                elseif ('text-rows' == $this->convertResponse) {
                    $evt->setResponse(new Response('Exception: "' .
                        lcfirst($evt->getException()->getMessage()) .
                        '" at ' . pathinfo($evt->getException()->getFile(), PATHINFO_FILENAME) . ':' .
                        $evt->getException()->getLine()
                    ));
                }

                if ($evt->getResponse()) {
                    $this->onControllerExceptionResponse($evt->getResponse());
                }
            }
        });

        $app->on(KernelEvents::RESPONSE, function(FilterResponseEvent $evt) {
            if ($this->requestUid == $evt->getRequest()->attributes->get('_route_request_id')) {
                $this->onControllerResponse($evt->getResponse());
            }
        });

        // Logging
        if ($this->logPath) {
            $this->logger = Logger::createRotatingFileLogger($app[ 'config.logs.path' ] . '/' . ltrim($this->logPath, '/'));
            $this->logger->configureAppEnvProcessor();
        }
    }

    // --- Response types

    // text-rows

    protected function getTextPlainResponse($data, $code = 200, array $headers = [])
    {
        if (is_array($data)) {
            $data = join(PHP_EOL, $data);
        }

        return new Response($data, $code, array_merge(['Content-Type' => 'text/plain'], $headers));
    }

    protected function getTextPlainStreamResponse(callable $callback, $code = 200, array $headers = [])
    {
        return StreamedResponse::create($callback, $code, array_merge(['Content-Type' => 'text/plain'], $headers));
    }

    // json

    protected function getJsonResponse($data, $code = 200, array $headers = [])
    {
        return new JsonResponse($data, $code, $headers);
    }

    // --- Shortcuts

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
            $this->dbConnMap[ 'default' ] ];
    }

    protected function getUrl($route, array $parameters = [], $absolute = true)
    {
        return $this->app[ 'url_generator' ]->generate($route, $parameters,
            !$absolute ? UrlGenerator::ABSOLUTE_PATH : UrlGenerator::ABSOLUTE_URL);
    }

    /**
     * @return Emitter
     */
    protected function getEvents()
    {
        return $this->app['events'];
    }

    protected function doSubrequest($route, array $parameters = [])
    {
        return $this->app->handle(Request::create(
            $this->getUrl($route, $parameters),
            $this->app[ 'routes' ]->get($route)->getMethods()[ 0 ],
            $parameters,
            $this->request->cookies->all(),
            [],
            $this->request->server->all()
        ), HttpKernel::SUB_REQUEST);
    }

    /**
     * Subrequests emulation
     *
     * @param Request $request
     * @return $this
     */
    protected function setRequest(Request $request)
    {
        $this->request = $request;

        return $this;
    }

    protected function redirectToRoute($route, $parameters = [])
    {
        return $this->app->redirect($this->app['url_generator']->generate($route, $parameters));
    }

    // --- Lifecycle events

    /**
     * On controller return. If controller returns Response object, method is not called
     *
     * @param $result
     */
    protected function onControllerResult($result)
    {

    }

    /**
     * If any exception thrown in this controller
     *
     * @param \Exception $exception
     * @return void|Response
     */
    protected function onControllerException(\Exception $exception)
    {

    }

    /**
     * Called on after controller response
     *
     * @param Response $response
     */
    protected function onControllerResponse(Response $response)
    {

    }

    /**
     * Called if an exception is thrown and converted to response
     * @param Response $response
     */
    protected function onControllerExceptionResponse(Response $response)
    {

    }

    protected function onControllerRequest(callable $controller)
    {

    }
}