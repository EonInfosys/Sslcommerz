<?php

namespace Sslcommerz\Payment\Model;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Payment\Model\Method\AbstractMethod;

//use \Magento\Payment\Model\Method\Adapter;
use Sslcommerz\Payment\Model\Config\Source\Order\Status\Paymentreview;
use Magento\Sales\Model\Order;
use Sslcommerz\Payment\Helper\Data;


/**
 * Pay In Store payment method model
 */
class Sslcommerznew extends AbstractMethod
{

    protected $orderRepository;
    /**
     * @var bool
     */
    protected $_isInitializeNeeded = true;

    /**
     * Payment code
     *
     * @var string
     */
    protected $_code = 'sslcommerz_pay';

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_isOffline = true;

    /**
     * Payment additional info block
     *
     * @var string
     */
    protected $_formBlockType = 'Sslcommerz\Payment\Block\Form\Sslcommerz';

    /**
     * Sidebar payment info block
     *
     * @var string
     */
    protected $_infoBlockType = 'Magento\Payment\Block\Info\Instructions';

    protected $_gateUrl = "https://securepay.sslcommerz.com/gwprocess/v4/api.php";

    protected $_testUrl = "https://sandbox.sslcommerz.com/gwprocess/v4/api.php";

    protected $_test;

    protected $orderFactory;

    //protected $saveTransaction;
    protected $transactionFactory;
    protected $sslcommerzHelper;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;


    /**
     * Get payment instructions text from config
     *
     * @return string
     */
    public function getInstructions()
    {
        return trim($this->getConfigData('instructions'));
    }

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Payment\Model\Method\Logger $logger,
        \Magento\Framework\Module\ModuleListInterface $moduleList,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Framework\DB\TransactionFactory $transactionFactory,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        Data $sslcommerzHelper,
        array $data = [])
    {
        $this->orderFactory = $orderFactory;
        $this->orderRepository = $orderRepository;
        $this->storeManager = $storeManager;
        $this->_request = $request;
        // $this->saveTransaction = $transactionFactory->create();
        $this->transactionFactory = $transactionFactory;
        $this->_checkoutSession = $checkoutSession;
        $this->sslcommerzHelper = $sslcommerzHelper;
        parent::__construct($context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $resource,
            $resourceCollection,
            $data);
    }


    //@param \Magento\Framework\Object|\Magento\Payment\Model\InfoInterface $payment
    public function getAmount($orderId)//\Magento\Framework\Object $payment)
    {
        //\Magento\Sales\Model\OrderFactory
        $orderFactory = $this->orderFactory;
        /** @var \Magento\Sales\Model\Order $order */
        // $order = $payment->getOrder();
        // $order->getIncrementId();
        /* @var $order \Magento\Sales\Model\Order */

        $order = $orderFactory->create()->loadByIncrementId($orderId);
        //$payment= $order->getPayment();
        // return $payment->getAmount();
        return $order->getGrandTotal();
    }

    protected function getOrder($orderId)
    {
        $orderFactory = $this->orderFactory;
        return $orderFactory->create()->loadByIncrementId($orderId);

    }

    /**
     * Set order state and status
     *
     * @param string $paymentAction
     * @param \Magento\Framework\Object $stateObject
     * @return void
     */
    public function initialize($paymentAction, $stateObject)
    {
        $state = $this->getConfigData('order_status');
        $this->_gateUrl = $this->getConfigData('cgi_url');
        $this->_testUrl = $this->getConfigData('cgi_url_test_mode');
        $this->_test = $this->getConfigData('test');
        $stateObject->setState($state);
        $stateObject->setStatus($state);
        $stateObject->setIsNotified(false);
    }

    /**
     * Check whether payment method can be used
     *
     * @param CartInterface|null $quote
     * @return bool
     */
    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        if ($quote === null) {
            return false;
        }
        return parent::isAvailable($quote) && $this->isCarrierAllowed(
                $quote->getShippingAddress()->getShippingMethod()
            );
    }

    public function getGateUrl()
    {
        if ($this->getConfigData('test')) {
            return $this->_testUrl;
        } else {
            return $this->_gateUrl;
        }
    }

    /**
     * Check whether payment method can be used with selected shipping method
     *
     * @param string $shippingMethod
     * @return bool
     */
    protected function isCarrierAllowed($shippingMethod)
    {
        if (empty($shippingMethod)) {
            $shippingMethod = "No";
        }
        // return strpos($this->getConfigData('allowed_carrier'), $shippingMethod) !== false;
        return strpos($this->getConfigData('allowed_carrier'), $shippingMethod) !== true;
    }


    public function generateHash($login, $sum, $pass, $id = null)
    {

        $hashData = array(
            "MrchLogin" => $login,
            "OutSum" => $sum,
            "InvId" => $id,
            "currency" => "BDT",
            "pass" => $pass,
        );

        $hash = strtoupper(md5(implode(":", $hashData)));
        return $hash;
    }

    public function sslcommerz_hash_key($store_passwd = "", $post_data = array())
    {
        if (isset($post_data) && isset($post_data['verify_sign']) && isset($post_data['verify_key'])) {
            # NEW ARRAY DECLARED TO TAKE VALUE OF ALL POST
            $pre_define_key = explode(',', $post_data['verify_key']);

            $new_data = array();
            if (!empty($pre_define_key)) {
                foreach ($pre_define_key as $value) {
                    // if (isset($post_data[$value])) {
                    $new_data[$value] = ($post_data[$value]);
                    // }
                }
            }
            # ADD MD5 OF STORE PASSWORD
            $new_data['store_passwd'] = md5($store_passwd);

            # SORT THE KEY AS BEFORE
            ksort($new_data);

            $hash_string = "";
            foreach ($new_data as $key => $value) {
                $hash_string .= $key . '=' . ($value) . '&';
            }
            $hash_string = rtrim($hash_string, '&');

            if (md5($hash_string) == $post_data['verify_sign']) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public function getSslOrederStatus($orderId)
    {
        $order = $this->orderRepository->get((int)$orderId);
        return $order->getStatus();
    }





    public function getPostDataGuzzle($orderId){


        $requestData = $this->sslcommerzHelper->initApiCall($orderId);
        //Print_r($requestData); die();


       $client =  new Client
            ([
                'headers' => [
                    'User-Agent' => 'testing/1.0',
                    'Content-Type' => 'application/json' ,
                    'Accept'     => 'application/json',
                    'http_errors' => false,
                    'verify' => false,
                ]
            ]);


        $options = [
            'form_params' => [
                'store_id' => 'testbox',
                'store_passwd' => 'qwerty' ,
                'total_amount' => 100,
                'tran_id' => 100,
                'cus_name' => "Matin",
                'cus_email' => "matin@gmail.com",
                'cus_add1' => "304 Tejgaon",
                'cus_city' => "Tejgaon",
                'cus_country' => "BD",
                'cus_phone' => "01717676441",
                'shipping_method' => "Ship",
                'ship_name' => "Ship",
                'ship_add1' => "Ship",
                'ship_city' => "Ship",
                'ship_postcode' => "1205",
                'ship_country' => "BD",
            ]
        ];
        //var_dump($options);
        $res = $client->post($this->getGateUrl(), $options);

        $body = $res->getBody();
        $arr_body = json_decode($body);
        print_r($arr_body);

//        echo $res->getStatusCode();// 500
//        echo $res->getBody();

        //echo json_decode($res);

        die();
    }

    public function getPostData($orderId)
    {
        $order = $this->orderRepository->get((int)$orderId);
        $storeUrl = $this->storeManager->getStore()->getBaseUrl();
        $tranId = $orderId . '_' . uniqid();

        if ($order->getStatus() == 'pending_payment') {
            /****** Start SSLCommerz Dev Guide PHP ******/
            $post_data = array();
            $post_data['store_id'] = $this->getConfigData('merchant_id');
            $post_data['store_passwd'] = $this->getConfigData('pass_word_1');
            $post_data['total_amount'] = round($order->getGrandTotal(), 2);
            $post_data['currency'] = "BDT"; //$this->getConfigData('currency');
            $post_data['tran_id'] = $tranId;
            $post_data['success_url'] = $storeUrl . 'order/payment/success';
            $post_data['fail_url'] = $storeUrl . 'order/payment/fail';
            $post_data['cancel_url'] = $storeUrl . 'order/payment/cancel';
            $post_data['ipn_url'] = $storeUrl . 'order/payment/ipn';
            # $post_data['multi_card_name'] = "mastercard,visacard,amexcard";
            ## DISABLE TO DISPLAY ALL AVAILABLE
            # EMI INFO
            $post_data['emi_option'] = 0;
            # CUSTOMER INFORMATION
            $post_data['cus_name'] = $order->getCustomerFirstname() . ' ' . $order->getCustomerLastname();
            $post_data['cus_email'] = $order->getCustomerEmail();
            $post_data['cus_add1'] = $order->getShippingAddress()->getStreet()[0];
            //$post_data['cus_add2'] = $order->getShippingAddress()->getStreet();
            $post_data['cus_city'] = $order->getShippingAddress()->getCity();
            $post_data['cus_state'] = $order->getShippingAddress()->getRegion();
            $post_data['cus_postcode'] = $order->getShippingAddress()->getPostcode();
            $post_data['cus_country'] = $order->getShippingAddress()->getCountryId();
            $post_data['cus_phone'] = $order->getShippingAddress()->getTelephone();
            $post_data['cus_fax'] = $order->getShippingAddress()->getTelephone();;
            # SHIPMENT INFORMATION
            $post_data['shipping_method'] = "NO";
            $post_data['ship_name'] = $order->getCustomerFirstname() . ' ' . $order->getCustomerLastname();
            $post_data['ship_add1 '] = $order->getShippingAddress()->getStreet()[0];
            //$post_data['ship_add2'] = $order->getShippingAddress()->getStreet();
            $post_data['ship_city'] = $order->getShippingAddress()->getCity();
            $post_data['ship_state'] = $order->getShippingAddress()->getRegion();
            $post_data['ship_postcode'] = $order->getShippingAddress()->getPostcode();
            $post_data['ship_country'] = $order->getShippingAddress()->getCountryId();
            ## Product Info
            $qntty = count($order->getAllItems());
            foreach ($order->getAllItems() as $item) {
                $name[] = $item->getName();
            }
            $items = implode($name, ',');
            $post_data['shipping_method'] = 'NO';
            $post_data['num_of_item'] = "$qntty";
            $post_data['product_name'] = "$items";
            $post_data['product_category'] = 'Ecommerce';
            $post_data['product_profile'] = 'general';
            # OPTIONAL PARAMETERS
            # CART PARAMETERS
            # REQUEST SEND TO SSLCOMMERZ
            $curl = curl_init($this->getGateUrl());
            curl_setopt($curl, CURLOPT_URL, $this->getGateUrl());
            curl_setopt($curl, CURLOPT_TIMEOUT, 30);
            curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 30);
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE); # KEEP IT FALSE IF YOU RUN FROM LOCAL PC
            $result = curl_exec($curl);

            $response = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            if ($response == 200 && !(curl_errno($curl))) {
                curl_close($curl);
                $sslcommerzResponse = $result;
            } else {
                curl_close($curl);
                echo "FAILED TO CONNECT WITH SSLCOMMERZ API";
                exit;
            }

            # PARSE THE JSON RESPONSE
            $sslcz = json_decode($sslcommerzResponse, true);
            $sessionkey = isset($sslcz['sessionkey']) ? $sslcz['sessionkey'] : "0";
            $st = 'init';
            $this->transactionInit($tranId, $sessionkey, $st);
            if (isset($sslcz['GatewayPageURL']) && $sslcz['GatewayPageURL'] != "") {
                # THERE ARE MANY WAYS TO REDIRECT - Javascript, Meta Tag or Php Header Redirect or Other
                # echo "<script>window.location.href = '". $sslcz['GatewayPageURL'] ."';</script>";
                echo "<meta http-equiv='refresh' content='0;url=" . $sslcz['GatewayPageURL'] . "'>";
                # header("Location: ". $sslcz['GatewayPageURL']);
                exit;
            } else {
                echo "JSON Data parsing error!";
            }
            /***** End SSLCommerz Dev Guide PHP *****/
        } else {
            return "Already Paid or Order not for payment";
        }
    }



    public function responseAction($response)
    {


        if (($response) || (!$response['tran_id'])) {

            $orderId = $this->getOrderIdByTrId($response['tran_id']);
            // var_dump($orderId); die();
            $order = $this->orderRepository->get((int)$orderId);

            if ($this->sslcommerz_hash_key($this->getConfigData('pass_word_1'), $response)) {
                $state = $this->getSslOrederStatus($orderId);

                if ($state == 'pending_payment') {
                    //$orderId = $this->_checkoutSession->getLastRealOrderId();
                    if ($response['status'] == 'VALID' || $response['status'] == 'VALIDATED') {
                        //var_dump($response['amount']);
                        $risk_level = $response['risk_level'];
                        $risk_title = $response['risk_title'];
                        $val_id = urlencode($response['val_id']);
                        //Validate by Api use valId  validationserverAPI
                        //$codeResult = $this->orderValidationApi($val_id);
                        $codeResult = $this->sslcommerzHelper->orderValidationApi($val_id);
                        $code = $codeResult['0'];
                        $handle = $codeResult['1'];
                        $result = json_decode($codeResult['2']);


                        if ($code == 200 && !(curl_errno($handle))) {
                            //var_dump($result->amount);die();
                            if ($risk_level == '0') {
                                $st = 'PROCESSING';
                                $orderState = Order::STATE_PROCESSING;
                                $order->setState($orderState, true, 'Gateway has authorized the payment.')->setStatus($orderState);
                            } else {
                                $st = 'HOLDED';
                                $orderState = Order::STATE_HOLDED;
                                $order->setState($orderState, true, $risk_title)->setStatus($orderState);
                            }

                            //Finally Save Order & Transaction
                            $this->transactionFinal($response['tran_id'], $st);
                            $order->save();
                            return "Payment Successfully  Done!";
                        }
                    } else {
                        echo "Hash Validation Failed!";
                        //$this->errorAction();
                    }
                } else {
                    return "Payment Already Done!";
                }
            } else {

                echo "There is a problem in the response we got";
                $this->errorAction();
            }
        }

    }

    public function getPaymentMethod()
    {
        $orderId = $this->_checkoutSession->getLastRealOrderId();
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $order = $objectManager->create('Magento\Sales\Model\Order')->loadByIncrementId($orderId);
        $payment = $order->getPayment();
        $method = $payment->getMethodInstance();
        $methodTitle = $method->getTitle();

        return $methodTitle;
    }

    public function getConfigPaymentData()
    {
        return $this->getConfigData('title');
    }

    public function getCusMail()
    {
        $orderId = $this->_checkoutSession->getLastRealOrderId();
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $order = $objectManager->create('Magento\Sales\Model\Order')->loadByIncrementId($orderId);

        $PostData['order_id'] = $orderId;
        $PostData['cus_email'] = $order->getCustomerEmail();
        $PostData['url'] = $this->getConfigData('test');
        $PostData['total_amount'] = round($this->getAmount($orderId), 2);
        $PostData['cus_name'] = $order->getCustomerName();
        $PostData['cus_phone'] = $order->getBillingAddress()->getTelephone();
        $PostData['title'] = $this->getConfigData('title');
        $PostData['full_name'] = $order->getBillingAddress()->getFirstname() . " " . $order->getBillingAddress()->getLastname();
        $PostData['country'] = $order->getBillingAddress()->getCountryId();

        // $PostData['company'] = $order->getBillingAddress()->getCompany();
        $PostData['street'] = $order->getBillingAddress()->getStreet();
        $PostData['region'] = $order->getBillingAddress()->getRegionId();
        $PostData['city'] = $order->getBillingAddress()->getCity() . ", " . $order->getBillingAddress()->getPostcode();
        $PostData['telephone'] = $order->getBillingAddress()->getTelephone();

        return $PostData;
    }

    public function errorAction($response)
    {
        if (($response['tran_id'])) {
            $st = $response['status'];
            $orderId = $this->getOrderIdByTrId($response['tran_id']);
            $order = $this->orderRepository->get((int)$orderId);
            $trVal = $this->transactionValidation($response['tran_id']);
            if (($trVal == "FAILED") || ($trVal == "CANCELLED")) {
                $orderState = Order::STATE_CANCELED;
                $order->setState($orderState, true, 'Gateway has declined the payment.')->setStatus($orderState);
                $this->transactionFinal($response['tran_id'], $st);

            }
            $order->save();

            return true;
        } else {
            return "tranID Not Found";
        }
    }


    public function ipnAction($response)
    {


        if ($response) {
            $tran_id = $response['tran_id'];
            $orderId = $this->getOrderIdByTrId($tran_id);
            $order = $this->orderRepository->get((int)$orderId);
            $status = $order->getStatus();

            if ($tran_id != "" && $status == 'pending_payment' && ($response['status'] == 'VALID' || $response['status'] == 'VALIDATED')) {
                $val_id = urlencode($response['val_id']);

                $codeResult = $this->orderValidationApi($val_id);
                $code = $codeResult['0'];
                $handle = $codeResult['1'];
                $result = json_decode($codeResult['2']);

                if ($code == 200 && !(curl_errno($handle))) {
                    # TO CONVERT AS ARRAY
                    # $result = json_decode($result, true);
                    # $status = $result['status'];

                    # TO CONVERT AS OBJECT
                    //$result = json_decode($result);
                    # TRANSACTION INFO
                    $tran_status = $result->status;

                    if ($tran_status == 'VALID' || $tran_status == 'VALIDATED') {
                        $orderState = Order::STATE_PROCESSING;
                        $order->setState($orderState, true, 'Payment Validated by IPN')->setStatus($orderState);
                        $msg = "Payment Validated by IPN";
                    }
                    $order->save();
                }
            } else {
                $msg = "IPN data missing!";
            }
        } else {
            $msg = "No IPN Request Found!";
        }
        return $msg;
    }


    public function transactionInit($tranId, $sessionkey, $st = 'init')
    {
        $typename = 'payment';
        $closed = 0;
        $comment = "payment initiation ";
        $orderId = $this->getOrderIdByTrId($tranId);
        $order = $this->orderRepository->get((int)$orderId);
        $payment = $order->getPayment();
        $payment->setTransactionId($tranId);
        $transaction = $payment->addTransaction($typename, null, false, $comment);
        $transaction->setParentTxnId($orderId);
        $transaction->setPaymentId(1);
        $transaction->setIsClosed($closed);
        $transaction->setSessionkey($sessionkey);
        $transaction->setStatus($st);
        $transaction->save();
    }


    public function transactionFinal($tranId, $st)
    {

       /* $typename = 'payment';
        $closed = 1;
        $comment = "payment Final ";
        $orderId = $this->getOrderIdByTrId($tranId);
        $order = $this->orderRepository->get((int)$orderId);
        $payment = $order->getPayment();
        $payment->setTransactionId($tranId);
        $transaction = $payment->addTransaction($typename, null, false, $comment);
        $transaction->setParentTxnId($orderId);
        $transaction->setIsClosed($closed);
        $transaction->setStatus($st);
        $transaction->save();*/
    }


    public function transactionValidation($tranId)
    {
        if ($this->getConfigData('test')) {
            $validUrl = "https://sandbox.sslcommerz.com/validator/api/merchantTransIDvalidationAPI.php";
        } else {
            $validUrl = "https://securepay.sslcommerz.com/validator/api/merchantTransIDvalidationAPI.php";
        }

        if ($tranId) {
            $store_id = urlencode($this->getConfigData('merchant_id'));
            $password = urlencode($this->getConfigData('pass_word_1'));
            $requested_url = $validUrl . '?tran_id=' . $tranId . '&store_id=' . $store_id . '&store_passwd=' . $password;

            $handle = curl_init();
            curl_setopt($handle, CURLOPT_URL, $requested_url);
            curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
            $result = curl_exec($handle);
            $code = curl_getinfo($handle, CURLINFO_HTTP_CODE);
            $t_status = "";
            if ($code == 200 && !(curl_errno($handle))) {
                # JSON CONVERT T
                $result = json_decode($result, true);
                if (isset($result['APIConnect']) && $result['APIConnect'] == 'DONE') {
                    if (isset($result['element'])) {
                        foreach ($result['element'] as $t) {
                            //var_dump($t); //die();
                            $tran_date = $t['tran_date'];
                            $tran_id = $t['tran_id'];
                            $amount = $t['amount'];
                            $bank_gw = $t['bank_gw'];
                            $card_type = $t['card_type'];
                            $card_no = $t['card_no'];
                            $card_issuer = $t['card_issuer'];
                            $card_brand = $t['card_brand'];
                            $card_issuer_country = $t['card_issuer_country'];
                            $card_issuer_country_code = $t['card_issuer_country_code'];
                            $status = $t['status'];
                            $error = $t['error'];
                            $risk_title = $t['risk_title'];
                            $risk_level = $t['risk_level'];

                            # TAKE LATEST STATUS
                            $t_status = $status;

                            if ($status == 'VALID') {    # CHECK CONDITIONS
                                $t_status = "Successful Transaction, Please check AMOUNT with your System. ";
                                if ($risk_level == '1') {
                                    $t_status = "Payment is Risky";
                                }
                                break;

                            } else if ($status == 'VALIDATED') {
                                $t_status = "Successful Transaction already validated by you, Please check AMOUNT with your System. ";
                                if ($risk_level == '1') {
                                    $t_status = "Payment is Risky";
                                }
                                break;
                            }
                        }
                    } else {    # NO SUCCESSFUL RECORD
                        $t_status = "No Record Found";
                    }
                } else {    # INVALID STORE ID AND PASSWORD
                    $t_status = "API Connection Failed";
                }
            } else {    # UNABLE TO CONNECT WITH SSLCOMMERZ
                $t_status = "Failed to connect with SSLCOMMERZ";
            }
            //echo $t_status; die();
            return $t_status;
        }

    }

    public function orderValidationApi($val_id)
    {
        if ($this->getConfigData('test')) {
            $validUrl = "https://sandbox.sslcommerz.com/validator/api/validationserverAPI.php";
        } else {
            $validUrl = "https://securepay.sslcommerz.com/validator/api/validationserverAPI.php";
        }

        $store_id = urlencode($this->getConfigData('merchant_id'));
        $password = urlencode($this->getConfigData('pass_word_1'));
        $requested_url = $validUrl . '?val_id=' . $val_id . '&store_id=' . $store_id . '&store_passwd=' . $password;

        $handle = curl_init();
        curl_setopt($handle, CURLOPT_URL, $requested_url);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($handle);
        $code = curl_getinfo($handle, CURLINFO_HTTP_CODE);
        // return $code;
        return array($code, $handle, $result);


    }

    public function getOrderIdByTrId($transId): string
    {
        $tran_id = explode("_", $transId);
        return $tran_id['0'];

    }
}
