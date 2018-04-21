<?php

namespace WHMCS\Module\Blazing\Proxy\Seller\UserService;

use Buzz\Browser;
use Buzz\Client\Curl;
use Buzz\Exception\RequestException;
use ErrorException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use WHMCS\Module\Blazing\Proxy\BillingDashboard\Application\Bootstrapper;
use WHMCS\Module\Blazing\Proxy\Seller\Logger;
use WHMCS\Module\Blazing\Proxy\Seller\UserService;
use WHMCS\Module\Framework\Addon;

class UserServiceCallback
{

    protected $userService;

    /**
     * UserServiceCallback constructor.
     *
     * @param $userService
     */
    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }


    public function call($event, $throw = false)
    {
        /** @var Addon $moduleSeller */
        $moduleSeller = Addon::getInstanceById('blazing_proxy_seller');

        $urls = [
            $this->userService->getCallbackUrl(),
            $moduleSeller->getConfig('proxyDashboardUrl') . '/checkout/callback/whmcs'
        ];
        $urls = array_filter($urls);

        /** @var Addon $moduleDashboard */
        $moduleDashboard = Addon::isModuleEnabled('blazing_proxy_billing_dashboard') ?
            Addon::getInstanceById('blazing_proxy_billing_dashboard', false) : false;

        if (!$moduleDashboard or 'external' == strtolower($moduleSeller->getConfig('dashboardMode'))) {
            if ($urls) {
                // Iterate over all available options
                foreach ($urls as $url) {
                    $result = $this->callExternal($url, $event, $throw);

                    if ($result) {
                        return $result;
                    }
                }

                return false;
            }
            else {
                Logger::err('No external dashboard url configured', [
                    'mode' => $moduleSeller->getConfig('dashboardMode')
                ]);

                if ($throw) {
                    throw new ErrorException('No external dashboard url configured');
                }

                return false;
            }
        }
        else {
            if (!$moduleDashboard) {
                if ($throw) {
                    Logger::err('No blazing_proxy_billing_dashboard module installed', [
                        'mode' => $moduleSeller->getConfig('dashboardMode')
                    ]);
                }
                else {
                    return false;
                }
            }

            return $this->callInternal($moduleDashboard, $event, $throw);
        }
    }

    protected function callExternal($url, $event, $throw)
    {
        try {
            $client = new Curl();
            $client->setVerifyHost(false);
            $client->setVerifyPeer(false);
            $client->setTimeout(15);

            $tries = 10;
            while (true) {
                try {
                    /** @var \Buzz\Message\Response $response */
                    $response = (new Browser($client))->submit($url, [
                        'userWhmcsId' => $this->userService->getUserId(),
                        'service'     => [
                            'id'        => $this->userService->getId(),
                            'productId' => $this->userService->getProductId(),
                            'userId'    => $this->userService->getUserId(),
                        ],
                        'event'       => $event
                    ] + Logger::prepareMasterRequestParameter(), 'post');

                    if (200 !== $response->getStatusCode()) {
                        throw new RequestException(sprintf('Response code - %s', $response->getStatusCode()));
                    }
                    $data = @json_decode($response->getContent(), true);
                    if (!$data or empty($data['status']) or 'success' != $data['status']) {
                        throw new RequestException('Bad response');
                    }

                    Logger::debug('Service callback response', ['response' => $response->getContent(), 'url' => $url]);
                    break;
                }
                catch (RequestException $e) {
                    $tries--;

                    // Stop trying
                    if ($tries <= 0) {
                        // Rethrow an exception
                        throw $e;
                    }

                    Logger::warn('Service callback exception', [
                        'triesLeft' => $tries,
                        'response'  => $response ? substr($response->getContent(), 0, 1000) : null,
                        'url' => $url
                    ]);

                    sleep(1);
                }
            }

            return true;
        } catch (\Exception $e) {
            Logger::warn('Service callback exception - ' . $e->getMessage() . ' (' . get_class($e) . ')', ['url' => $url]);

            if ($throw) {
                throw $e;
            }

            return false;
        }
    }

    protected function callInternal(Addon $module, $event, $throw)
    {
        try {
            $app = Bootstrapper::bootstrapApp($module, Request::createFromGlobals());
            $app->flush();
            $url = $app[ 'url_generator' ]->generate( 'callback_whmcs');
            $request = Request::create($url, 'POST', [
                'userWhmcsId' => $this->userService->getUserId(),
                'service'     => [
                    'id'        => $this->userService->getId(),
                    'productId' => $this->userService->getProductId(),
                    'userId'    => $this->userService->getUserId(),
                ],
                'event'       => $event
            ]);
            /** @var Response $response */
            $response = $app->handle($request);

            $data = @json_decode($response->getContent(), true);
            if (!$data or empty($data['success']) or 'success' != $data['success']) {
                throw new ErrorException('Bad response');
            }

            Logger::debug('Callback handler response', ['response' => $response->getContent()]);
        }
        catch (\Exception $e) {
            Logger::warn('Callback handler exception', ['exception' => $e->getMessage()]);

            if ($throw) {
                throw $e;
            }
        }

        return true;
    }
}
