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
namespace Onestepcheckout\Iosc\Block\Adminhtml;

use \Magento\Customer\Model\FormFactory;
use \Magento\Customer\Model\AddressFactory;
use Magento\Customer\Model\Metadata\AddressMetadata;
use Magento\Customer\Model\Metadata\CustomerMetadata;
use Magento\Framework\View\Element\Template\Context;

class Config extends \Magento\Framework\View\Element\Template
{
    /**
     * @var AddressMetadata
     */
    protected $addressMetadata;
    /**
     * @var CustomerMetadata
     */
    protected $customerMetadata;

    /**
     * Constructor
     *
     * @param Context $context
     * @param AddressMetadata $addressMetadata
     * @param CustomerMetadata $customerMetadata
     * @param array $data
     */
    public function __construct(
        Context $context,
        AddressMetadata $addressMetadata,
        CustomerMetadata $customerMetadata,
        array $data = []
    ) {
        $this->addressMetadata = $addressMetadata;
        $this->customerMetadata = $customerMetadata;
        parent::__construct($context, $data);
    }

    /**
     * @inheritdoc
     *
     * @see \Magento\Framework\View\Element\Template::_construct()
     */
    public function _construct()
    {
        $config = $this->getDefaultFields();
        $this->setData('fields', $config);
    }

    /**
     * Get the array of default address and customer attributes
     *
     * @return array
     */
    public function getDefaultFields()
    {
        $fields = [];

        $customerAttributes = $this->customerMetadata->getAllAttributesMetadata();
        $addressAttributes = $this->addressMetadata->getAllAttributesMetadata();
        $notNeeded = [
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
            if (! in_array($attribute->getAttributeCode(), $notNeeded)) {
                $fields['customer'][$attribute->getAttributeCode()] = [
                    'required' => (int)$attribute->isRequired()

                ];
            }
        }

        foreach ($addressAttributes as $attribute) {
            if (! in_array($attribute->getAttributeCode(), $notNeeded)) {
                $lines = $attribute->getMultilineCount();
                if ($lines > 0) {
                    for ($i = 0; $i <= ($lines-1); $i++) {
                        $fields['address'][$attribute->getAttributeCode(). '.' . $i] = [
                            'required' => (int)$attribute->isRequired()
                        ];
                    }
                } else {
                    $fields['address'][$attribute->getAttributeCode()] = [
                        'required' => (int)$attribute->isRequired()
                    ];
                }
            }
        }

        if (! empty($fields['address']) && $fields['customer']) {
            $fields['merged'] = array_merge($fields['address'], $fields['customer']);
        }

        return $fields;
    }
}
