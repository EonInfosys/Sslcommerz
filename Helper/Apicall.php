<?php


namespace Sslcommerz\Payment\Helper;

use \Magento\Framework\App\Config\ScopeConfigInterface;
use \Magento\Store\Model\StoreManagerInterface;
use \Magento\Framework\Exception\LocalizedException;

class Apicall extends \Magento\Framework\App\Helper\AbstractHelper
{
    protected $scopeConfig;
    protected $_storeManager;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager
    )
    {
        $this->scopeConfig = $scopeConfig;
        $this->_storeManager = $storeManager;

    }

    public function isEnable()
    {
        return $this->scopeConfig->getValue(
            'payment/sslcommerz_pay/active',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }


    public function createSessionApiCall($url, $data)
    {


        if ($this->isEnable()) {
            $client = new \GuzzleHttp\Client
            ([
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'http_errors' => false,
                    'verify' => false,
                ]
            ]);

            try {
                $response = $client->post($url, $data);
            } catch (GuzzleHttp\Exception\ClientException $e) {
                $response = $e->getResponse();
            }

            return $response;
        }
    }

    public function orderValidationApi($requested_url)
    {
        $client = new \GuzzleHttp\Client
        ([
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'http_errors' => false,
                'verify' => false,
            ]
        ]);

        try {
            $response = $client->get($requested_url);
        } catch (GuzzleHttp\Exception\ClientException $e) {
            $response = $e->getResponse();
        }
        return $response;
    }


    public function transactionValidation($requested_url)
    {

        $client = new \GuzzleHttp\Client
        ([
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'http_errors' => false,
                'verify' => false,
            ]
        ]);

        try {
            $response = $client->get($requested_url);
        } catch (GuzzleHttp\Exception\ClientException $e) {
            $response = $e->getResponse();
        }

            $t_status = "";
            if ($response->getStatusCode() == 200) {
                # JSON CONVERT T
                $result = json_decode($response->getBody(), true);
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
