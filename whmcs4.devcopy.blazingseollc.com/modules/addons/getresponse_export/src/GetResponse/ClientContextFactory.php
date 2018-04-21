<?php

namespace WHMCS\Module\Blazing\Export\GetResponse;

use WHMCS\Module\Blazing\Export\Vendor\WHMCS\Module\Framework\Api\APIFactory;
use WHMCS\Module\Blazing\Export\Compiler\CompileContext;

class ClientContextFactory
{

    private $moduleConfig;

    public function __construct($moduleConfig)
    {
        $this->moduleConfig = $moduleConfig;
    }

    /**
     * @param null $clientModel
     *
     * @return CompileContext
     */
    public function create($clientModel)
    {
        $clientApi = APIFactory::client();
        $details = iterator_to_array(
            $clientApi->getClientsDetails($clientModel->id)
        );
        return new ClientContext($clientModel, $details, $this->moduleConfig);
    }

    /**
     * @return mixed
     */
    public function getModuleConfig()
    {
        return $this->moduleConfig;
    }
}
