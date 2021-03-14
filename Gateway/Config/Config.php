<?php


namespace Sslcommerz\Payment\Gateway\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Config extends \Magento\Payment\Gateway\Config\Config
{
    const KEY_ACTIVE = 'active';
    const XML_PATH_ENABLE = 'payment/sslcommerz_pay/active';
   /* const KEY_TERMINAL_ID = 'terminal_id';
    const KEY_CLIENT_ID = 'client_id';
    const KEY_CLIENT_SECRET = 'client_secret';
    const KEY_GATEWAY_ORDER_DESCRIPTION = 'gateway_order_description';
    const KEY_TEST_MODE = 'test_mode';

    const KEY_TERMINAL_ID_TEST = 'terminal_id_test';
    const KEY_CLIENT_ID_TEST = 'client_id_test';
    const KEY_CLIENT_SECRET_TEST = 'client_secret_test';

    const KEY_BACK_LINK = 'back_link';
    const KEY_FAILURE_BACK_LINK = 'failure_back_link';

    const KEY_ORDER_SUCCESS_STATUS = 'order_status_success';*/



    protected $scopeConfig;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        $methodCode = null,
        $pathPattern = self::DEFAULT_PATH_PATTERN
    ) {
        parent::__construct($scopeConfig, $methodCode, $pathPattern);
    }

    /** @return bool */

    public function getModuleStatus(): bool
    {
     
        return $this->scopeConfig->getValue(
            self::XML_PATH_ENABLE,
            ScopeInterface::SCOPE_STORE
        );
    }


//    /**
//     * Get Payment configuration status
//     *
//     * @return bool
//     */
//    public function isActive(): bool
//    {
//        return (bool) $this->getValue(self::KEY_ACTIVE);
//    }


//    public function isActive($storeId = null)
//    {
//        // var_dump(self::KEY_ACTIVE);die();
//        return (bool) $this->getValue(self::KEY_ACTIVE, $storeId);
//
//    }

    /*
    public function getTerminalId($storeId = null)
    {
        return $this->getValue(Config::KEY_TERMINAL_ID, $storeId);
    }

    public function getClientId($storeId = null)
    {
        return $this->getValue(Config::KEY_CLIENT_ID, $storeId);
    }

    public function getClientSecret($storeId = null)
    {
        return $this->getValue(Config::KEY_CLIENT_SECRET, $storeId);
    }

    public function getBackLink($storeId = null)
    {
        return $this->getValue(Config::KEY_BACK_LINK, $storeId);
    }

    public function getFailureBackLink($storeId = null)
    {
        return $this->getValue(Config::KEY_FAILURE_BACK_LINK, $storeId);
    }

    public function getGatewayOrderDescription($storeId = null)
    {
        return $this->getValue(Config::KEY_GATEWAY_ORDER_DESCRIPTION, $storeId);
    }

    public function getTestMode($storeId = null)
    {
        return (bool) $this->getValue(Config::KEY_TEST_MODE, $storeId);
    }

    public function getTerminalIdTest($storeId = null)
    {
        return $this->getValue(Config::KEY_TERMINAL_ID_TEST, $storeId);
    }

    public function getClientIdTest($storeId = null)
    {
        return $this->getValue(Config::KEY_CLIENT_ID_TEST, $storeId);
    }

    public function getClientSecretTest($storeId = null)
    {
        return $this->getValue(Config::KEY_CLIENT_SECRET_TEST, $storeId);
    }

    public function getOrderSuccessStatus($storeId = null)
    {
        return $this->getValue(Config::KEY_ORDER_SUCCESS_STATUS, $storeId);
    }*/
}
