<?php

namespace WHMCS\Module\Blazing\Proxy\Seller\EmitterEvents;

class TransitionalProductGeneratedEvent extends AbstractEvent
{

    /**
     * @var
     */
    private $originalProductId;
    /**
     * @var
     */
    private $generatedProductId;

    public function __construct($originalProductId, $generatedProductId)
    {
        $this->originalProductId = $originalProductId;
        $this->generatedProductId = $generatedProductId;
    }

    /**
     * Get originalProductId
     *
     * @return mixed
     */
    public function getOriginalProductId()
    {
        return $this->originalProductId;
    }

    /**
     * Get generatedProductId
     *
     * @return mixed
     */
    public function getGeneratedProductId()
    {
        return $this->generatedProductId;
    }
}
