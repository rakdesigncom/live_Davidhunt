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
namespace Onestepcheckout\Iosc\Observer\Frontend;

use Magento\Checkout\Model\Session;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DataObject\Copy;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Quote\Model\Quote;
use Magento\Store\Model\ScopeInterface;
use Onestepcheckout\Iosc\Helper\Data;
use Psr\Log\LoggerInterface;

class Setlogindefaults implements ObserverInterface
{

    /**
     * @var ScopeConfigInterface|null
     */
    public $scopeConfig = null;
    /**
     * @var CustomerSession
     */
    protected $customerSession;
    /**
     * @var Copy
     */
    protected $objectCopyService;
    /**
     * @var Session
     */
    protected $checkoutSession;
    /**
     * @var LoggerInterface
     */
    protected $log;
    /**
     * @var Data
     */
    protected $helper;

    /**
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param CustomerSession $customerSession
     * @param Copy $objectCopyService
     * @param Session $checkoutSession
     * @param LoggerInterface $logger
     * @param Data $helper
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        CustomerSession      $customerSession,
        Copy                 $objectCopyService,
        Session              $checkoutSession,
        LoggerInterface      $logger,
        Data                 $helper
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->customerSession = $customerSession;
        $this->objectCopyService = $objectCopyService;
        $this->checkoutSession = $checkoutSession;
        $this->log = $logger;
        $this->helper = $helper;
    }

    /**
     * Add default data to address objects
     *
     * @param Observer $observer
     * @event customer_login
     *
     * @return void
     */
    public function execute(Observer $observer)
    {
        /** @var Quote $quote */
        $quote = $this->checkoutSession->getQuote();

        if (!$this->helper->isEnabled() || ! is_object($quote)) {
            return;
        }

        if (!empty($quote->getCustomer()) && !empty($quote->getCustomer()->getAddresses())) {
            $saveQuote = false;
            $customer = $observer->getEvent()->getCustomer();
            $primaryBilling = $customer->getPrimaryBillingAddress();
            if (!$quote->getBillingAddress()->getCustomerAddressId() &&
                (is_object($primaryBilling) && $primaryBilling->getId())
            ) {
                $newBilling = $this->copyAddressData(
                    $customer->getPrimaryBillingAddress(),
                    $quote->getBillingAddress()
                );
                $quote->setBillingAddress($newBilling);
                $saveQuote = true;
            }
            $primaryShipping = $customer->getPrimaryShippingAddress();
            if (!$quote->getShippingAddress()->getCustomerAddressId() &&
                (is_object($primaryShipping) && $primaryShipping->getId())
            ) {
                $newShipping = $this->copyAddressData(
                    $customer->getPrimaryShippingAddress(),
                    $quote->getShippingAddress()
                );
                $quote->setShippingAddress($newShipping);
                $saveQuote = true;
            }
            $scopeStore = ScopeInterface::SCOPE_STORE;
            $baOpenDefault = (int)$this
                                ->scopeConfig->getValue('onestepcheckout_iosc/billingfields/opendefault', $scopeStore);
            if ($baOpenDefault &&
                is_object($primaryShipping) &&
                is_object($primaryBilling)
            ) {
                $shippingAddressId = (int)$primaryShipping->getId();
                $billingAddressId = (int)$primaryBilling->getId();

                if ($shippingAddressId !== $billingAddressId) {
                    $this->checkoutSession->setOscBaOpenState($baOpenDefault);
                }
            }

            if ($saveQuote) {
                $quote->save();
            }
        }
    }

    /**
     * Copy Address data
     *
     * @param array $source
     * @param $target
     * @return $target
     */
    private function copyAddressData($source, $target)
    {
        $this->objectCopyService->copyFieldsetToTarget(
            'customer_address',
            'to_quote_address',
            $source,
            $target
        );

        return $target;
    }
}
