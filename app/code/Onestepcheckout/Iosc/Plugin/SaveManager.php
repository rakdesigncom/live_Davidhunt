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
namespace Onestepcheckout\Iosc\Plugin;

use Exception;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Onestepcheckout\Iosc\Helper\Data;
use Onestepcheckout\Iosc\Model\DataManager;
use Onestepcheckout\Iosc\Model\MockManager;

class SaveManager
{
    /**
     * @var boolean
     */
    public $isPlaceOrderCalled = false;
    /**
     * @var DataManager
     */
    protected $dataManager;
    /**
     * @var Http
     */
    protected $request;
    /**
     * @var MockManager
     */
    protected $mockManager;
    /**
     * @var Session
     */
    protected $checkoutSession;
    /**
     * @var Data
     */
    protected $helper;

    /**
     *
     * @param DataManager $dataManager
     * @param Http $request
     * @param MockManager $mockManager
     * @param Session $checkoutSession
     * @param Data $helper
     */
    public function __construct(
        DataManager $dataManager,
        Http $request,
        MockManager $mockManager,
        Session $checkoutSession,
        Data $helper
    ) {

        $this->dataManager = $dataManager;
        $this->request = $request;
        $this->mockManager = $mockManager;
        $this->checkoutSession = $checkoutSession;
        $this->helper = $helper;
    }

    /**
     * Process posted data
     *
     * @param $content
     * @param $billingAddress
     */
    public function processPayload($content, $billingAddress)
    {
        if ($content) {
            $payload = $this->dataManager->deserializeJsonPost($content);
            unset($payload['paymentMethod']);
            unset($payload['shippingMethod']);
            $payload = $this->dataManager->process($payload);
        }
        if (!empty($billingAddress) && empty($billingAddress->getCustomerAddressId())) {
            $mockedData = $this->getMockedData($this->getMockManager(), $billingAddress);

            $errors = $this->getMockManager()->validateMockedData($mockedData);
            if (!empty($errors)) {
                throw new CouldNotSaveException(__(implode(', ', $errors)), null);
            }

            $this->addMockedData($billingAddress, $mockedData);
        }
    }

    /**
     * Update \Magento\Quote\Api\Data\AddressInterface with mocked data
     *
     * @param AddressInterface $billingAddress
     * @param array $mockedData
     */
    public function addMockedData(AddressInterface $billingAddress, array $mockedData)
    {
        $billingAddress->addData($mockedData);
    }

    /**
     * Get mocked data
     *
     * @param MockManager $mockManager
     * @param AddressInterface $billingAddress
     * @return array
     */
    public function getMockedData(
        MockManager $mockManager,
        AddressInterface $billingAddress
    ) {
        return $mockManager->getMockedAddress($billingAddress);
    }

    /**
     * Get \Onestepcheckout\Iosc\Model\MockManager
     */
    public function getMockManager()
    {
        return $this->mockManager;
    }

    /**
     * Get \Magento\Framework\App\Request\Http
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Before plugin to save all posted data before order placement
     *
     * @param $parent
     * @param $cartId
     * @param PaymentInterface $paymentMethod
     * @param AddressInterface $billingAddress
     */
    public function beforeSavePaymentInformationAndPlaceOrder(
        $parent,
        $cartId,
        PaymentInterface $paymentMethod,
        AddressInterface $billingAddress = null
    ) {

        if (!$this->helper->isEnabled()) {
            return;
        }
        $this->setIsPlaceOrderCalled(true);
        $this->savePaymentInformation($billingAddress);
    }

    /**
     * Before plugin to save all posted data before order placement
     *
     * @param $parent
     * @param $email
     * @param PaymentInterface $paymentMethod
     * @param AddressInterface|null $billingAddress
     */
    public function beforeSavePaymentInformation(
        $parent,
        $email,
        PaymentInterface $paymentMethod,
        AddressInterface $billingAddress = null
    ) {
        if (!$this->helper->isEnabled() || $this->getIsPlaceOrderCalled()) {
            $this->setIsPlaceOrderCalled(false);
            return;
        }
        $this->savePaymentInformation($billingAddress);
    }

    /**
     * Get if beforeSavePaymentInformationAndPlaceOrder is hit in same process
     *
     * @return boolean
     */
    protected function getIsPlaceOrderCalled()
    {
        /*
         * This status is needed cause some payment methods don't hit the
         * same api end-points to place the order or redirect to gateway.
         * Whichever end-point you hit you hit the savePaymentInformation method is called
         * on set-payment-information or payment-information end-points.
         * We set variable to avoid calling out the same method multiple times
         * saves some on performance.
         */
        return $this->isPlaceOrderCalled;
    }

    /**
     * Set if beforeSavePaymentInformationAndPlaceOrder is hit in same process
     *
     * @param boolean $val
     * @return boolean
     */
    protected function setIsPlaceOrderCalled($val = true)
    {
        return $this->isPlaceOrderCalled = $val;
    }

    /**
     * Save all posted data before order placement
     *
     * @param AddressInterface $billingAddress
     */
    protected function savePaymentInformation(
        AddressInterface $billingAddress = null
    ) {
        try {
            $content = $this->getRequest()->getContent();
            $oscRequestHash = crc32($content);
            $storepickupIf = (boolean)strpos($content, 'pickup_location_code') ?? false;
            if ($this->checkoutSession->getOscSaveRequestHash() != $oscRequestHash ||
                $storepickupIf === true
            ) {
                $this->checkoutSession->setOscSaveRequestHash($oscRequestHash);
                $this->processPayload(false, $billingAddress);
                $this->processPayload($content, []);
            }
        } catch (Exception $e) {
            throw new CouldNotSaveException(
                __('Please verify your billing address on following fields: ' . $e->getMessage()),
                $e
            );
        }
    }
}
