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

use Magento\Checkout\Api\Data\ShippingInformationInterface;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Request\Http;
use Magento\Quote\Api\Data\AddressInterface;
use Onestepcheckout\Iosc\Helper\Data;
use Onestepcheckout\Iosc\Model\DataManager;
use Onestepcheckout\Iosc\Model\MockManager;

class GuestShippingSaveManager
{
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
     * @var Data
     */
    protected $helper;
    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     *
     * @param DataManager $dataManager
     * @param Http $request
     * @param MockManager $mockManager
     * @param Data $helper
     * @param Session $checkoutSession
     */
    public function __construct(
        DataManager $dataManager,
        Http $request,
        MockManager $mockManager,
        Data $helper,
        Session $checkoutSession
    ) {

        $this->dataManager = $dataManager;
        $this->request = $request;
        $this->mockManager = $mockManager;
        $this->helper = $helper;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * Process posted data
     *
     * @param $content
     * @param $address
     * @return AddressInterface|void
     */
    public function processPayload($content, $address)
    {

        if ($content) {
            $payload = $this->dataManager->deserializeJsonPost($content);
            if (isset($payload['paymentMethod'])) {
                unset($payload['paymentMethod']);
            }

            $payload = $this->dataManager->process($payload);
        }
        if (!empty($address)) {
            $mockedData = $this->getMockedData($this->getMockManager(), $address);
            $address = $this->addMockedData($address, $mockedData);
            return $address;
        }
    }

    /**
     * Update \Magento\Quote\Api\Data\AddressInterface with mocked data
     *
     * @param AddressInterface $address
     * @param array $mockedData
     * @return AddressInterface
     */
    public function addMockedData(AddressInterface $address, array $mockedData)
    {
        $address->addData($mockedData);
        return $address;
    }

    /**
     * Get mocked data
     *
     * @param MockManager $mockManager
     * @param AddressInterface $address
     * @return array
     */
    public function getMockedData(
        MockManager $mockManager,
        AddressInterface $address
    ) {
        return $mockManager->getMockedAddress($address);
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
     * BeforeSaveAddressInformation plugin
     *
     * @param $parent
     * @param $cartId
     * @param ShippingInformationInterface $addressInformation
     */
    public function beforeSaveAddressInformation(
        $parent,
        $cartId,
        ShippingInformationInterface $addressInformation
    ) {

        if (!$this->helper->isEnabled()) {
            return;
        }

        $content = $this->getRequest()->getContent();
        $oscGuestShippingSaveRequestHash = crc32($content);

        if ($this->checkoutSession->getOscGuestShippingSaveRequestHash() != $oscGuestShippingSaveRequestHash) {
            $this->checkoutSession->setOscGuestShippingSaveRequestHash($oscGuestShippingSaveRequestHash);
            $this->processPayload($content, []);
            $address = $this->processPayload(false, $addressInformation->getShippingAddress());
            $addressInformation->getShippingAddress()->addData($address->getData());
            $address = $this->processPayload(false, $addressInformation->getBillingAddress());
            $addressInformation->getBillingAddress()->addData($address->getData());
        }
    }
}
