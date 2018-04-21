<?php

namespace WHMCS\Module\Blazing\Export\GetResponse\Field;

use WHMCS\Module\Blazing\Export\GetResponse\ClientContext;
use WHMCS\Module\Blazing\Export\Compiler\CustomCompileField;
use WHMCS\Module\Blazing\Export\GetResponse\Service\Type;

class FullServiceListField extends CustomCompileField
{
    const NAME = 'full_service_list';

    /**
     * FullServiceListField constructor.
     *
     * @param string $id
     */
    public function __construct($id)
    {
        parent::__construct($id);
    }

    /**
     * Retrieve value for custom field. It may be array or scalar
     * which will be wrapped into array.
     *
     * @param ClientContext $context
     *
     * @return mixed
     */
    function doCompile($context)
    {
        $serviceType = new Type();
        $model = $context->getClientModel();
        $serviceList = $model->services()
            ->with('product.productGroup')
            ->get()
            ->map(
                function ($service) use ($serviceType) {
                    $productName = strtolower(
                        $service->product->name
                    );
                    $type = $serviceType->detectServiceType($service->product->productGroup->name . ' ' . $productName);
                    if (null !== $type) {
                        return $type . ',' . strtolower(
                                $service->domainStatus
                            );
                    }
                    return null;
                }
            )
            ->filter(
                function ($v) {
                    return (bool)$v;
                }
            )
            ->all();
        $serviceList = array_unique($serviceList);
        $serviceList = implode(',', $serviceList);
        return $serviceList;
    }
}