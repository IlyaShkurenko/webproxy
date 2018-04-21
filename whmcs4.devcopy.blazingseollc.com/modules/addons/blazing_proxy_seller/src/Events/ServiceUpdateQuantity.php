<?php

namespace WHMCS\Module\Blazing\Proxy\Seller\Events;

use WHMCS\Module\Blazing\Proxy\Seller\Logger;
use WHMCS\Module\Blazing\Proxy\Seller\Seller;
use WHMCS\Module\Blazing\Proxy\Seller\UserService;
use WHMCS\Module\Framework\Api\APIFactory;
use WHMCS\Module\Framework\Helper;

class ServiceUpdateQuantity extends AbstractModuleListener
{
    protected $name = 'UpdateQuantity';

    /** @noinspection PhpInconsistentReturnPointsInspection
     * @param array $args
     * @return string|null
     */
    protected function execute(array $args = [])
    {
        $serviceId = $args[ 'serviceid' ];
        $userId = $args[ 'userid' ];
        Logger::bindUserId($userId);

        $userService = UserService::findByCustomerServiceId($serviceId);

        if (!$userService or !in_array($userService->getStatus(), [
                UserService::STATUS_ACTIVE,
                UserService::STATUS_ACTIVE_UPGRADING,
                UserService::STATUS_ACTIVE_UPGRADED
            ])) {
            return 'No package or invalid status';
        }

        // Upgrade by quantity
        $product = APIFactory::client()->getProducts(null, $userService->getServiceId())[0];
        $customFields = $product['customFields']['customField'];
        $newQuantity = false;
        if (!empty($customFields)) {
            foreach ($customFields as $field) {
                if ($userService->getProduct()->getCustomFieldQuantityId() == $field['id']) {
                    $newQuantity = $field['value'];
                }
            }
        }

        if (!$newQuantity) {
            return 'No quantity defined';
        }
        elseif ($newQuantity == $userService->getQuantity()) {
            return sprintf('The quantity in should be different than "%s" in the "%s" field',
                $newQuantity, $userService->getProduct()->getNormalizer()->getCustomFieldQuantityName());
        }

        Logger::info('### Manual service upgrade', ['old' => $userService->getQuantity(), 'new' => $newQuantity]);
        (new Seller())->upgradePackage($userService, $newQuantity);

        if ($userService->getUpgradeOrderId()) {
            if (Helper::getWHMCSVersion() > 7) {
                $systemUrl = Helper::apiResponse('GetConfigurationValue', ['setting' => 'SystemURL'], 'result=success')['value'];
            }
            else {
                $systemUrl = Helper::conn()->selectOne("SELECT value FROM tblconfiguration WHERE setting = 'SystemURL'")['value'];
            }

            echo 'redirect|' . rtrim($systemUrl, '/') . '/admin/orders.php?action=view&id=' . $userService->getUpgradeOrderId();
            exit;
        }
    }
}
