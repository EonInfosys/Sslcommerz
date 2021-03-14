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
class IpnHandler implements HandlerInterface
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

    public function ipnAction($response)
    {


        if ($response) {
            $tran_id = $response['tran_id'];
            $orderId = $this->helperData->getOrderIdByTrId($tran_id);
            //$order = $this->orderRepository->get((int)$orderId);
            $order = $this->helperData->getOrderData($orderId);
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
}
