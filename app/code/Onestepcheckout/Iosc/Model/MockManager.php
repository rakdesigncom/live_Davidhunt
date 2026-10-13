<?php
/**
 * OneStepCheckout
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to One Step Checkout AS software license.
 *
 * License is available through the world-wide-web at this URL:
 * https://www.onestepcheckout.com/LICENSE.txt
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to mail@onestepcheckout.com so we can send you a copy immediately.
 *
 * @category   onestepcheckout
 * @package    onestepcheckout_iosc
 * @copyright  Copyright (c) 2017 OneStepCheckout  (https://www.onestepcheckout.com/)
 * @license    https://www.onestepcheckout.com/LICENSE.txt
 */
namespace Onestepcheckout\Iosc\Model;

use Magento\Customer\Model\Metadata\AddressMetadata;
use Magento\Customer\Model\Metadata\CustomerMetadata;
use Onestepcheckout\Iosc\Helper\Data;

class MockManager
{

    public const MOCK_VALUE = '-';
    public const EMAIL_MOCK_VALUE = 'support@onestepcheckout.com';
    public const COUNTRY_ID_MOCK_VALUE = 'US';
    /**
     * @var AddressMetadata
     */
    protected $addressMetadata;
    /**
     * @var CustomerMetadata
     */
    protected $customerMetadata;
    /**
     * @var Data
     */
    protected $helper;

    /**
     *
     * @param AddressMetadata $addressMetadata
     * @param CustomerMetadata $customerMetadata
     * @param Data $helper
     */
    public function __construct(
        AddressMetadata $addressMetadata,
        CustomerMetadata $customerMetadata,
        Data $helper
    ) {
        $this->addressMetadata = $addressMetadata;
        $this->customerMetadata = $customerMetadata;
        $this->helper = $helper;
    }

    /**
     * Get the array of default address and customer attributes
     *
     * @return array
     */
    public function getDefaultAddressFields()
    {
        $fields = [];

        $customerAttributes = $this->customerMetadata->getAllAttributesMetadata();
        $addressAttributes = $this->addressMetadata->getAllAttributesMetadata();

        $ignoredFields = [
            'created_in',
            'disable_auto_group_change',
            'website_id',
            'group_id',
            'created_at',
            'updated_at',
            'default_billing',
            'default_shipping',
            'password_hash',
            'rp_token',
            'rp_token_created_at',
            'store_id',
            'confirmation',
            'vat_is_valid',
            'vat_request_date',
            'vat_request_id',
            'vat_request_success',
            'region_id', //this is merged with region
            'failures_num',
            'first_failure',
            'lock_expires'
        ];

        foreach ($customerAttributes as $attribute) {
            if (! in_array($attribute->getAttributeCode(), $ignoredFields)) {
                $fields['customer'][$attribute->getAttributeCode()] = [
                    'required' => (int)$attribute->isRequired()

                ];
            }
        }
        foreach ($addressAttributes as $attribute) {
            if (! in_array($attribute->getAttributeCode(), $ignoredFields)) {
                $fields['address'][$attribute->getAttributeCode()] = [
                    'required' => (int)$attribute->isRequired()
                ];
            }
        }

        if (! empty($fields['address']) && $fields['customer']) {
            $fields['merged'] = array_merge($fields['address'], $fields['customer']);
        }
        return $fields;
    }

    /**
     * Get required fields out of default existing fields
     *
     * @return array
     */
    public function getRequiredDefaultAddressFields()
    {
        $defaultFields = $this->getDefaultAddressFields();
        $required = [];
        foreach ($defaultFields['merged'] as $k => $v) {
            if ($v['required']) {
                $required[$k] = $v;
            }
        }
        return $required;
    }

    /**
     * Returns filtered array of available fields
     *
     * @param array $data
     * @return array $data
     */
    public function getFilteredAddressFields(array $data = []): array
    {

        $defaultFields = $this->getDefaultAddressFields();
        foreach ($data as $k => $v) {
            if (!isset($defaultFields['merged'][$k])) {
                unset($data[$k]);
            }
        }
        foreach ($data as $k => $v) {
            if ($v === self::MOCK_VALUE) {
                $data[$k] = '';
            }
        }

        return $data;
    }

    /**
     * Get Mocked Address
     *
     * @param object $address
     * @param array $required
     * @return array
     */
    public function getMockedAddress($address, array $required = []): array
    {

        if (empty($required)) {
            $required = $this->getRequiredDefaultAddressFields();
        }

        $address = $address->getData();

        foreach ($required as $k => $v) {
            if (!isset($address[$k]) || (isset($address[$k]) && $address[$k] === "")) {
                if ($k == 'email') { // phpcs:ignore Magento2.CodeAnalysis.EmptyBlock
                } elseif ($k == 'country_id') {
                    $address[$k] = self::COUNTRY_ID_MOCK_VALUE;
                } elseif ($k == 'street' && empty($address[$k])) {
                    $address[$k] = [self::MOCK_VALUE];
                } else {
                    $address[$k] = self::MOCK_VALUE;
                }
            }
        }

        return $address;
    }

    /**
     * Get default config values for address
     *
     * @param Magento\Quote\Model\Quote\Address $address
     * @param array $defaults
     * @return array();
     */
    public function getDefaultAddressValues($address, array $defaults = [])
    {
        $data = [];
        $address = $address->getData();

        foreach ($defaults as $key => $value) {
            if ($value['default_value'] != '') {
                /**
                 * special cases for region and streets are needed as those are handled differently in data structure
                 */
                if ($key == 'region' && is_numeric($value['default_value'])) {
                    $data['region_id'] = $value['default_value'];
                } else {
                    $data[$key] = $value['default_value'];
                }
            }
        }

        return $data;
    }

    /**
     * See if mocked data still contains defaults on enabled and required fields
     *
     * @param array $data
     * @param string $type
     * @return array $errors list of fields or empty
     */
    public function validateMockedData(array $data, $type = 'billingfields')
    {

        $errors = [];
        $fieldConfig = $this->helper->getConfig([], [$type], $type);

        if (isset($data['region_id'])) {
            $data['region'] = $data['region_id'];
            unset($data['region_id']);
        }
        if (isset($data['street']) && !is_array($data['street'])) {
            $streetFields = explode("\n", $data['street']);
            foreach ($streetFields as $k => $v) {
                $data['street.'. $k] = $v;
            }
            unset($data['street']);
        }

        foreach ($data as $k => $v) {

            if (isset($fieldConfig[$k]) &&
                $fieldConfig[$k]['enabled'] &&
                $fieldConfig[$k]['required'] &&
                trim($v ?? '') == self::MOCK_VALUE
            ) {
                if (strstr($k, 'street.')) {
                    $k = 'street';
                }
                $p = __($k);
                $errors[] = $k;
            }
        }

        return $errors;
    }
}
