<?php

namespace WHMCS\Module\Blazing\Proxy\Seller\Product;

use WHMCS\Module\Blazing\Proxy\Seller\Product;
use WHMCS\Module\Framework\Helper;

class ProductNormalizer
{

    /**
     * @var Product
     */
    protected $product;

    protected $customFieldQuantityName = 'ProxyQuantity';
    protected $customFieldQuantityId;

    public function __construct(Product $product)
    {
        $this->product = $product;
    }

    /**
     * Get product
     *
     * @return Product
     */
    public function getProduct()
    {
        return $this->product;
    }

    public function normalize()
    {
        $this
            ->normalizeProductType()
            ->normalizeProductPaymentSettings();

        // If custom field just created (a new product) - set autosetup
        if ($this->normalizeCustomFieldQuantity()) {
            $this->adjustAutoSetup();
        }
    }

    public function isProductTypeCorrect()
    {
        return ('other' == $this->product->getType()) and ('blazing_proxy_seller' == $this->product->getModuleName());
    }

    public function normalizeProductType()
    {
        if (!$this->isProductTypeCorrect()) {
            $queryArgs = [];

            $this->product->setType($queryArgs['type'] = 'other');
            $this->product->setModuleName($queryArgs['module'] = 'blazing_proxy_seller');

            Helper::conn()->update("UPDATE tblproducts SET `type` = ?, servertype = ? WHERE id = ?",
                [$queryArgs['type'], $queryArgs['module'], $this->product->getId()]);
        }

        return $this;
    }

    public function normalizeProductPaymentSettings()
    {
        Helper::conn()->update("UPDATE tblproducts SET paytype = 'recurring' WHERE id = ?", [$this->product->getId()]);
        Helper::conn()->update("
          UPDATE tblpricing SET 
            `msetupfee` = '0.00', `qsetupfee` = '0.00', `ssetupfee` = '0.00', 
            `asetupfee` = '0.00', `bsetupfee` = '0.00', `tsetupfee` = '0.00', 
            `monthly` = '0.00', `quarterly` = '-1.00', `semiannually` = '-1.00', 
            `annually` = '-1.00', `biennially` = '-1.00', `triennially` = '-1.00'
            WHERE relid = ? and `type` = 'product'", [$this->product->getId()]);

        return $this;
    }

    public function isCustomFieldQuantityExists()
    {
        if (!$this->customFieldQuantityId) {
            $customFieldId = Helper::conn()->selectOne('
              SELECT id 
              FROM tblcustomfields 
              WHERE type = "product" AND fieldname = ? AND relid = ?', [$this->customFieldQuantityName, $this->product->getId()]
            );

            $this->customFieldQuantityId = !empty($customFieldId['id']) ? $customFieldId['id'] : false;
        }

        return $this->customFieldQuantityId;
    }

    public function getCustomFieldQuantityName()
    {
        return $this->customFieldQuantityName;
    }

    public function normalizeCustomFieldQuantity()
    {
        if (!$this->isCustomFieldQuantityExists()) {
            Helper::conn()->insert("
              INSERT INTO `tblcustomfields` (
                `type`, `relid`, 
                `fieldname`, `fieldtype`, `description`, `fieldoptions`, 
                `regexpr`, `adminonly`, `required`, `showorder`, `showinvoice`, `sortorder`, 
                `created_at`, `updated_at`
              ) VALUES
              (
                'product', ?, 
                ?, 'text', 'To set amount on an order creation', '', 
                '[0-9]+', 'on', '', '', '', 0, 
                '0000-00-00 00:00:00', '0000-00-00 00:00:00'
              );",
                [$this->product->getId(), $this->customFieldQuantityName]);

            return true;
        }
        else {
            Helper::conn()->update("
                UPDATE `tblcustomfields` SET 
                    `regexpr` = '[0-9]+'
                WHERE relid = ? and `type` = 'product'", [$this->product->getId()]);
        }

        return false;
    }

    public function adjustAutoSetup()
    {
        Helper::conn()->update("UPDATE tblproducts SET `autosetup` = 'payment' WHERE id = ?", [$this->product->getId()]);
    }
}
