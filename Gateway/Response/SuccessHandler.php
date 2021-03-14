<?php


namespace Sslcommerz\Payment\Gateway\Response;

use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Sslcommerz\Payment\Helper\Data;
use Sslcommerz\Payment\Helper\Apicall;
use Magento\Sales\Model\Order;

/**
 * @author    EonInfosys Team <matinict@gmail.com>
 * @copyright 2021 EonInfosys
 * @link      https://github.com/eoninfosys
 */
class SuccessHandler implements HandlerInterface
{

    protected $helperData;
    protected $apicall;

    public function __construct(
        // Config $gatewayConfig,
      //  OrderRepositoryInterface $orderRepository,
        Data $helperData,
        Apicall $apicall
       // LoggerInterface $logger,
       // Session $session
    )
    {
        //$this->gatewayConfig = $gatewayConfig;
        //$this->orderRepository = $orderRepository;
        $this->helperData = $helperData;
        $this->apicall = $apicall;
       // $this->_logger = $logger;
       // $this->_session = $session;
    }

    /**
     * Handles charge capture response
     *
     * @param array $handlingSubject
     * @param array $response
     * @return void
     */
    public function handle(array $handlingSubject, array $response)
    {
        if (!isset($handlingSubject['payment'])
            || !$handlingSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            throw new \InvalidArgumentException('Payment data object should be provided');
        }

        /** @var PaymentDataObjectInterface $paymentDO */
        $paymentDO = $handlingSubject['payment'];
        /** @var $payment \Magento\Sales\Model\Order\Payment */
        $payment = $paymentDO->getPayment();
        $payment->setTransactionId($response['api_response']->getId());
        $payment->setIsTransactionClosed(false);
    }

    public function responseAction($response)
    {
         //var_dump($response['tran_id']); die();

        if (($response) || (!$response['tran_id'])) {

            $orderId = $this->helperData->getOrderIdByTrId($response['tran_id']);
            //var_dump($orderId); die();
            $order = $this->helperData->getOrderData($orderId);

             //var_dump($this->helperData->sslcommerz_hash_key($response)); die();

            if ($this->helperData->sslcommerz_hash_key($response)) {
                $state = $this->helperData->getOrederStatus($orderId);
                //var_dump($response['status']);die();
                if ($state == 'pending_payment') {
                    //$orderId = $this->_checkoutSession->getLastRealOrderId();
                    if ($response['status'] == 'VALID' || $response['status'] == 'VALIDATED') {
                        //var_dump($response['amount']);
                        $risk_level = $response['risk_level'];
                        $risk_title = $response['risk_title'];
                        $val_id = urlencode($response['val_id']);
                        //Validate by Api use valId  validationserverAPI
                        if ($this->helperData->getConfigData('test')) {
                            $validUrl = "https://sandbox.sslcommerz.com/validator/api/validationserverAPI.php";
                        } else {
                            $validUrl = "https://securepay.sslcommerz.com/validator/api/validationserverAPI.php";
                        }

                        $store_id = urlencode($this->helperData->getConfigData('merchant_id'));
                        $password = urlencode($this->helperData->getConfigData('pass_word_1'));
                        $requested_url = $validUrl . '?val_id=' . $val_id . '&store_id=' . $store_id . '&store_passwd=' . $password;


                        $responseValApi = $this->apicall->orderValidationApi($requested_url);
                        $code = $responseValApi->getStatusCode();
                        $result = json_decode($responseValApi->getBody());

                        //var_dump($result->risk_level);die();

                        if ($code == 200) {

                            if ($risk_level ==0) {

                                $st = 'PROCESSING';
                                $orderState = Order::STATE_PROCESSING;
                                $order->setState($orderState, true, 'Gateway has authorized the payment.')->setStatus($orderState);
                            } else {
                                $st = 'HOLDED';
                                $orderState = Order::STATE_HOLDED;
                                $order->setState($orderState, true, $risk_title)->setStatus($orderState);
                            }

                            //Finally Save Order & Transaction
                            $this->helperData->transactionFinal($response['tran_id'], $st);
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
                $this->helperData->errorAction();
            }
        }

    }


}
