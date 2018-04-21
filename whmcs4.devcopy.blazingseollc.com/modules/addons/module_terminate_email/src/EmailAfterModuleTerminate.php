<?php

namespace WHMCS\Module\Blazing\Notify;

use Illuminate\Support\Arr;
use WHMCS\Module\Framework\Addon;
use WHMCS\Module\Framework\Api\APIFactory;
use WHMCS\Module\Framework\Events\HookListener\Module\AfterModuleTerminate;
use WHMCS\Module\Framework\Helper;
use WHMCS\Module\Framework\ModuleHooks;

class EmailAfterModuleTerminate extends AfterModuleTerminate
{

    protected $name = self::KEY;

    /**
     * @param array
     * @return mixed
     */
    protected function execute(array $args = [])
    {
        $params = $args['params'];
        $templateName = $this->getModule()->getConfig('templateName');
        if (!strlen($templateName)) {
            throw new \LogicException('Emailing failed as template is not specified.');
        }
        logActivity('Mailing about termination service with id: ' . $params['serviceid'], 0);

        $products = APIFactory::client()->getProducts(null, $params['serviceid']);
        $product = isset($products[0]) ? $products[0] : [];

        logActivity(sprintf('Received: service_name - %s , service_groupname - %s , service_domain - %s',
            isset($product['name']) ? $product['name'] : null,
            isset($product['groupname']) ? $product['groupname'] : null,
            isset($product['domain']) ? $product['domain'] : null));

        return APIFactory::system()
            ->sendEmail(
                $params['userid'],
                $templateName,
                null,
                null,
                null,
                [
                    'customvars' => base64_encode(serialize([
                        'service_name' => isset($product['name']) ? $product['name'] : null,
                        'service_groupname' => isset($product['groupname']) ? $product['groupname'] : null,
                        'service_domain' => isset($product['domain']) ? $product['domain'] : null,
                    ]))
                ]
            );
    }

    protected function onExecuteException(\Exception $e)
    {
        return $e->getMessage();
    }
}
