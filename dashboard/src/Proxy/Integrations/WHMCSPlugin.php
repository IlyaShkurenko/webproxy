<?php

namespace Proxy\Integrations;

use Proxy\Integration;

class WHMCSPlugin extends Integration
{

    public function updatePackage(
        $userId,
        $whmcsProductId,
        $quantity,
        $successUrl = '',
        $failUrl = '',
        $callbackUrl = '',
        $affiliateId = null,
        $promocode = null
    ) {
        return $this->getRequestHandler()->doRequest('updatePackage', [
            'userId'      => $userId,
            'productId'   => $whmcsProductId,
            'quantity'    => $quantity,
            'url'         => [
                'redirect' => [
                    'success' => $successUrl,
                    'fail'    => $failUrl
                ],
                'callback' => $callbackUrl
            ],
            'affiliateId' => $affiliateId,
            'promocode'   => $promocode
        ]);
    }

    public function cancelPackage($userId, $whmcsProductId)
    {
        return $this->getRequestHandler()->doRequest('cancelPackage', [
            'userId'    => $userId,
            'productId' => $whmcsProductId
        ]);
    }

    public function getPricingTiers($showHidden = false)
    {
        static $response;

        if (!$response) {
            $response = $this->getRequestHandler()->doRequest('getPricingTiers',
                array_merge($showHidden ? ['withUnpublished' => $showHidden] : []));

            if (empty($response[ 'pricing' ])) {
                return $response;
            }

            // Migration for definitions
            foreach ($response[ 'pricing' ] as $i => $product) {
                if ('rotate' == $product[ 'meta' ][ 'category' ]) {
                    $product[ 'meta' ][ 'category' ] = 'rotating';
                }

                $response[ 'pricing' ][ $i ] = $product;
            }
        }

        return $response;
    }

    public function getUserProducts($userId)
    {
        return $this->getRequestHandler()->doRequest('getUserProducts', ['userId' => $userId]);
    }

    public function getAffiliateIdCallback($callbackUrl)
    {
        return $this->getRequestHandler()->doCallbackRequest($callbackUrl, 'getAffiliate');
    }

    public function getUserWhmcsIdCallback($callbackUrl)
    {
        return $this->getRequestHandler()->doCallbackRequest($callbackUrl, 'getUserId');
    }

    public function calculateTotal($productId, $quantity, $userId = null, $promocode = null)
    {
        return $this->getRequestHandler()->doRequest('calculateTotal', [
            'productId' => $productId,
            'quantity'  => $quantity,
            'userId'    => $userId,
            'promocode' => $promocode
        ]);
    }

    /**
     * @return WHMCSPluginRequestHandler
     */
    protected function getRequestHandler()
    {
        return $this->app[ 'integration.whmcs.plugin.request_handler' ];
    }
}