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

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Request\Http;
use Magento\Quote\Api\Data\AddressInterface;
use Onestepcheckout\Iosc\Helper\Data;
use Onestepcheckout\Iosc\Model\DataManager;
use Onestepcheckout\Iosc\Model\MockManager;

class GuestBillingSaveManager
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
     * @param $billingAddress
     */
    public function processPayload($content, $billingAddress)
    {
        if ($content) {
            $payload = $this->dataManager->deserializeJsonPost($content);

            if (isset($payload['paymentMethod'])) {
                unset($payload['paymentMethod']);
            }
            if (isset($payload['shippingMethod'])) {
                unset($payload['shippingMethod']);
            }
            $payload = $this->dataManager->process($payload);
        }
        if (!empty($billingAddress)) {
            $mockedData = $this->getMockedData($this->getMockManager(), $billingAddress);
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
     * BeforeAssign plugin
     *
     * @param $parent
     * @param $cartId
     * @param AddressInterface $address
     * @param string $useForShipping
     */
    public function beforeAssign($parent, $cartId, $address, $useForShipping = false)
    {
        if (!$this->helper->isEnabled()) {
            return;
        }

        $content = $this->getRequest()->getContent();
        $oscGuestBillingSaveRequestHash = crc32($content);

        if ($this->checkoutSession->getOscGuestBillingSaveRequestHash() != $oscGuestBillingSaveRequestHash) {
            $this->checkoutSession->setOscGuestBillingSaveRequestHash($oscGuestBillingSaveRequestHash);
            $this->processPayload($content, $address);
        }
    }
}
