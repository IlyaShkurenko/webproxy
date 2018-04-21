<?php

namespace Proxy\Controller;

use Buzz\Browser;
use Proxy\Util\Util;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class ApiController extends AbstractController
{
    public function proxyIPv4ListAction($email, $key)
    {
        try {
            $user = $this->getApi()->userManagement()->findUserByLoginOrEmail($email);
            if ($key != $user['apiKey']) {
                throw new \ErrorException('API key is invalid');
            }
        }
        catch (\ErrorException $e) {
            throw new BadRequestHttpException("Email or API key is wrong");
        }

        // Ok, user is valid one
        $proxies = $this->getApi()->ports4()->getAll([], [],
            ['country' => 'asc', 'category' => 'asc', 'updated' => 'desc', 'rotated' => 'desc', 'ip' => 'asc'],
            $user['userId']
        )['list'];

        $response = [];
        foreach ($proxies as $row) {
            if (in_array($row['category'], ['rotating', 'google'])) {
                $response[] = $row['serverIp'] . ":" . $row['port'];
            } else {
                if ('PW' == $user['authType']) {
                    $response[] = $row['ip'] . ":" . $this->app['config.port.pwd'] . ":" .
                        Util::toProxyLogin($user) . ":" . $user['apiKey'];
                } else {
                    $response[] = $row['ip'] . ":" . $this->app['config.port.ip'];
                }
            }
        }

        return new Response(
            join("\n", $response),
            200,
            [
                'cache-control' => 'no-cache, must-revalidate', // HTTP/1.1
                'expires'       => 'Sat, 26 Jul 1997 05:00:00 GMT', // Date in the past
                'content-type'  => 'text/plain'
            ]
        );
    }

    public function proxyIPv6ListAction($email, $key)
    {
        try {
            $user = $this->getApi()->userManagement()->findUserByLoginOrEmail($email);
            if ($key != $user['apiKey']) {
                throw new \ErrorException('API key is invalid');
            }
        }
        catch (\ErrorException $e) {
            throw new BadRequestHttpException("Email or API key is wrong");
        }

        // Ok, user is valid one
        $proxies = $this->getApi()->ports6()->getAll()['list'];

        $response = [];
        foreach ($proxies as $row) {
            $response[] = join(':', [$row['serverIp'], $row['serverPort'], $row['login'], $user['apiKey']]);
        }

        return new Response(
            join("\n", $response),
            200,
            [
                'cache-control' => 'no-cache, must-revalidate', // HTTP/1.1
                'expires'       => 'Sat, 26 Jul 1997 05:00:00 GMT', // Date in the past
                'content-type'  => 'text/plain'
            ]
        );
    }

    protected function proxyRequest(Request $request, $url)
    {
        try {
            $browser = new Browser();
            $result = $browser->submit($url, $request->query->all(), 'get');
            $status = 500;
            if (!empty($result->getHeaders()[0]) and preg_match('~^HTTP[^\s]+\s(\d+)~i', $result->getHeaders()[0], $match)) {
                $status = (int) $match[1];
            }
            $headers = [];
            foreach ($result->getHeaders() as $header) {
                if (false !== strpos($header, ':')) {
                    list ($key, $value) = explode(':', $header);
                    $headers[$key] = $value;
                }
            }
            return new Response($result->getContent(), $status, $headers);
        }
        catch (\Exception $e) {
            return new Response($e->getMessage(), 500);
        }
    }
}
