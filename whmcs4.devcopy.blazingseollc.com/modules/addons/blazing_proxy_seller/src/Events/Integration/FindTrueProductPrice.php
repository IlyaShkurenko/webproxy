<?php

namespace WHMCS\Module\Blazing\Proxy\Seller\Events\Integration;

use Axelarge\ArrayTools\Arr;
use WHMCS\Module\Blazing\Proxy\Seller\EmitterEvents\AbstractEvent;
use WHMCS\Module\Blazing\Proxy\Seller\EmitterEvents\BeforeUpgradeEvent;
use WHMCS\Module\Blazing\Proxy\Seller\EmitterEvents\ListenerInterface;
use WHMCS\Module\Blazing\Proxy\Seller\UserService;
use WHMCS\Module\Framework\Api\APIFactory;
use WHMCS\Module\Framework\Events\AbstractHookListener;

class FindTrueProductPrice extends AbstractHookListener implements ListenerInterface
{
    // Get service from what data have been loaded
    protected $name = 'FindPriceForProduct';

    /** @var UserService[] */
    protected $services = [];

    /**
     * @param array
     * @return mixed
     */
    protected function execute(array $args = null)
    {
        $newId = Arr::getOrElse((array) $args, 'newId');
        $oldId = Arr::getOrElse((array) $args, 'id');

        if (!$oldId and !$newId) {
            /** @noinspection PhpInconsistentReturnPointsInspection */
            return;
        }

        // Ok, we have the service id, thus we can find the service
        if ($newId) {
            foreach ($this->services as $userService) {
                if ($newId == $userService->getTempProductId()) {
                    $service = APIFactory::client()->getProducts($userService->getUserId(), $userService->getServiceId())
                        ->validate('0')[0];
                    $total = $service['recurringAmount'];

                    return [ 'monthly' => $total ];
                }
            }
        }

        if ($oldId) {
            foreach ($this->services as $userService) {
                if ($oldId == $userService->getProductId() and in_array($userService->getStatus(),
                        [UserService::STATUS_NEW, UserService::STATUS_ACTIVE,
                            UserService::STATUS_ACTIVE_UPGRADING, UserService::STATUS_ACTIVE_UPGRADED])) {
                    $service = APIFactory::client()->getProducts($userService->getUserId(), $userService->getServiceId())
                        ->validate('0')[0];
                    $total = $service['recurringAmount'];

                    return [ 'monthly' => $total ];
                }
            }
        }

        /** @noinspection PhpInconsistentReturnPointsInspection */
        return;
    }

    /**
     * @return array
     */
    public function getEmitterListenedEvents()
    {
        return [
            BeforeUpgradeEvent::class
        ];
    }

    public function handleEmitterEvent(AbstractEvent $e)
    {
        if (BeforeUpgradeEvent::name() == $e->getName()) {
            /** @var BeforeUpgradeEvent $e */
            $this->services[] = $e->getService();
        }
    }
}
