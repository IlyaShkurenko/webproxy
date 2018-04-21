<?php

namespace WHMCS\Module\Blazing\Proxy\Seller\Events\Integration;

use Axelarge\ArrayTools\Arr;
use WHMCS\Module\Blazing\Proxy\Seller\EmitterEvents\AbstractEvent;
use WHMCS\Module\Blazing\Proxy\Seller\EmitterEvents\ListenerInterface;
use WHMCS\Module\Blazing\Proxy\Seller\EmitterEvents\TransitionalProductGeneratedEvent;
use WHMCS\Module\Blazing\Proxy\Seller\Logger;
use WHMCS\Module\Blazing\Proxy\Seller\UserService;
use WHMCS\Module\Framework\Events\AbstractHookListener;
use WHMCS\Module\Framework\Helper;

class FindTrueProductId extends AbstractHookListener implements ListenerInterface
{

    // Get service from what data have been loaded
    protected $name = 'FindTraitProductId';

    protected $loadedTransitionalMap = [];

    /**
     * @param array
     * @return mixed
     */
    protected function execute(array $args = null)
    {
        if (!$id = Arr::getOrElse((array) $args, 'id')) {
            /** @noinspection PhpInconsistentReturnPointsInspection */
            return;
        }

        // Predefined map
        if (!empty($this->loadedTransitionalMap[$id])) {
            return ['id' => $this->loadedTransitionalMap[$id]];
        }

        if (!$userService = UserService::findByCustomerServiceId($id)) {
            Logger::debug('FindTrueProductId service not found', ['id' => $id]);
            /** @noinspection PhpInconsistentReturnPointsInspection */
            return;
        }

        Helper::restoreDb();

        return [
            'id' => in_array($userService->getStatus(),
                [UserService::STATUS_NEW, UserService::STATUS_ACTIVE_UPGRADING]) ?
                $userService->getTempProductId() : $userService->getProductId()
        ];
    }

    /**
     * @return array
     */
    public function getEmitterListenedEvents()
    {
        return [
            TransitionalProductGeneratedEvent::class
        ];
    }

    public function handleEmitterEvent(AbstractEvent $e)
    {
        if (TransitionalProductGeneratedEvent::name() == $e->getName()) {
            /** @var TransitionalProductGeneratedEvent $e */
            $this->loadedTransitionalMap[ $e->getGeneratedProductId() ] = $e->getOriginalProductId();
        }
    }
}
