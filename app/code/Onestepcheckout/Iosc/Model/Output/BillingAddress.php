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
namespace Onestepcheckout\Iosc\Model\Output;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session;
use Magento\Framework\Api\SimpleDataObjectConverter;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Quote\Api\Data\AddressExtensionFactory;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Model\Quote\AddressFactory;
use Onestepcheckout\Iosc\Model\MockManager;

/**
 * BillingAddress implements OutputManagementInterface
 */
class BillingAddress implements OutputManagementInterface
{

    /**
     * @var MockManager
     */
    protected $mockManager;
    /**
     * @var AddressInterface
     */
    protected $addressInterface;
    /**
     * @var SimpleDataObjectConverter
     */
    protected $simpleDataObjectConverter;
    /**
     * @var AddressFactory
     */
    protected $addressFactory;
    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;
    /**
     * @var AddressExtensionFactory
     */
    protected $addressExtensionFactory;
    /**
     * @var Session
     */
    protected $customerSession;

    /**
     * Get outputKey
     *
     * @return string
     */
    public function getOutputKey()
    {
        return 'billingAddress';
    }

    /**
     * @var ScopeConfigInterface
     */
    public $scopeConfig = null;

    /**
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param MockManager $mockManager
     * @param AddressInterface $addressInterface
     * @param SimpleDataObjectConverter $simpleDataObjectConverter
     * @param AddressFactory $addressFactory
     * @param CheckoutSession $checkoutSession
     * @param AddressExtensionFactory $addressExtensionFactory
     * @param Session $customerSession
     */
    public function __construct(
        ScopeConfigInterface      $scopeConfig,
        MockManager               $mockManager,
        AddressInterface          $addressInterface,
        SimpleDataObjectConverter $simpleDataObjectConverter,
        AddressFactory            $addressFactory,
        CheckoutSession           $checkoutSession,
        AddressExtensionFactory   $addressExtensionFactory,
        Session                   $customerSession
    ) {

        $this->scopeConfig = $scopeConfig;
        $this->mockManager = $mockManager;
        $this->addressInterface = $addressInterface;
        $this->simpleDataObjectConverter = $simpleDataObjectConverter;
        $this->addressFactory = $addressFactory;
        $this->checkoutSession = $checkoutSession;
        $this->addressExtensionFactory = $addressExtensionFactory;
        $this->customerSession = $customerSession;
    }

   /**
    * @inheritdoc
    *
    * @see \Onestepcheckout\Iosc\Model\Input\InputManagement::processPayload()
    */
    public function processPayload($input)
    {

        $response = [];

        $quote =  $this->checkoutSession->getQuote();

        if (isset($input[$this->getOutputKey()]) && $quote->getId()) {
            $addressData = $input[$this->getOutputKey()];
            if (! empty($addressData)) {
                $isLoggedIn = $this->customerSession->getId();

                if ($isLoggedIn && !isset($addressData['customer_address_id'])) {
                    if (!isset($addressData['customerAddressId'])) {
                        $addressData['customer_address_id'] = null;
                    }
                }

                if ($isLoggedIn &&
                    (array_key_exists('saveInAddressBook', $addressData) &&
                    $addressData['saveInAddressBook'] === null)
                ) {
                    $addressData['saveInAddressBook'] = '0';
                }
                if (isset($addressData['extension_attributes']['isUseBillingAddress'])) {
                    $this->checkoutSession->setOscBaOpenState(
                        (int)$addressData['extension_attributes']['isUseBillingAddress']
                    );
                    unset($addressData['extension_attributes']['isUseBillingAddress']);
                }

                $address = $this->addressInterface;
                if (isset($addressData['extensionAttributes']) && is_array($addressData['extensionAttributes'])) {
                    $addressData['extension_attributes'] = $addressData['extensionAttributes'];
                    unset($addressData['extensionAttributes']);
                }

                if (isset($addressData['extension_attributes']) && is_array($addressData['extension_attributes'])) {
                    foreach ($addressData['extension_attributes'] as $k => $v) {
                        if (!isset($addressData[$k])) {
                            $addressData[$k] = $v;
                        }
                        $methodName = 'set' . $this->simpleDataObjectConverter->snakeCaseToUpperCamelCase($k);
                        $extensionAttributes = $this->addressExtensionFactory->create();

                        if (method_exists($extensionAttributes, $methodName)) {
                            // phpcs:ignore Magento2.Functions.DiscouragedFunction
                            call_user_func([
                                $extensionAttributes,
                                $methodName
                            ], $v);
                        }
                    }
                    $methodName = false;
                    unset($addressData['extension_attributes']);
                }

                foreach ($addressData as $k => $v) {
                    $methodName = 'set' . $this->simpleDataObjectConverter->snakeCaseToUpperCamelCase($k);
                    if (method_exists($address, $methodName)) {
                        // phpcs:ignore Magento2.Functions.DiscouragedFunction
                        call_user_func([
                            $address,
                            $methodName
                        ], $v);
                    }
                }
            } else {
                $address = $this->addressFactory->create();
            }
            $addressMock = $this->mockManager->getMockedAddress($address);

            $response = $quote->getBillingAddress()->addData($addressMock)->save();
            $response = $this->mockManager->getFilteredAddressFields($response->getData());
        }

        return $response;
    }
}
