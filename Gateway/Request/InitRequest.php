<?php

namespace Sslcommerz\Payment\Gateway\Request;

//use Sslcommerz\Payment\Gateway\Config\Config;
//use Sslcommerz\Payment\Gateway\Http\Client\ApiRequestor;
use Sslcommerz\Payment\Helper\Data;
use Sslcommerz\Payment\Helper\Apicall;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Checkout\Model\Session;
use Magento\Payment\Gateway\Data\Order\OrderAdapter;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Sales\Model\Order;
use Psr\Log\LoggerInterface;

class InitRequest implements BuilderInterface
{
    //private $gatewayConfig;
    protected $orderRepository;
    private $_logger;
    private $_session;
    protected $helperData;
    protected $apicall;

    /**
     * @param OrderRepositoryInterface $orderRepository
     * @param Data $helperData
     * @param Apicall $apicall
     * @param LoggerInterface $logger
     * @param Session $session
     */
    public function __construct(
        // Config $gatewayConfig,
        OrderRepositoryInterface $orderRepository,
        Data $helperData,
        Apicall $apicall,
        LoggerInterface $logger,
        Session $session
    )
    {
        //$this->gatewayConfig = $gatewayConfig;
        $this->orderRepository = $orderRepository;
        $this->helperData = $helperData;
        $this->apicall = $apicall;
        $this->_logger = $logger;
        $this->_session = $session;
    }

    public function getPostData($orderId)
    {
       //var_dump($orderId);die();
        $tranId = $orderId . '_' . uniqid();
        //Api Call With url & OrderData
        $response = $this->apicall->createSessionApiCall($this->helperData->getGateUrl(), $this->processOrderData($orderId, $tranId));
        $result = json_decode($response->getBody());

        //Check Response Data
        if ($response->getStatusCode() == 200) {
            $sslcz = $result;
        } else {
            echo "FAILED TO CONNECT WITH SSLCOMMERZ API";
            exit;
        }
        # PARSE THE JSON RESPONSE
        $sessionkey = isset($sslcz->sessionkey) ? $sslcz->sessionkey : "0";
        $st = 'init';
        //$tranId=$orderId . '_' . uniqid();
        $this->helperData->transactionInit($tranId, $sessionkey, $st);
        if (isset($sslcz->GatewayPageURL) && $sslcz->GatewayPageURL != "") {
            # THERE ARE MANY WAYS TO REDIRECT - Javascript, Meta Tag or Php Header Redirect or Other
            # echo "<script>window.location.href = '". $sslcz->GatewayPageURL ."';</script>";
            echo "<meta http-equiv='refresh' content='0;url=" . $sslcz->GatewayPageURL . "'>";
            # header("Location: ". $sslcz->GatewayPageURL);
            exit;
        } else {
            echo "JSON Data parsing error!";
        }
        /***** End SSLCommerz Dev Guide PHP *****/


    }

    public function processOrderData($orderId, $tranId)
    {

        $order = $this->orderRepository->get((int)$orderId);
        $options =
            [
                'form_params' =>
                    [
                        # SSLCommerz
                        'store_id' => $this->helperData->getConfigData('merchant_id'),
                        'store_passwd' => $this->helperData->getConfigData('pass_word_1'),
                        'total_amount' => round($order->getGrandTotal(), 2),
                        'currency' => "BDT", //$this->getConfigData('currency'),
                        'tran_id' => $tranId,
                        'success_url' => $this->helperData->getBaseUrl() . 'order/payment/success',
                        'fail_url' => $this->helperData->getBaseUrl() . 'order/payment/fail',
                        'cancel_url' => $this->helperData->getBaseUrl() . 'order/payment/cancel',
                        'ipn_url' => $this->helperData->getBaseUrl() . 'order/payment/ipn',
                        'emi_option' => 0,
                        # CUSTOMER INFORMATION
                        'cus_name' => $order->getCustomerFirstname() . ' ' . $order->getCustomerLastname(),
                        'cus_email' => $order->getCustomerEmail(),
                        'cus_add1' => $order->getShippingAddress()->getStreet()[0],
                        'cus_city' => $order->getShippingAddress()->getCity(),
                        'cus_state' => $order->getShippingAddress()->getRegion(),
                        'cus_country' => $order->getShippingAddress()->getCountryId(),
                        'cus_phone' => $order->getShippingAddress()->getTelephone(),
                        'cus_fax' => $order->getShippingAddress()->getTelephone(),
                        # SHIPMENT INFORMATION
                        'shipping_method' => $order->getShippingDescription(),
                        'ship_name' => $order->getCustomerFirstname() . ' ' . $order->getCustomerLastname(),
                        'ship_add1' => $order->getShippingAddress()->getStreet()[0],
                        'ship_city' => $order->getShippingAddress()->getCity(),
                        'ship_state' => $order->getShippingAddress()->getRegion(),
                        'ship_postcode' => $order->getShippingAddress()->getPostcode(),
                        'ship_country' => $order->getShippingAddress()->getCountryId(),
                        ## Product Info
                        'num_of_item' => count($order->getAllItems()),
                        'product_name' => $this->helperData->getProductNames($orderId),
                        'product_category' => 'Ecommerce',
                        'product_profile' => 'general',
                    ]
            ];

        return $options;
    }


    /**
     * Checks the quote for validity
     * @param OrderAdapter $order
     * @return bool;
     */
    private function validateQuote(OrderAdapter $order)
    {
        return true;
    }

    /**
     * Builds ENV request
     * From: https://github.com/magento/magento2/blob/2.1.3/app/code/Magento/Payment/Model/Method/Adapter.php
     * The $buildSubject contains:
     * 'payment' => $this->getInfoInstance()
     * 'paymentAction' => $paymentAction
     * 'stateObject' => $stateObject
     *
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject)
    {
        $payment = $buildSubject['payment'];
        $stateObject = $buildSubject['stateObject'];

        $order = $payment->getOrder();

        // if ($this->validateQuote($order)) {
        if (true) {
            $stateObject->setIsCustomerNotified(false);
            $stateObject->setState(Order::STATE_PENDING_PAYMENT);
            $stateObject->setStatus(Order::STATE_PENDING_PAYMENT);
        } else {
            $stateObject->setIsCustomerNotified(false);
            $stateObject->setState(Order::STATE_CANCELED);
            $stateObject->setStatus(Order::STATE_CANCELED);
        }

        return ['IGNORED' => ['IGNORED']];
    }
}
