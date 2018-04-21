<?php

namespace WHMCS\Module\Blazing\Proxy\Seller\UserService;

use Symfony\Component\HttpFoundation\Request;
use WHMCS\Module\Blazing\Proxy\Seller\UserService;
use WHMCS\Module\Framework\Addon;
use WHMCS\Module\Framework\Helper;

class UserServiceProductDescriber
{
    protected $userService;

    protected $cache;

    /**
     * UserServiceCallback constructor.
     *
     * @param $userService
     */
    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public static function buildInvoiceUrlForRequest($invoiceId, Request $request = null)
    {
        $systemUrl = Helper::getConfigValue('SystemURL');

        return rtrim($systemUrl, '/') .
            Addon::getInstanceById('blazing_proxy_seller')->getRelativeDirectory() .
            '/redirect.php?' . http_build_query([
                'url' => rtrim($systemUrl, '/') . '/viewinvoice.php?id=' . $invoiceId,
                'track_data' => ['invoiceId' => $invoiceId]
            ]);
    }

    public function getData()
    {
        if (!$this->userService->getServiceId()) {
            throw new \ErrorException('No service id defined to load service data');
        }

        return $this->getOrCache('data', function() {
            return Helper::conn()->selectOne('SELECT * FROM tblhosting WHERE id = ?',
                [$this->userService->getServiceId()]);
        });
    }

    public function getSuspendReason()
    {
        $data = $this->getData();

        return !empty($data['suspendreason']) ? $data['suspendreason'] : 'Overdue On Payment';
    }

    public function getInvoices()
    {
        return $this->getOrCache('invoices', function() {
            return Helper::conn()->select("
                SELECT i.*
                FROM tblinvoiceitems ii
                INNER JOIN tblinvoices i ON i.id = ii.invoiceid
                WHERE ii.relid = ?", [$this->userService->getServiceId()]);
        });
    }

    public function getUnpaidOverdueInvoice()
    {
        $product = $this->getData();
        foreach ($this->getInvoices() as $invoice) {
            if ('Unpaid' == $invoice['status'] and $product['nextduedate'] == $invoice['duedate']) {
                return $invoice;
            }
        }

        return false;
    }

    protected function getOrCache($key, callable $callback) {
        if (!is_string($key)) {
            $key = md5(json_encode($key));
        }

        if (isset($this->cache[$key])) {
            return $this->cache[$key];
        }

        return $this->cache[$key] = $callback();
    }
}