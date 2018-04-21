<?php

namespace Proxy\Controller;

use Axelarge\ArrayTools\Arr;
use Blazing\Reseller\Api\Api\Entity\PackageEntity;
use Blazing\Common\RestApiRequestHandler\Exception\BadRequestException;
use Buzz\Exception\InvalidArgumentException;
use ErrorException;
use Gears\Arrays;
use Proxy\Util\TFA;
use Proxy\Util\Util;
use RuntimeException;
use Silex\Application;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernel;

class CheckoutController extends AbstractController
{

    public function checkout($country = false, $category = false)
    {
        // Get bought products, sort by by whmcs asc, id asc
        $bought = $this->getApi()->packages()->getAll()['list'];
        usort($bought, function($row1) { return $row1['source'] == 'whmcs' ? -1 : 1; });
        usort($bought, function($row1, $row2) { return $row1['id'] < $row2['id'] ? -1 : 1; });
        $bought = array_map(function($row) {
            return [
                'country'  => $row[ 'country' ],
                'category' => $row[ 'category' ],
                'amount'   => $row[ 'ports' ],
                'source'   => $row[ 'source' ],
                'id'       => $row[ 'id' ],
                'status'   => $row[ 'status' ],
            ];
        }, $bought);

        // Fix preselected category
        if ('static' == $category) {
            $category = 'dedicated';
        }
        elseif ('rotate' == $category) {
            $category = 'rotating';
        }

        // Get available products (in whmcs)
        $available = array_filter(array_map(function(array $product) use ($bought) {
            // Ignore IPv6 stuff
            if (!$product[ 'meta' ][ 'country' ] or !$product[ 'meta' ][ 'category' ]) {
                return false;
            }
            $enabled = true;

            foreach ($bought as $exist) {
                if ($product[ 'meta' ][ 'country' ] == $exist[ 'country' ] and
                    $product[ 'meta' ][ 'category' ] == $exist[ 'category' ]
                ) {
                    $enabled = false;
                }
            }

            return [
                'country'  => $product[ 'meta' ][ 'country' ],
                'category' => $product[ 'meta' ][ 'category' ],
                'amount'   => $this->getCountBounds($product[ 'meta' ][ 'country' ], $product[ 'meta' ][ 'category' ]),
                'disabled' => !$enabled
            ];
        }, $this->getPricingTiers()));

        foreach ($available as $product) {
            foreach ($bought as $i => $existentProduct) {
                if (!isset($bought[ $i ][ 'product' ])) {
                    $bought[ $i ][ 'product' ] = [];
                }

                if ($product[ 'country' ] == $existentProduct[ 'country' ] and
                    $product[ 'category' ] == $existentProduct[ 'category' ]
                ) {
                    $bought[ $i ][ 'product' ] = $product;
                }
            }
        }

        // Extended status
        if (Arr::any($bought, function(array $row) { return PackageEntity::STATUS_ACTIVE != $row['status']; })) {
            $userServices = $this->app['integration.whmcs.plugin']->getUserProducts($this->getUser()->getDetails('whmcsId'));

            if (!empty($userServices['products'])) {
                $userServices = $userServices['products'];
                $pricingProducts = $this->getPricingTiers();

                // Look over product to find product id, and then status
                foreach ($bought as $i => $package) {
                    // Active product has no status
                    if (PackageEntity::STATUS_ACTIVE == $package['status']) {
                        continue;
                    }

                    $foundService = false;
                    foreach ($pricingProducts as $product) {
                        if ($product['meta']['country'] == $package['country'] and
                            $product['meta']['category'] == $package['category']) {
                            foreach ($userServices as $userService) {
                                // Equality is found
                                if ($product['productId'] == $userService['productId']) {
                                    $foundService = $userService;
                                    break 2;
                                }
                            }
                        }
                    }

                    // Push information
                    if ($foundService) {
                        if (!empty($foundService[ 'statusReason' ])) {
                            $bought[ $i ][ 'extended' ][ 'statusReason' ] = $foundService[ 'statusReason' ];
                        }
                        if (!empty($foundService[ 'unpaidInvoice' ])) {
                            $bought[ $i ][ 'extended' ][ 'invoiceUrl' ] = $foundService[ 'unpaidInvoice' ];
                        }
                    }
                }
            }
        }

        return $this->app['twig']->render('checkout/checkout.html.twig', [
            'upgradableProducts'   => array_values($bought),
            'available'            => array_values($available),
            'migrateFeature'       => true,
            'migrateAutomatically' => true,
            'data'                 => [
                'country'  => $country,
                'category' => $category,
                'quantity' => $this->request->get('quantity')
            ]
        ]);
    }

    public function quickBuy($country = false, $category = false, Request $request)
    {
        if ($this->getUser()->isAuthorized()) {
            return $this->redirectToRoute('checkout', ['country' => $country, 'category' => $category]);
        }

        if ('dedicated' == $country) {
            $country = 'static';
        }

        try {
            $geo = Util::getIpInfo($request->getClientIp());
        }
        catch (\Exception $e) {
            $geo = [];
        }

        if ($this->app['config.maintenance.login']) {
            $this->addFlashError($this->app['config.maintenance.message']);
        }

        return $this->app['twig']->render('checkout/quick-buy.twig', [
            'data'           => array_replace_recursive([
                'plan'     => array_filter([
                    'country'  => $country,
                    'category' => $category,
                    'amount'   => $request->get('quantity')
                ]),
                'city'     => Arrays::get($geo, 'city'),
                'state'    => Arrays::get($geo, 'region'),
                'country'  => Arrays::get($geo, 'country.code'),
                'postcode' => Arrays::get($geo, 'zip'),
            ], $request->query->all(), $request->request->all()),
            'initialRequest' => true,
            'available'      => array_values(array_filter(array_map(function(array $product) {
                // Ignore IPv6 stuff
                if (!$product[ 'meta' ][ 'country' ] or !$product[ 'meta' ][ 'category' ]) {
                    return false;
                }

                return [
                    'country'  => $product[ 'meta' ][ 'country' ],
                    'category' => $product[ 'meta' ][ 'category' ],
                    'amount'   => $this->getCountBounds($product[ 'meta' ][ 'country' ], $product[ 'meta' ][ 'category' ]),
                ];
            }, $this->getPricingTiers()))),
            'countries' => Util::getCountriesList()
        ]);
    }

    public function continueQuickBuy()
    {
        if ($this->getUser()->getSession()->get('tfa.checkoutData')) {
            return $this
                ->disableCaptchaOnTheNextCheck()
                ->redirectPost($this->getUrl('do_quick_buy', []),
                unserialize($this->getUser()->getSession()->get('tfa.checkoutData')),
                'Processing, please wait...'
            );
        }

        return $this->redirectToRoute('quick_buy_empty');
    }

    public function doQuickBuy(Request $request)
    {
        if ($this->app['config.maintenance.login']) {
            return $this->redirectToRoute('quick_buy_empty');
        }

        try {
            $this->validateCaptcha();

            if (!$request->get('email') or !filter_var($request->get('email'), FILTER_VALIDATE_EMAIL)) {
                throw new ErrorException('You did not enter your email or it is invalid');
            }

            if (!$request->get('plan') or
                empty($request->get('plan')['country'][0]) or
                empty($request->get('plan')['category'][0]) or
                empty($request->get('plan')['amount'][0])) {
                throw new ErrorException('No plan/amount selected');
            }

            if ($this->getUser()->getTFA()) {
                $this->getUser()->getTFA()->setStrategy(TFA::VALIDATE_STRATEGY_IP_REQUIRED);
                if (!$this->getUser()->getTFA()->isValidated($request->get('email'))) {
                    $this->getUser()->getSession()->set('tfa.requiredVerification', 1);
                    $this->getUser()->getSession()->set('tfa.userKey', $request->get('email'));
                    $this->getUser()->getSession()->set('tfa.checkoutData', serialize($request->request->all()));
                    $this->getUser()->getSession()->set('tfa.redirect', $this->getUrl('quick_buy_continue_tfa', []));

                    return $this->redirectToRoute('tfa');
                }
            }

            $userData = [
                'email' => $request->get('email'),
                'password2' => $request->get('password'),

                'firstname' => $request->get('firstname'),
                'lastname' => $request->get('lastname'),
                'phonenumber' => $request->get('phone'),

                'companyname' => $request->get('company'),
                'address1' => $request->get('address'),
                'city' => $request->get('city'),
                'state' => $request->get('state'),
                'postcode' => $request->get('postcode'),
                'country' => $request->get('country'),
            ];

            if (!$userData['firstname']) {
                $userData['firstname'] =
                    ucwords(str_replace(['.', '+'], ' ', Arrays::get(explode('@', $userData['email']), 0, '')));
            }
            if (!$userData['lastname'] and false !== strpos($userData['firstname'], ' ')) {
                $userData['lastname'] = substr($userData['firstname'], strpos($userData['firstname'], ' ') + 1);
                $userData['firstname'] = substr($userData['firstname'], 0, strpos($userData['firstname'], ' '));
            }
            if (empty($userData['city']) or empty($userData['state']) or empty($userData['country'])) {
                $geo = Util::getIpInfo($request->getClientIp());

                foreach ([
                    'city'     => 'city',
                    'state'    => 'region',
                    'country'  => 'country.code',
                    'postcode' => 'zip'
                ] as $map => $key) {
                    if (empty($userData[$map]) and Arrays::get($geo, $key)) {
                        $userData[$map] = Arrays::get($geo, $key);
                    }
                }
            }

            foreach ([
                'lastname' => '-',
                'phonenumber' => '(-)',
                'address1' => '(no address)',
                'postcode' => '00000'
            ] as $field => $default) {
                if (!$userData[$field]) {
                    $userData[$field] = $default;
                }
            }

            $response = $this->app['integration.whmcs.api']->api('addclient', $userData);

            if (empty($response['result']) or 'success' != $response['result']) {
                throw new ErrorException('Error: ' .
                    (!empty($response['message']) ? $response['message'] : json_encode($response)));
            }

            $userId = $response['clientid'];

            if (!$userId) {
                throw new ErrorException('Error: ' . json_encode($response));
            }

            // Add user to db
            $user = $this->getApi()->userManagement()->upsertUser('whmcs', $userId, $userData['email']);
            if (empty($user['userId'])) {
                throw new ErrorException('Unable to create user account');
            }
            $this->getUser()->authorizeById($user['userId']);
            if ($this->getUser()->getTFA()) {
                $this->getUser()->getTFA()->setTFAValidated($user[ 'userId' ]);
            }

            // Migrate customer if needed
            try {
                $packageExists = !!$this->getApi()->packages()->getByAttributes(
                    PackageEntity::construct()
                        ->setCountry($request->get('plan')[ 'country' ][ 0 ])
                        ->setCategory($request->get('plan')[ 'category' ][ 0 ])
                );
            }
            catch (BadRequestException $e) {
                $packageExists = false;
            }
            if (!$packageExists) {
                $returnUrl = $this->getUrl('checkout_process',
                    ['plan' => $request->get('plan'), 'new' => 1, 'details' => $request->get('details')]);
            }
            else {
                $returnUrl = $this->getUrl('checkout',
                    [
                        'country' => $request->get('plan')['country'][0],
                        'category' => $request->get('plan')['category'][0],
                        'quantity' => $request->get('plan')['amount'][0]
                    ]);
            }

            // User created at this point
            return $this->app['integration.whmcs.plugin.auth']->authorize($userData['email'], $userData['password2'],
                $returnUrl, null, 'Processing, please wait...');
        }
        catch (ErrorException $e) {
            $this->addFlashError($e->getMessage());

            return $this->app['twig']->render('checkout/quick-buy.twig', [
                'data'         => array_merge(array_filter($request->request->all()), [
                    'plan'    => [
                        'country'  => Arrays::get($request->request->all(), 'plan.country.0'),
                        'category' => Arrays::get($request->request->all(), 'plan.category.0'),
                        'amount'   => Arrays::get($request->request->all(), 'plan.amount.0'),
                    ],
                    'details' => [
                        'promocode' => Arrays::get($request->request->all(), 'details.promocode')
                    ]
                ]),
                'available'    => array_map(function(array $product) {
                    return [
                        'country'  => $product[ 'meta' ][ 'country' ],
                        'category' => $product[ 'meta' ][ 'category' ],
                        'amount'   => $this->getCountBounds($product[ 'meta' ][ 'country' ], $product[ 'meta' ][ 'category' ])
                    ];
                }, $this->getPricingTiers()),
                'countries' => Util::getCountriesList()
            ]);
        }
    }

    public function doCheckout(Application $app, Request $request) {
        $step = $request->get('step', 'start');

        if ('start' == $step) {
            // Prepare order data
            $planUnformed = $request->get('plan');
            $details = $this->reformPlanDetails($planUnformed['country'], $planUnformed['category'], $planUnformed['amount']);
            $additional = $request->get('details');

            // Restrict customer from upgrading package from another source
            if (!empty($details[0])) {
                try {
                    $package = $this->getApi()->packages()->getByAttributes(
                        PackageEntity::construct()
                            ->setCountry($planUnformed[ 'country' ][ 0 ])
                            ->setCategory($planUnformed[ 'category' ][ 0 ])
                    );
                    if ('whmcs' != $package['item']['source']) {
                        return $this
                            ->addFlashError("Upgrading/calcelling package for source \"{$package['item']['source']}\" is prohibited")
                            ->redirectToRoute('checkout');
                    }
                }
                catch (BadRequestException $e) {
                    // Listen only if package not found (new)
                    if ('NOT_FOUND' != $e->getErrorCode()) {
                        throw $e;
                    }
                }
            }

            // Validate pricing tiers

            if ($details[0]['amount'] > 0) {
                $countBounds = $this->getCountBounds($details[ 0 ][ 'country' ], $details[ 0 ][ 'category' ]);
                // Min quantity
                if ($details[ 0 ][ 'amount' ] < $countBounds[ 'min' ]) {
                    return $this
                        ->addFlashError('Minimal amount is: ' . $countBounds['min'])
                        ->redirectToRoute('checkout');
                }
                if ($details[ 0 ][ 'amount' ] > $countBounds[ 'max' ]) {
                    return $this
                        ->addFlashError('Max amount is: ' . $countBounds['max'])
                        ->redirectToRoute('checkout');
                }
                // No price found
                if (false === $this->getPrice($details[ 0 ][ 'country' ], $details[ 0 ][ 'category' ],
                        $details[ 0 ][ 'amount' ])
                ) {
                    return $this
                        ->addFlashError('Purchase of this package is prohibited')
                        ->redirectToRoute('checkout');
                }
            }

            // For existent order no need to get affiliate
            if (!$request->get('new')) {
                $step = 'complete';
            }
            else {
                $this->app['session']->set('checkout.details', $details);
                $this->app['session']->set('checkout.additional', $additional);

                $response = $app[ 'integration.whmcs.plugin' ]->getAffiliateIdCallback(
                    $this->getUrl('checkout_process', ['step' => 'affiliate']));

                if ($response instanceof Response) {
                    return $response;
                }
                elseif ($response instanceof Request) {
                    return $this->app->handle($response, HttpKernel::SUB_REQUEST);
                }
            }
        }

        elseif ('affiliate' == $step) {
            if ($request->get('error') and $request->get('alert')) {
                return $this->addFlashError($request->get('message'))->redirectToRoute('checkout');
            }
            elseif($request->get('error')) {
                throw new ErrorException('Error occured, information - ' . $request->get('message'));
            }

            $details = $this->app[ 'session' ]->get('checkout.details');
            $additional = $this->app[ 'session' ]->get('checkout.additional');
            $this->app[ 'session' ]->remove('checkout.details');
            $this->app[ 'session' ]->remove('checkout.additional');
            $affiliateId = (int) $request->get('id');
            $step = 'complete';
        }

        if ('complete' == $step and !empty($details)) {
            if ($details[0]['amount']) {
                try {
                    $orderInformation = $app[ 'integration.whmcs.plugin' ]->updatePackage(
                        $this->getUser()->getDetails('whmcsId'),
                        $details[ 0 ][ 'productId' ],
                        $details[ 0 ][ 'amount' ],
                        $this->getUrl('dashboard', ['paid' => 1]),
                        $this->getUrl('dashboard', []),
                        $this->getUrl('callback_whmcs', ['userWhmcsId' => $this->getUser()->getDetails('whmcsId')]),
                        !empty($affiliateId) ? $affiliateId : null,
                        !empty($additional[ 'promocode' ]) ? $additional[ 'promocode' ] : null
                    );
                }
                catch (InvalidArgumentException $e) {
                    return $this->addFlashError($e->getMessage())->redirectToRoute('checkout');
                }

                if (!empty($orderInformation['invoiceId'])) {
                    return new RedirectResponse($orderInformation['invoiceUrl']);
                }
                elseif (!empty($orderInformation['noInvoice'])) {
                    return $this->redirectToRoute('checkout');
                }
                else {
                    throw new ErrorException('Error occured, information - ' . json_encode($orderInformation));
                }
            }
            else {
                $orderInformation = $app[ 'integration.whmcs.plugin' ]->cancelPackage(
                    $this->getUser()->getDetails('whmcsId'),
                    $details[ 0 ][ 'productId' ]
                );

                if (empty($orderInformation['cancelled'])) {
                    throw new ErrorException('Error occured, information - ' . json_encode($orderInformation));
                }

                return $this->redirectToRoute('checkout');
            }
        }

        throw new ErrorException('A wrong workflow', 400);
    }

    public function callbackWhmcs(Request $request)
    {
        $userId = $request->get('userId');
        $userWhmcsId = $request->get('whmcsUserId') or $userWhmcsId = $request->get('userWhmcsId');

        if (!$userId and !$userWhmcsId) {
            return new JsonResponse([
                'status' => 'error',
                'text' => 'No user id passed'
            ], Response::HTTP_BAD_REQUEST);
        }

        if (!$userId and $userWhmcsId) {
            try {
                $userId = $this->getApi()->userManagement()->getUserByBillingId('whmcs', $userWhmcsId)['userId'];
            }
            catch (BadRequestException $e) {}
        }

        if ($userId) {
            $this->log->addSharedIndex('userId', $userId);
            $user = $this->getApi()->user()->getDetails($userId);
        }

        if (empty($user)) {
            return new JsonResponse([
                'status' => 'error',
                'text' => 'No valid user found'
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $this->app[ 'db_helper' ]->refreshUserPlans($user[ 'userId' ]);
            $synced = $this->app[ 'db_helper' ]->getLastRefreshDetails();
        }
        catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'text' => $e->getMessage()
            ], 500);
        }

        foreach (['updated', 'added', 'deleted'] as $key) {
            if (!empty($synced[$key]['count'])) {
                foreach ($synced[$key]['sets'] as $i => $set) {
                    $synced[$key]['sets'][$i]['syncLog'][] =
                        $this->app['integration.proxy.api']->portsSync($user['userId'], $set['country'], $set['category']);
                }
            }
        }

        return new JsonResponse([
            'status' => 'success',
            'details' => $synced
        ]);
    }

    public function total(Request $request) {
        $total = 0;
        $discount = 0;

        $planUnformed = $request->get('plan');
        $plan = $this->reformPlan($planUnformed['country'], $planUnformed['category'], $planUnformed['amount']);

        foreach($plan as $country => $categories) {
            foreach($categories as $category => $amount) {
                if ('static' == $category) {
                    $category = 'dedicated';
                }

                try {
                    $data = $this->app[ 'integration.whmcs.plugin' ]->calculateTotal(
                        $this->getDetails($country, $category, $amount)['productId'],
                        $amount,
                        $this->getUser()->getDetails('whmcsId'),
                        Arr::getOrElse($request->get('details', []), 'promocode')
                    );
                    $total = $data['total'];
                    $discount = $data['discount'];

                    if (!empty($data['isPromoValid'])) {
                        $promoValid = true;
                    }
                }
                catch (ErrorException $e) {}
                catch (RuntimeException $e) {}
            }
        }

        return new JsonResponse(['total' => $total, 'discount' => $discount, 'promoValid' => !empty($promoValid)]);
    }

    public function validatePromocode(Request $request)
    {
        try {
            $code = Arr::getOrElse($request->get('details', []), 'promocode');

            if (!$code) {
                throw new ErrorException('No code is passed');
            }

            $planUnformed = $request->get('plan');
            $plan = $this->reformPlan($planUnformed['country'], $planUnformed['category'], $planUnformed['amount']);

            $isValid = false;
            foreach($plan as $country => $categories) {
                foreach($categories as $category => $amount) {
                    if ('static' == $category) {
                        $category = 'dedicated';
                    }

                    $data = $this->app[ 'integration.whmcs.plugin' ]->calculateTotal(
                        $this->getDetails($country, $category, $amount)['productId'],
                        $amount,
                        $this->getUser()->getDetails('whmcsId'),
                        $code
                    );

                    if (!empty($data['isPromoValid'])) {
                        $isValid = true;
                    }
                }
            }

            if ($isValid) {
                return new JsonResponse(['result' => 'success', 'promotion' => []]);
            }
            else {
                return new JsonResponse(['result' => 'fail', 'reason' => '']);
            }
        }
        catch (ErrorException $e) {
            return new JsonResponse(['result' => 'fail', 'reason' => $e->getMessage()]);
        }
    }

    private function reformPlan($countries, $categories, $amounts) {
        $plan = [];
        if (count($countries) != count($categories) || count($countries) != count($amounts)) {
            throw new \Exception('Plan Counts Don\'t Match');
        }
        foreach ($countries as $idx => $country) {
            $country = strtolower($country);
            $category = strtolower($categories[$idx]);
            $amount = (int)$amounts[$idx];
            if ($country && $category /*&& $amount*/) {
                $plan[$country][$category] = (isset($plan[$country][$category])) ? $plan[$country][$category] + $amount : $amount;
            }
        }
        return $plan;
    }

    private function getPrice($country, $category, $amount) {

        foreach ($this->getPricingTiers() as $product) {
            if ($country == $product['meta']['country'] and $category == $product['meta']['category']) {
                foreach ($product['tiers'] as $tier) {
                    if ($tier['from'] <= $amount and $amount <= $tier['to']) {
                        return $tier['price'] * $amount;
                    }
                }
            }
        }

        return false;
    }

    private function getCountBounds($country, $category)
    {
        $min = 0;
        $max = 0;

        foreach ($this->getPricingTiers() as $product) {
            if ($country == $product['meta']['country'] and $category == $product['meta']['category']) {
                foreach ($product['tiers'] as $tier) {
                    if (!$min) {
                        $min = $tier['from'];
                    }

                    if ($tier['from'] < $min) {
                        $min = $tier['from'];
                    }

                    if ($tier['to'] > $max) {
                        $max = $tier['to'];
                    }
                }
            }
        }

        return [
            'min' => max($min, 1),
            'max' => $max,
            'step' => 1
        ];
    }

    private function getPricingTiers()
    {
        $response = $this->app['integration.whmcs.plugin']->getPricingTiers();

        if (empty($response['pricing'])) {
            throw new ErrorException('No pricing tiers received, response - ' . json_encode($response));
        }

        return $response['pricing'];
    }

    private function reformPlanDetails($countries, $categories, $amounts) {
        $reformed = [];
        $plan = $this->reformPlan($countries, $categories, $amounts);
        foreach($plan as $country => $categories) {
            foreach($categories as $category => $amount) {
                $reformed[] = $this->getDetails($country, $category, $amount);
            }
        }
        return $reformed;
    }

    private function getDetails($country, $category, $amount) {
        if ('static' == $category) {
            $category = 'dedicated';
        }

        $response = $this->app['integration.whmcs.plugin']->getPricingTiers();

        if (empty($response['pricing'])) {
            throw new ErrorException('No pricing tiers received, response - ' . json_encode($response));
        }

        $pricingTiers = $response['pricing'];

        foreach ($pricingTiers as $product) {
            if ($country == $product['meta']['country'] and $category == $product['meta']['category']) {
                return [
                    'productId' => $product['productId'],
                    'country' => $product['meta']['country'],
                    'category' => $product['meta']['category'],
                    'amount' => $amount
                ];
            }
        }

        throw new ErrorException('Not found category or country!');
    }
}