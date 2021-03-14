<?php

namespace Sslcommerz\Payment\Gateway\Validator;

use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\Validator\ResultInterface;

/**
 * @author    EonInfosys Team <matinict@gmail.com>
 * @copyright 2021 EonInfosys
  * @link      https://github.com/eoninfosys
 */
class CaptureResponseValidator extends AbstractValidator
{
    const RESULT_CODE = 'RESULT_CODE';

    /**
     * Performs validation of response
     *
     * @param array $validationSubject
     * @return ResultInterface
     */
    public function validate(array $validationSubject)
    {
        return $this->createResult(true);
    }
}
