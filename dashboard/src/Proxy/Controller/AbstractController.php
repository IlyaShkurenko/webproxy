<?php

namespace Proxy\Controller;

use Blazing\Logger\Logger;
use Blazing\Reseller\Api\Api;
use Doctrine\DBAL\Connection;
use ErrorException;
use Proxy\User;
use Proxy\Util\Util;
use ReCaptcha\ReCaptcha;
use RuntimeException;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGenerator;

abstract class AbstractController
{
    /**
     * @var Application
     */
    protected $app;

    /**
     * @var Request
     */
    protected $request;

    protected $dbConnMap = [
        'default' => 'proxy'
    ];

    /** @var Logger */
    protected $log;

    public function __construct(Application $app)
    {
        $this->app = $app;

        $app->on(KernelEvents::CONTROLLER, function (FilterControllerEvent $evt) {
            $this->request = $evt->getRequest();
        });

        // Real or null logger
        $this->log = $app['logs'] or $this->log = new Logger();
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

    /**
     * Get API
     *
     * @return Api
     */
    protected function getApi()
    {
        return $this->app['api'];
    }

    /**
     * Get session user
     *
     * @return User
     */
    protected function getUser()
    {
        return $this->app['session.user'];
    }

    protected function addFlashSuccess($text)
    {
        $this->app['session']->getFlashBag()->add('success', $text);

        return $this;
    }

    protected function addFlashError($text)
    {
        $this->log->warn($text);
        $this->app['session']->getFlashBag()->add('error', $text);

        return $this;
    }

    protected function saveVariableToFlash($key, $value)
    {
        $this->app['session']->getBag('vars')->set($key, $value);

        return $this;
    }

    protected function getVariableFromFlash($key)
    {
        return $this->app['session']->getBag('vars')->get($key);
    }

    protected function getUrl($route, $parameters, $absolute = true)
    {
        return $this->app[ 'url_generator' ]->generate($route, $parameters,
            !$absolute ? UrlGenerator::ABSOLUTE_PATH : UrlGenerator::ABSOLUTE_URL);
    }

    protected function redirectToRoute($route, $parameters = [])
    {
        return $this->app->redirect($this->app['url_generator']->generate($route, $parameters));
    }

    protected function redirectToRemoteRoute($route, $parameters = [])
    {
        return $this->app->redirect(ltrim(
            $this->app[ 'url_generator' ]->generate($route, $parameters, UrlGenerator::RELATIVE_PATH),
            './')
        );
    }

    protected function getCurrentUrlName()
    {
        $globals = $this->app['twig']->getGlobals();

        if (empty($globals['CURRENT_ROUTE'])) {
            throw new RuntimeException('Cannot determine current route');
        }

        return $globals['CURRENT_ROUTE'];
    }

    protected function sendEmail($to, $subject, $text, $from = 'support@blazingseollc.com')
    {

        $userInBilling = false;
        $sent = false;

        try {
            $userDetails = $this->app[ 'integration.whmcs.api' ]->getClientDetailsByEmail($to);
            if (!empty($userDetails[ 'userid' ])) {
                $userInBilling = $userDetails[ 'userid' ];
            }

            if ($userInBilling) {
                $result = $this->app[ 'integration.whmcs.api' ]->api('SendEmail', [
                    'id'            => $userInBilling,
                    'customsubject' => $subject,
                    'custommessage' => $text,

                    // Dummy type
                    'customtype' => 'general',
                ]);

                $sent = (!empty($result[ 'result' ]) and 'success' == $result[ 'result' ]);
            }
        }
        catch (ErrorException $e) {
            if (!empty($this->app['logs'])) {
                $this->app['logs']->warn('Sending email, exception: ' . $e->getMessage(), ['exception' => $e]);
            }
        }

        if (!$sent) {
            if (!empty($this->app['logs'])) {
                $this->app['logs']->notice('Sending email, using the default mailer for ' . $to);
            }

            $text = strip_tags($text);
            $sent = mail($to, $subject, $text, join("\r\n", [
                "From: $from",
                "Reply-To: $from",
            ]));
        }

        if (!empty($this->app['logs'])) {
            $this->app['logs']->debug("Email \"$subject\" has " . ($sent ? '' : 'not ') . 'been sent', [
                'email'   => $to,
                'subject' => $subject,
                'text'    => $text,
                'from'    => $from
            ]);
        }

        return $sent;
    }

    protected function validateCaptcha()
    {
        // Turned off completely
        if (!$this->app['config.captcha.enabled']) {
            return true;
        }

        if ($this->getVariableFromFlash('captcha.turn_off_1')) {
            return true;
        }

        // Check on the specific pages
        switch ($this->getCurrentUrlName()) {
            case 'quick_buy':
            case 'do_quick_buy':
                if (!$this->app[ 'config.captcha.page.signup' ]) {
                    return true;
                }
                break;

            case 'logintype':
                if (!$this->app[ 'config.captcha.page.signin' ]) {
                    return true;
                }
                break;
        }

        if (!$this->request) {
            throw new ErrorException('No request passed to validate captcha');
        }

        $captcha = new ReCaptcha($this->app['config.captcha.secret']);
        $response = $captcha->verify($this->request->get('g-recaptcha-response'), $this->request->getClientIp());

        if ($response->isSuccess()) {
            return true;
        }

        if ($this->app['logs']) {
            $this->app['logs']->warn('Captcha error', [
                'errors' => $response->getErrorCodes(),
                'hostname' => $response->getHostname()
            ]);
        }

        throw new ErrorException('Captcha error');
    }

    protected function disableCaptchaOnTheNextCheck()
    {
        $this->saveVariableToFlash('captcha.turn_off_1', 1);

        return $this;
    }

    protected function redirectPost($url, array $fields = [], $text = 'Redirection...')
    {
        if ($this->log) {
            $this->log->debug("Submitting to \"$url\"", [
                'url' => $url,
                'fields' => $fields,
                'text' => $text
            ]);
        }

        $data = [];
        foreach (explode('&', urldecode(http_build_query($fields))) as $line) {
            list ($key, $value) = explode('=', $line);
            $data[$key] = $value;
        }

        return $this->app[ 'twig' ]->render('form_submit.html.twig', [
            'url'    => $url,
            'fields' => $data,
            'text'   => $text,
        ]);
    }
}
