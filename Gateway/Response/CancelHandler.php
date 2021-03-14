<?php
/**
 * @category EonInfosys
 * @developer matinict@gmail.com
 * @giturl github.com/eoninfosys
 */

namespace Sslcommerz\Payment\Gateway\Response;

use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Sslcommerz\Payment\Helper\Data;
use Sslcommerz\Payment\Helper\Apicall;
use Magento\Sales\Model\Order;



class CancelHandler implements HandlerInterface
{

    protected $helperData;
    protected $apicall;

    public function __construct(
        Data $helperData,
        Apicall $apicall
    )
    {
        $this->helperData = $helperData;
        $this->apicall = $apicall;
    }

    /**
     * Handles charge cancel response
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

        $payment->setIsTransactionClosed(true);
    }

    public function errorAction($response)
    {
        //var_dump($response['status']);die();

        if (($response['tran_id'])) {
            $st = $response['status'];
            $orderId = $this->helperData->getOrderIdByTrId($response['tran_id']);
            $order = $this->helperData->getOrderData($orderId);

            //var_dump($this->helperData->getGateValidUrl()); die();


            $store_id = urlencode($this->helperData->getConfigData('merchant_id'));
            $password = urlencode($this->helperData->getConfigData('pass_word_1'));
            $requested_url = $this->helperData->getGateValidUrl() . '?tran_id=' . $response['tran_id'] . '&store_id=' . $store_id . '&store_passwd=' . $password;

            $trVal = $this->apicall->transactionValidation($requested_url);

            if (($trVal == "FAILED") || ($trVal == "CANCELLED")) {
                $orderState = Order::STATE_CANCELED;
                $order->setState($orderState, true, 'Gateway has declined the payment.')->setStatus($orderState);
                $this->helperData->transactionFinal($response['tran_id'], $st);

            }
            $order->save();

            return true;
        } else {
            return "tranID Not Found";
        }
    }
}
