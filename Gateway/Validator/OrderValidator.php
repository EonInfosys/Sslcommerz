<?php

namespace Sslcommerz\Payment\Gateway\Validator;

use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\Validator\ResultInterface;
use Magento\Sales\Api\OrderRepositoryInterface;

/**
 * @author    EonInfosys Team <matinict@gmail.com>
 * @copyright 2021 EonInfosys
 * @link      https://github.com/eoninfosys
 */

class OrderValidator extends AbstractValidator
{

    protected $orderRepository;

    public function __construct(
        OrderRepositoryInterface $orderRepository
    ){
        $this->orderRepository = $orderRepository;
    }
    /**
     * Performs validation of response
     *
     * @param array $validationSubject
     * @return ResultInterface
     */
    public function validate(array $validationSubject)
    {
    }

    public function getOrderStatus($orderId)
    {

        try {
            $order = $this->orderRepository->get((int)$orderId);
            $state = $order->getState(); //Get Order State(Complete, Processing, ....)
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e){
            $state = false;
        }

        return  $state;


    }



}
