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
class Postcode extends AbstractValidator
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
    private const PATTERN_POSTCODE = '/(?:[\p{L}\p{M}\d\s\-]{1,100})/u';

    /**
     * Validate city fields.
     *
     * @param Customer $customer
     * @return bool
     */
    public function isValid($customer)
    {
        if (!$this->isValidPostcode($customer->getPostcode())) {
            parent::_addMessages([[
                'postcode' => "Invalid Postcode. Please use A-Z, a-z, 0-9, -, spaces"
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
    private function isValidPostcode($postcode)
    {
        if ($postcode != null) {
            if (preg_match(self::PATTERN_POSTCODE, $postcode, $matches)) {
                return $matches[0] == $postcode;
            }
        }

        return true;
    }
}
