<?php

namespace WHMCS\Module\Blazing\Export\GetResponse;

use WHMCS\Module\Blazing\Export\Compiler\ArrayCompileField;
use WHMCS\Module\Blazing\Export\Compiler\CallbackCompileField;
use WHMCS\Module\Blazing\Export\Compiler\Compiler;
use WHMCS\Billing\Invoice;
use WHMCS\Module\Blazing\Export\Compiler\CustomCallbackCompileField;
use WHMCS\Module\Blazing\Export\GetResponse\Field\FullServiceListField;
use WHMCS\Module\Blazing\Export\GetResponse\Service\Type;
use WHMCS\Module\Blazing\Export\Vendor\WHMCS\Module\Framework\Helper;

/**
 * Class CompileBuilder
 * Builds fields required for GetResponse service.
 *
 * todo Smells, must be split into classes
 *
 * @package WHMCS\Module\Blazing\Export\GetResponse
 */
class CompilerBuilder
{

    /**
     * @var array
     */
    private $fieldsIdMap;

    /**
     * @var array
     */
    private $tagsIdMap;

    public function __construct(array $fieldsMap = [], array $tagsMap = [])
    {
        $this->fieldsIdMap = $fieldsMap;
        $this->tagsIdMap = $tagsMap;
    }

    public function build()
    {

        return new Compiler(
            [
                new CallbackCompileField(
                    'ip',
                    function (ClientContext $context) {
                        return $context->getClientModel()->lastLoginIp;
                    }
                ),
                new CallbackCompileField(
                    'name',
                    function (ClientContext $context) {
                        return $context->getClientModel()->firstName . ' '
                            . $context->getClientModel()->lastName;
                    }
                ),
                new CallbackCompileField(
                    'email',
                    function (ClientContext $context) {
                        return $context->getClientModel()->email;
                    }
                ),
                new CallbackCompileField(
                    'campaign',
                    function (ClientContext $context) {
                        return [
                            'campaignId' => $context->getConfig('campaignId')
                        ];
                    }
                ),
                new CallbackCompileField(
                    'tags',
                    function (ClientContext $context) {
                        return [
                            [
                                'tagId' => $this->tId('start_month1')
                            ]
                        ];
                    }
                ),
                new ArrayCompileField(
                    'customFieldValues', [
                        new FullServiceListField(
                            $this->rId(FullServiceListField::NAME)
                        ),
                        new CustomCallbackCompileField(
                            $this->rId('total_payments'),
                            function (ClientContext $context) {
                                $client = $context->getClientModel();
                                $conn = Helper::conn();
                                $tbl = $conn->table('tblaccounts');
                                $sum = $tbl->selectRaw(
                                    'sum(amountIn - amountOut - fees) as sum_total'
                                )
                                    ->where('userid', $client->id)
                                    ->value('sum_total');
                                return $sum ? $sum : 0;
                            }
                        ),
                        new CustomCallbackCompileField(
                            $this->rId('total_recurring'),
                            function (ClientContext $context) {
                                $services = $context->getClientModel()
                                    ->services
                                    ->whereLoose('domainStatus', 'Active');
                                $total = $services
                                    ->sum('amount');
                                return $total ? $total : 0;
                            }
                        ),
                        new CustomCallbackCompileField(
                            $this->rId('all_state'),
                            function (ClientContext $context) {
                                $services = $context->getClientModel()
                                    ->services()
                                    ->where('domainstatus', 'Active')
                                    ->count();

                                return $services ? 'active' : 'terminated';
                            }
                        ),
                        new CustomCallbackCompileField(
                            $this->rId('dedicated_state'),
                            function (ClientContext $context) {
                                $services = $context->getClientModel()
                                    ->services()
                                    ->with('product.productGroup')
                                    ->where('domainstatus', 'Active')
                                    ->get()
                                    ->filter(
                                        function ($service) {
                                            return (new Type())->isDedicated(
                                                $service->product->productGroup->name
                                                . ' ' . $service->product->name
                                            );
                                        }
                                    )
                                    ->count();

                                return $services ? 'active' : 'terminated';
                            }
                        ),
                        new CustomCallbackCompileField(
                            $this->rId('proxy_state'),
                            function (ClientContext $context) {
                                $services = $context->getClientModel()
                                    ->services()
                                    ->with('product.productGroup')
                                    ->where('domainstatus', 'Active')
                                    ->get()
                                    ->filter(
                                        function ($service) {
                                            return (new Type())->isProxy(
                                                $service->product->productGroup->name
                                                . ' ' . $service->product->name
                                            );
                                        }
                                    )
                                    ->count();

                                return $services ? 'active' : 'terminated';
                            }
                        ),
                        new CustomCallbackCompileField(
                            $this->rId('vps_state'),
                            function (ClientContext $context) {
                                $services = $context->getClientModel()
                                    ->services()
                                    ->with('product.productGroup')
                                    ->where('domainstatus', 'Active')
                                    ->get()
                                    ->filter(
                                        function ($service) {
                                            return (new Type())->isVps(
                                                $service->product->productGroup->name
                                                . ' ' . $service->product->name
                                            );
                                        }
                                    )
                                    ->count();

                                return $services ? 'active' : 'terminated';
                            }
                        ),
                    ]
                ),
            ]
        );
    }

    /**
     * Resolve custom field id.
     *
     * @param $name
     *
     * @return mixed
     */
    public function rId($name)
    {
        if (isset($this->fieldsIdMap[$name])) {
            return $this->fieldsIdMap[$name];
        }
        throw new \RuntimeException(
            "Field \"$name\" was not imported. Please make sure that it exists in your account."
        );
    }

    /**
     * Resolve tag id.
     *
     * @param $name
     *
     * @return mixed
     */
    public function tId($name)
    {
        if (isset($this->tagsIdMap[$name])) {
            return $this->tagsIdMap[$name];
        }
        throw new \RuntimeException(
            'Tag id was not imported. Please make sure that it exists in your account.'
        );
    }
}
