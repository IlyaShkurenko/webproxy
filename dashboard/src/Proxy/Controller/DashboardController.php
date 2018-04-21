<?php

namespace Proxy\Controller;

use Axelarge\ArrayTools\Arr;
use Blazing\Reseller\Api\Api\Entity\PackageEntity;
use Blazing\Common\RestApiRequestHandler\Exception\BadRequestException;
use ErrorException;
use Proxy\Util\Util;
use Silex\Application;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DashboardController extends AbstractController
{
    public function dashboard(Application $app, Request $request) {
        $user = $this->getUser();

        if ($this->getVariableFromFlash('paid')) {
            $this->addFlashError('Your order has been paid successfully. Your proxies will be updated in 10 min');
        }

        $plans = $this->getApi()->packages()->getAll(false, PackageEntity::STATUS_ACTIVE)['list'];
        $ports = $this->getApi()->ports4()->getAll([], [],
            ['country' => 'asc', 'category' => 'asc', 'updated' => 'desc', 'rotated' => 'desc', 'ip' => 'asc'])['list'];
        $ips = Arr::pluck($this->getApi()->user()->getAuthIpList()['list'], 'ip', 'id');

        $hasRotate = false;
        $hasStatic = false;
        foreach($plans as $plan) {
            if (in_array($plan['category'], ['sneaker', 'dedicated', 'semi-3', 'kushang', 'mapple', 'supreme'])) {
                $hasStatic = true;
            } elseif (in_array($plan['category'], ['rotate'])) {
                $hasRotate = true;
            }
        }

        return $app['twig']->render('dashboard/main.html.twig', [
            'email' => $user->getDetails('email'),
            'ports' => $ports,
            'plans' => $plans,
            'user' => $user,
            'ips' => $ips,
            'hasRotate' => $hasRotate,
            'hasStatic' => $hasStatic,
            'userIp' => $request->getClientIp(),
            'apiUsername' => Util::toProxyLogin($user->getDetails()),
            'paymentSuccessful' => !!$request->get('paid')
        ]);
    }

    public function locations(Application $app) {
        $locations = [];
        foreach ($this->getApi()->misc()->getLocationsAvailability()['list'] as $country => $regions) {
            foreach ($regions as $regionId => $data) {
                foreach ($data['categories'] as $category => $count) {
                    if (empty($locations[ $country . $data[ 'city' ] ])) {
                        $locations[ $country . $data[ 'city' ] ] = [
                            'id'      => $regionId,
                            'country' => $country,
                            'region'  => $data[ 'city' ],
                            'state'   => $data[ 'state' ]
                        ];
                    }

                    $locations[ $country . $data[ 'city' ] ][ $category ] = $count;
                }
            }
        }

        $plans = [];
        foreach($this->getApi()->ports4()->getAllocation([], ['dedicated', 'semi-3'])['list'] as $country => $categories) {
            foreach ($categories as $category => $data) {
                foreach ($data['regions'] as $region => $count) {
                    $type = $country . "-" . $category;

                    if(!isset($plans[$type])) {
                        $plans[$type] = [
                            'country' => $country,
                            'category' => $category,
                            'count' => 0,
                            'region' => []
                        ];
                    }

                    $plans[$type]['count'] += $count;
                    $plans[$type]['region'][$region] = $count;
                }
            }
        }

        // Reorder plans as first should be a plan which should be assigned
        usort($plans, function(array $plan1, array $plan2) {
            return
                (!isset($plan1['region'][0]) and isset($plan2['region'][0])) ?
                    1 :
                    ((isset($plan1['region'][0]) and !isset($plan2['region'][0])) ?
                        -1:
                        0
                    );


        });

        return $app['twig']->render('dashboard/locations.html.twig', ['plans' => $plans, 'locations' => $locations]);
    }

    public function saveFormat($format) {
        $this->getApi()->user()->updateSettings(['authType' => $format]);
        $this->getUser()->refreshData();

        return new JsonResponse(['success' => true]);
    }

    public function saveLocations(Application $app, Request $request) {
        $ports = $request->get('ports');

        foreach ($ports as $country => $categories) {
            foreach ($categories as $category => $regions) {
                try {
                    $this->getApi()->ports4()->setAllocation($country, $category,
                        array_filter($regions, function($v) { return (int) $v; }));
                }
                catch (\Exception $e) {
                    return new JsonResponse([
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        $response = ['success' => true];

        if ($app['session']->get('redirect_dashboard')) {
            $response['redirectUrl'] = $app['url_generator']->generate('dashboard');
            $app['session']->remove('redirect_dashboard');
        }

        return new JsonResponse($response);
    }

    public function sneaker(Application $app, Request $request) {
        if ($request->getMethod() == "POST") {
            $location = $request->get('location');

            if (!in_array($location, ['LA','NY'])) {
                return $this->addFlashError('Invalid Location Passed')->redirectToRoute('sneaker');
            }

            $this->getApi()->user()->updateSneakerLocation($location);
            $this->getUser()->refreshData();

            if ($app['session']->get('redirect_dashboard')) {
                $app['session']->remove('redirect_dashboard');

                return $this->redirectToRoute('dashboard');
            }
            else {
                $this->addFlashSuccess('Sneaker Location Saved');

                return $this->redirectToRoute('sneaker');
            }
        }
        return $app['twig']->render('dashboard/sneaker.html.twig');
    }

    public function settings() {
        return $this->app['twig']->render('dashboard/settings.html.twig', [
            'proxyUrl' => $this->app['config.proxyUrl']
        ]);
    }

    public function saveSettings()
    {
        $options = [
            'rotationType' => $this->request->get('rotationType'),
            'rotate_ever'   => $this->request->get('rotateEver'),
            'rotate_30'     => $this->request->get('rotate30'),
        ];

        // Drop out null options
        foreach ($options as $option => $value) {
            if (is_null($value)) {
                unset($options[ $option ]);
            }
        }

        if ($options) {
            $this->getApi()->user()->updateSettings($options);

            // Update session
            $this->getUser()->refreshData();
        }

        return new JsonResponse(['status' => 'success']);
    }

    public function replace() {
        $proxies = $this->getApi()->ports4()->getAll([], ['dedicated', 'semi-3', 'sneaker'],
            ['country' => 'asc', 'category' => 'asc', 'updated' => 'desc', 'rotated' => 'desc', 'ip' => 'asc'])['list'];
        $replacements = $this->getApi()->ports4()->getAvailableReplacements()['list'];

        return $this->app['twig']->render('dashboard/replace.html.twig', [
            'replacements' => $replacements,
            'proxies' => $proxies
        ]);
    }

    public function replaceIp(Application $app, $id)
    {
        $ports = Arr::pluck($this->getApi()->ports4()->getAll([], ['dedicated', 'semi-3', 'sneaker'])['list'], 'ip', 'id');

        try {
            if (empty($ports[$id])) {
                throw new ErrorException('No port is found');
            }

            $ip = $ports[$id];
            $this->getApi()->ports4()->setPendingReplace($ip);
        }
        catch (ErrorException $e) {
            return $this
                ->addFlashError($e->getMessage())
                ->redirectToRoute('replace');
        }

        return $this->addFlashSuccess('Replacement request received. See table below')
            ->redirectToRoute('replace');
    }

    public function replaceMultipleIp(Request $request)
    {
        $rawData = trim($request->get('replace', ''));
        $this->saveVariableToFlash('replaceMultipleIp', $rawData);
        $data = [];

        // Parse data
        if ($rawData) {
            $data = explode("\n", trim($rawData));
            $data = array_map(function($ip) {
                list($ip) = explode(":", trim($ip));
                $ip = trim((string) $ip);

                return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : null;
            }, $data);
            $data = array_filter($data);
        }

        if (!$data) {
            return $this->addFlashError('No valid IP-s passed')->redirectToRoute('replace');
        }

        try {
            $this->getApi()->ports4()->setPendingReplaceMultiple($data);
        }
        catch (ErrorException $e) {
            return $this
                ->addFlashError($e->getMessage())
                ->redirectToRoute('replace');
        }

        return $this->addFlashSuccess('Replacement request received. See table below')
            ->redirectToRoute('replace');
    }

    public function contact(Application $app, Request $request) {
        return $app['twig']->render('dashboard/contact.html.twig');
    }

    public function addIp($ip)
    {
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return new JsonResponse([
                'error'   => true,
                'message' => "Not a valid IP address, must be valid IPv4 address."
            ]);
        }

        try {
            $ip = $this->getApi()->user()->addAuthIp($ip)['ip'];
        }
        catch (BadRequestException $e) {
            return new JsonResponse([
                'status'  => 'error',
                'message' => $e->getMessage()
            ]);
        }

        return new JsonResponse([
            'status' => 'success',
            'ip' => [
                'id' => $ip['id'],
                'ip' => $ip['ip']
            ]
        ]);
    }

    public function removeIp($id) {
        $this->getApi()->user()->deleteAuthIp($id);

        return new JsonResponse(['status' => 'success']);
    }

    public function setRotationTime(Request $request)
    {
        $time = $request->get('time');
        $portId = $request->get('portId');

        try {
            $this->getApi()->ports4()->setRotationTime($portId, $time);
        }
        catch (BadRequestException $e) {
            return new JsonResponse([
                'status'  => 'error',
                'message' => $e->getMessage()
            ]);
        }

        return new JsonResponse(['status' => 'success']);
    }

    public function exportProxies($type)
    {
        $pass = $this->getUser()->getDetails('authType') == 'PW';

        // Parse type
        $typeParsed = explode('-', $type);
        $country    = '';
        $category   = '';
        if (count($typeParsed) >= 2) {
            $country = $typeParsed[ 0 ];
            // category can be "semi-3", so push out country and concatenate category parts
            $category = join('-', array_slice($typeParsed, 1));
        }

        $proxies = $this->getApi()->ports4()->getAll($country ? [$country] : [], $category ? [$category] : [],
            ['country' => 'asc', 'category' => 'asc', 'updated' => 'desc', 'rotated' => 'desc', 'ip' => 'asc'])['list'];

        $response = [];
        foreach ($proxies as $row) {
            if (in_array($row['category'], ['rotating', 'google'])) {
                $response[] = $row['serverIp'] . ":" . $row['port'];
            } else {
                if ($pass) {
                    $response[] = $row['ip'] . ":" . $this->app['config.port.pwd'] . ":" .
                        Util::toProxyLogin($this->getUser()->getDetails()) . ":" .
                        $this->getUser()->getDetails('apiKey');
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
}