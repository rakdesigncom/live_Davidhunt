<?php

declare(strict_types=1);

namespace Fyb\Theme\Model\ValidationRules;

use Magento\Framework\Validation\ValidationResultFactory;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\ValidationRules\QuoteValidationRuleInterface;

/**
 * Class NameValidationRule
 * Validates the first name and last name fields in a quote.
 */
class NameValidationRule implements QuoteValidationRuleInterface
{
    /**
     * Regular expression pattern for validating names.
     *
     * \p{L}: Unicode letters.
     * \p{M}: Unicode marks (diacritic marks, accents, etc.).
     * ,: Comma.
     * -: Hyphen.
     * _: Underscore.
     * .: Period.
     * ': Apostrophe mark.
     * ’: Right single quotation mark.
     * `: Grave accent.
     * &: Ampersand.
     * \s: Whitespace characters (spaces, tabs, newlines, etc.).
     * \d: Digits (0-9).
     */
    private const PATTERN_NAME = '/(?:[\p{L}\p{M}\,\-\_\.\'’`\s\d]){1,255}+/u';

    /**
     * @var ValidationResultFactory
     */
    private $validationResultFactory;

    /**
     * Constructor.
     *
     * @param ValidationResultFactory $validationResultFactory
     */
    public function __construct(ValidationResultFactory $validationResultFactory)
    {
        $this->validationResultFactory = $validationResultFactory;
    }

    /**
     * Validate the first name and last name in the quote.
     *
     * @param Quote $quote
     *
     * @return array
     */
    public function validate(Quote $quote): array
    {
        $validationErrors = [];
        $firstName = $quote->getCustomerFirstname();
        $lastName = $quote->getCustomerLastname();

        if (!$this->isValidName($firstName) || !$this->isValidName($lastName)) {
            $validationErrors[] = __('Only letters, numbers, spaces and mix of \- \_ \' \’ \` are allowed in First Name and Last Name.');
        }

        return [$this->validationResultFactory->create(['errors' => $validationErrors])];
    }

    /**
     * Check if a name field is valid according to the pattern.
     *
     * @param string|null $nameValue
     *
     * @return bool
     */
    private function isValidName($nameValue): bool
    {
        if ($nameValue !== null) {
            if (preg_match(self::PATTERN_NAME, $nameValue, $matches)) {
                return $matches[0] === $nameValue;
            }
        }

        return false;
    }
}
