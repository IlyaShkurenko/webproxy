<?php

namespace ProxyReseller\Controller\ApiV20\Traits;

trait CommonMethodsTrait
{
    protected $billingTypes = ['whmcs', 'amember'];

    protected function validateBillingSource($type)
    {
        /** @noinspection PhpUndefinedMethodInspection */
        $this->assertOrException(in_array($type, $this->billingTypes), 'Billing type is invalid', ['type' => $type]);
    }
}