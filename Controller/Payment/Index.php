<?php

namespace Sslcommerz\Payment\Controller\Payment;


use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Request\Http;
use Sslcommerz\Payment\Model\Sslcommerznew;
use Sslcommerz\Payment\Gateway\Validator\OrderValidator;
use Sslcommerz\Payment\Gateway\Request\InitRequest;


class Index extends \Magento\Framework\App\Action\Action
{
    protected $request;
    protected $initRequest;
    protected $sslcommerznewData;
    protected $orderValidator;
    protected $resultPageFactory;


    public function __construct(
        Context $context,
        Http $request,
        InitRequest $initRequest,
        Sslcommerznew $sslcommerznewData,
        OrderValidator $orderValidator,
        PageFactory $resultPageFactory
    )
    {
        $this->resultPageFactory = $resultPageFactory;
        $this->request = $request;
        $this->initRequest = $initRequest;
        $this->sslcommerznewData = $sslcommerznewData;
        $this->orderValidator = $orderValidator;
        parent::__construct($context);
    }

    public function execute()
    {
        $getData = $this->request->getParams();
        $state = $this->orderValidator->getOrderStatus($getData['order_id']);
        //var_dump($state);die();

        if (($state === false) || ($state != "pending_payment")) {
            echo "Already Paid or Order not for payment! plz check orderId again...";
        } else {
            $postData = $this->initRequest->getPostData($getData['order_id']);

        }


    }


}
