<?php


namespace Sslcommerz\Payment\Helper;

use \Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Locale\CurrencyInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Sales\Model\Order;


class Data extends AbstractHelper
{

    const XML_PATH_ENABLE = 'payment/sslcommerz_pay/active';
    ##const XML_MERCHANT_ID = 'payment/sslcommerz_pay/merchant_id';
    protected $_test;
    protected $_gateUrl = "https://securepay.sslcommerz.com/gwprocess/v4/api.php";
    protected $_testUrl = "https://sandbox.sslcommerz.com/gwprocess/v4/api.php";

    protected $_gateValidUrl = "https://securepay.sslcommerz.com/validator/api/merchantTransIDvalidationAPI.php";
    protected $_testValidUrl = "https://sandbox.sslcommerz.com/validator/api/merchantTransIDvalidationAPI.php";



    /**  @var StoreManagerInterface */
    private $storeManager;

    /**
     * Core store config
     *
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**  @var CurrencyInterface */
    private $localecurrency;

    protected $orderRepository;
    protected $apicall;


    /**
     * Constructor
     * @param Context $context
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param CurrencyInterface $localeCurrency
     * @param StoreManagerInterface $storeManager
     * @param Apicall $apicall
     */

    public function __construct(
        Context $context,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        CurrencyInterface $localeCurrency,
        StoreManagerInterface $storeManager,
        Apicall $apicall
    )
    {
        $this->storeManager = $storeManager;
        $this->orderRepository = $orderRepository;
        $this->localecurrency = $localeCurrency;
        $this->apicall = $apicall;
        parent::__construct($context);
    }

    /** @return bool */

    public function getModuleStatus(): bool
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_ENABLE,
            ScopeInterface::SCOPE_STORE
        );
    }


    public function getConfigData($key, $store = null)
    {
        return $this->scopeConfig->getValue(
            'payment/sslcommerz_pay/' . $key,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
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

    public function getGateValidUrl()
    {
        if ($this->getConfigData('test')) {
            return $this->_testValidUrl;
        } else {
            return $this->_gateValidUrl;
        }
    }

    public function transactionInit($tranId, $sessionkey, $st)
    {

        $typename = 'payment';
        $closed = 0;
        $comment = "payment initiation ";
        $orderId = $this->getOrderIdByTrId($tranId);
        //echo "Workingggg_".$tranId.'-'.$sessionkey.' '.$st; die();
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
        $typename = 'payment';
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
        $transaction->save();
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


    public function getProductNames($orderId)
    {
        $order = $this->orderRepository->get((int)$orderId);
        foreach ($order->getAllItems() as $item) {
            $name[] = $item->getName();
        }
        //$items = implode($name, ',');
        // php74
        //return implode($words,' ') . '.';  to
        return implode(' ',$name) . '.';

      //  return $items;
    }

    public function getBaseUrl()
    {
        return $this->storeManager->getStore()->getBaseUrl();
    }


    public function getOrderIdByTrId($transId): string
    {
        $tran_id = explode("_", $transId);
        return $tran_id['0'];

    }

    public function getOrderData($orderId)
    {
        return $this->orderRepository->get((int)$orderId);

    }

    public function sslcommerz_hash_key($post_data)
    {
        // var_dump( ); die();

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
            $new_data['store_passwd'] = md5($this->getConfigData('pass_word_1'));

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

    public function getOrederStatus($orderId)
    {
        $order = $this->orderRepository->get((int)$orderId);
        return $order->getStatus();
    }


}
