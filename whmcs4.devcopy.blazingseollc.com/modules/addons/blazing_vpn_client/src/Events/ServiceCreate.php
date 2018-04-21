<?php

namespace Blazing\Vpn\Client\Events;

use Blazing\Vpn\Client\Container;
use Blazing\Vpn\Client\Vendor\ApiRequestHandler\Exception\BadRequestException;
use Blazing\Vpn\Client\Vendor\WHMCS\Module\Framework\Api\APIFactory;

class ServiceCreate extends AbstractFunction
{
    protected $name = 'CreateAccount';

    /**
     * @param array
     * @return mixed
     */
    protected function execute(array $args = null)
    {
        $serviceId = $args['serviceid'];
        $userId = $args['userid'];
        $api = Container::getInstance()->getVpnApi();
        $logger = Container::getInstance()->getLogger();

        $logger->addSharedIndex('userId', $userId);
        $logger->info('Service creation', [
            'serviceId' => $serviceId,
            'context' => $args
        ]);

        try {
            $api->getServiceData($serviceId);

            // Oops, service is created
            $logger->warn('Service is already created, do not proceed');

            return 'Service is already created, can not create that again';
        } catch (BadRequestException $e) {
        }

        // No service have been created yet (exception should be caught)
        try {
            $response = $api->createVpnService($userId, $serviceId);
        } catch (BadRequestException $exception) {
            APIFactory::system()->sendEmail($userId,
                null,
                'Support: VPN is out of stock now.',
                'We will have your VPN certificate to you within the next 12 hours. '
                . 'We are unexpectedly ran out of dedicated IPs and are restocking them now. '
                . 'An email will followup with you in 1-2 hours on average, but please allot up to 12'
                . ' for our engineers to route more dedicated IPs to our infrastructure.',
                'general'
            );

            return $exception->getMessage();
        }

        // Save list as note, visible to admin side only
        $list = [];
        foreach ($response['list'] as $location) {
            $list[] = "- {$location['country']}.{$location['region']}.{$location['city']}";
        }
        APIFactory::service()->updateClientProduct($serviceId, ['notes' => join(PHP_EOL, $list)]);

        $logger->debug('VPN service is created');

        return true;
    }
}
