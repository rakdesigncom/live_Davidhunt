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
 * Customer city fields validator.
 */
class Company extends AbstractValidator
{
    /**
     * Allowed characters:
     *
     * \p{L}: Unicode letters.
     * \p{M}: Unicode marks (diacritic marks, accents, etc.).
     * ,: Comma.
     * \-: Hyphen.
     * \.: Period.
     * `'’: Single quotes, both regular and right single quotation marks.
     * &: Ampersand.
     * \s: Whitespace characters (spaces, tabs, newlines, etc.).
     * \d: Digits (0-9).
     */
    private const PATTERN_COMPANY = '/(?:[\p{L}\p{M}\d\s\-]{1,100})/iu';

    /**
     * Validate city fields.
     *
     * @param Customer $customer
     * @return bool
     */
    public function isValid($customer)
    {
        if (!$this->isValidCompany($customer->getCompany())) {
            parent::_addMessages([[
                'company' => "Invalid Company. Please use A-Z, a-z, 0-9, -, ', spaces"
            ]]);
        }

        return count($this->_messages) == 0;
    }

    /**
     * Check if city field is valid.
     *
     * @param string|null $company
     * @return bool
     */
    private function isValidCompany($company)
    {
        if ($company != null) {
            if (preg_match(self::PATTERN_COMPANY, $company, $matches)) {
                return $matches[0] == $company;
            }
        }

        return true;
    }
}
