<?php

namespace Sslcommerz\Payment\Gateway\Validator;

use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\Validator\ResultInterface;

/**
 * @author    EonInfosys Team <matinict@gmail.com>
 * @copyright 2021 EonInfosys
  * @link      https://github.com/eoninfosys
 */
class RefundResponseValidator extends AbstractValidator
{
    /**
     * Performs validation of response
     *
     * @param array $validationSubject
     * @return ResultInterface
     */
    public function validate(array $validationSubject)
    {
        if (!isset($validationSubject['response'])) {
            throw new \InvalidArgumentException('Response does not exist');
        }

        $response = $validationSubject['response'];

        if (isset($response['api_response'])) {
            if (isset($response['api_response']->error)) {
                return $this->createResult(
                    false,
                    [__('Could not capture the charge')]
                );
            }
            if (!$response['api_response']->getId()) {
                return $this->createResult(
                    false,
                    [__('Invalid Refund')]
                );
            }
        } elseif (isset($response['message'])) {
            return $this->createResult(
                false,
                [__($response['message'])]
            );
        }

        return $this->createResult(true);
    }
}
