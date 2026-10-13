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

use Magento\Checkout\Model\Session;
use Magento\Framework\Api\SimpleDataObjectConverter;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Webapi\ServiceOutputProcessor;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\CartTotalRepositoryInterface;
use Magento\Quote\Api\Data\CartExtensionFactory;
use Magento\Quote\Model\Cart\ShippingMethodConverter;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\ShippingAssignment\ShippingAssignmentPersister;
use Magento\Quote\Model\Quote\TotalsCollector;
use Magento\Quote\Model\ShippingAssignmentFactory;
use Magento\Quote\Model\ShippingFactory;
use Onestepcheckout\Iosc\Helper\Data;

/**
 * ShippingMethod implements OutputManagementInterface
 */
class ShippingMethod implements OutputManagementInterface
{

    /**
     * @var Data
     */
    protected $helper;
    /**
     * @var SimpleDataObjectConverter
     */
    protected $simpleDataObjectConverter;
    /**
     * @var ServiceOutputProcessor
     */
    protected $serviceOutputProcessor;
    /**
     * @var ShippingMethodConverter
     */
    protected $converter;
    /**
     * @var ShippingAssignmentFactory
     */
    protected $shippingAssignmentFactory;
    /**
     * @var ShippingFactory
     */
    protected $shippingFactory;
    /**
     * @var CartExtensionFactory
     */
    protected $cartExtensionFactory;
    /**
     * @var CartRepositoryInterface
     */
    protected $quoteRepository;
    /**
     * @var ShippingAssignmentPersister
     */
    protected $shippingAssignmentPersister;
    /**
     * @var Session
     */
    protected $checkoutSession;
    /**
     * @var TotalsCollector
     */
    protected $totalsCollector;
    /**
     * @var CartTotalRepositoryInterface
     */
    protected $cartTotalsRepository;

    /**
     * Get outputKey
     *
     * @return string
     */
    public function getOutputKey()
    {
        return 'shippingMethod';
    }

    /**
     * @var ScopeConfigInterface
     */
    public $scopeConfig = null;

    /**
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param Data $helper
     * @param SimpleDataObjectConverter $simpleDataObjectConverter
     * @param ServiceOutputProcessor $serviceOutputProcessor
     * @param ShippingMethodConverter $converter
     * @param CartRepositoryInterface $quoteRepository
     * @param ShippingAssignmentFactory $shippingAssignmentFactory
     * @param CartExtensionFactory $cartExtensionFactory
     * @param ShippingFactory $shippingFactory
     * @param ShippingAssignmentPersister $shippingAssignmentPersister
     * @param Session $checkoutSession
     * @param TotalsCollector $totalsCollector
     * @param CartTotalRepositoryInterface $cartTotalsRepository
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Data $helper,
        SimpleDataObjectConverter $simpleDataObjectConverter,
        ServiceOutputProcessor $serviceOutputProcessor,
        ShippingMethodConverter $converter,
        CartRepositoryInterface $quoteRepository,
        ShippingAssignmentFactory $shippingAssignmentFactory,
        CartExtensionFactory $cartExtensionFactory,
        ShippingFactory $shippingFactory,
        ShippingAssignmentPersister $shippingAssignmentPersister,
        Session $checkoutSession,
        TotalsCollector $totalsCollector,
        CartTotalRepositoryInterface $cartTotalsRepository
    ) {

        $this->scopeConfig = $scopeConfig;
        $this->helper = $helper;
        $this->simpleDataObjectConverter = $simpleDataObjectConverter;
        $this->serviceOutputProcessor = $serviceOutputProcessor;
        $this->converter = $converter;
        $this->shippingAssignmentFactory = $shippingAssignmentFactory;
        $this->shippingFactory = $shippingFactory;
        $this->cartExtensionFactory = $cartExtensionFactory;
        $this->quoteRepository = $quoteRepository;
        $this->shippingAssignmentPersister = $shippingAssignmentPersister;
        $this->checkoutSession = $checkoutSession;
        $this->totalsCollector = $totalsCollector;
        $this->cartTotalsRepository = $cartTotalsRepository;
    }

    /**
     * @inheritdoc
     *
     * @see \Onestepcheckout\Iosc\Model\Input\InputManagement::processPayload()
     */
    public function processPayload($input)
    {
        $data = [];

        /** @var Quote $quote */
        $quote = $this->checkoutSession->getQuote();
        if (!$quote->getId() || $quote->isVirtual() || $quote->getItemsCount() == 0) {
            return $data;
        }
        $shippingAddress = $quote->getShippingAddress();
        if (count($shippingAddress->getAllItems()) != count($quote->getAllItems())) {
            $shippingAddress->setData('cached_items_all', $quote->getAllItems());
        }
        $doubleRequest = false;
        if ($quote->getOscCollectRates() !== "1") {
            $shippingAddress->setCollectShippingRates(true);
        } else {
            $shippingAddress->setCollectShippingRates(false);
        }

        $data = $this
            ->getShippingRates($quote, $this->converter, $this->serviceOutputProcessor);
        $codes = [];
        foreach ($data as $rate) {
            $codes[] = $rate['carrier_code'] . '_' . $rate['method_code'];
        }
        if (!empty($input[$this->getOutputKey()])) {
            $shippingMethodRequest = $input[$this->getOutputKey()];
            $method = $shippingMethodRequest['shipping_carrier_code']
            . '_' . $shippingMethodRequest['shipping_method_code'];
            $selectedMethod = $shippingAddress->getShippingMethod();
            $suggestedMethod = $quote->getIoscAutoshippingMethod();

            if (!empty($suggestedMethod) && $suggestedMethod !== $method && in_array($suggestedMethod, $codes)) {
                $method = $suggestedMethod;
            }
            if ($this->checkoutSession->getPrevCountry() == $shippingAddress->getCountryId()) {
                $shippingAddress->setShippingMethod(null);
            }
            if (in_array($method, $codes) &&
                $method != $selectedMethod
            ) {
                $quote = $this->prepareShippingAssignment($quote, $shippingAddress, $method);
                $shippingAssignments = $quote->getExtensionAttributes()->getShippingAssignments();
                $this->shippingAssignmentPersister->save($quote, current($shippingAssignments));

            }
        }

        return $data;
    }

    /**
     * Get available shipping rates
     *
     * @param Quote $quote
     * @param ShippingMethodConverter $converter
     * @param ServiceOutputProcessor $serviceOutputProcessor
     * @return array
     */
    private function getShippingRates(
        Quote $quote,
        ShippingMethodConverter $converter,
        ServiceOutputProcessor $serviceOutputProcessor
    ) {
        $rates = [];
        $shippingAddress = $quote->getShippingAddress();
        if (!empty($quote->setCartFixedRules())) {
            $quote->setCartFixedRules();
        }

        $this->totalsCollector->collectAddressTotals($quote, $shippingAddress);

        $shippingRates = $shippingAddress->getGroupedAllShippingRates();

        foreach ($shippingRates as $carrierRates) {
            foreach ($carrierRates as $rate) {
                $rates[$rate->getCode()] = $converter->modelToDataObject($rate, $quote->getQuoteCurrencyCode());
            }
        }

        $rates = $serviceOutputProcessor
            ->convertValue($rates, '\Magento\Quote\Api\Data\ShippingMethodInterface[]');

        return $rates;
    }

    /**
     * Prepare shipping assignment
     *
     * @param CartInterface $quote
     * @param AddressInterface $address
     * @param string $method
     * @return CartInterface
     */
    public function prepareShippingAssignment($quote, $address, $method)
    {
        $cartExtension = $quote->getExtensionAttributes();
        if ($cartExtension === null) {
            $cartExtension = $this->cartExtensionFactory->create();
        }

        $shippingAssignments = $cartExtension->getShippingAssignments();
        if (empty($shippingAssignments)) {
            $shippingAssignment = $this->shippingAssignmentFactory->create();
        } else {
            $shippingAssignment = $shippingAssignments[0];
        }

        $shipping = $shippingAssignment->getShipping();
        if ($shipping === null) {
            $shipping = $this->shippingFactory->create();
        }

        $shipping->setAddress($address);
        $shipping->setMethod($method);
        $shippingAssignment->setShipping($shipping);
        $cartExtension->setShippingAssignments([$shippingAssignment]);
        return $quote->setExtensionAttributes($cartExtension);
    }
}
