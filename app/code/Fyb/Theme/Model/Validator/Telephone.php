<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fyb\Theme\Model\Validator;

use Magento\Customer\Model\Customer;
use Magento\Framework\Validator\AbstractValidator;

/**
 * Customer telephone fields validator.
 */
class Telephone extends AbstractValidator
{
    /**
     * Allowed char:
     *
     * \(\) :Matches open and close parentheses
     * \s: Matches any whitespace character.
     * \+: Matches the plus sign.
     * \-: Matches the hyphen.
     * \d: Digits (0-9).
     */
    private const PATTERN_TELEPHONE = '/(?:[\d\+\-\()]{1,20})/u';

    /**
     * Validate telephone fields.
     *
     * @param Customer $customer
     * @return bool
     */
    public function isValid($customer)
    {
        if (!$this->isValidTelephone($customer->getTelephone())) {
            parent::_addMessages([[
                'telephone' => "Invalid Phone Number. Please use 0-9, +, -, (, ). space not allowed."
            ]]);
        }

        return count($this->_messages) == 0;
    }

    /**
     * Check if telephone field is valid.
     *
     * @param string|null $telephoneValue
     * @return bool
     */
    private function isValidTelephone($telephoneValue)
    {
        if ($telephoneValue != null) {
            if (preg_match(self::PATTERN_TELEPHONE, $telephoneValue, $matches)) {
                return $matches[0] == $telephoneValue;
            }
        }

        return true;
    }
}
