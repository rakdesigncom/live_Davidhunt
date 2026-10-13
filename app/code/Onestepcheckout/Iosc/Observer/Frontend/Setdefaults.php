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

use Exception;
use GeoIp2\Database\Reader;
use Magento\Checkout\Model\Session;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Directory\Model\RegionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DataObject\Copy;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Magento\Framework\Webapi\ServiceOutputProcessor;
use Magento\Payment\Api\PaymentMethodListInterface;
use Magento\Quote\Api\PaymentMethodManagementInterface;
use Magento\Quote\Model\Cart\ShippingMethodConverter;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\TotalsCollector;
use Magento\Store\Model\ScopeInterface;
use Onestepcheckout\Iosc\Helper\Data;
use Onestepcheckout\Iosc\Model\MockManager;
use Magento\Quote\Model\Quote\Address;
use Psr\Log\LoggerInterface;

class Setdefaults implements ObserverInterface
{

    /**
     * @var ScopeConfigInterface|null
     */
    public $scopeConfig = null;
    /**
     * @var MockManager
     */
    protected $mockManager;
    /**
     * @var ShippingMethodConverter
     */
    protected $converter;
    /**
     * @var ServiceOutputProcessor
     */
    protected $serviceOutputProcessor;
    /**
     * @var PaymentMethodManagementInterface
     */
    protected $paymentMethodManagement;
    /**
     * @var DirectoryList
     */
    protected $dir;
    /**
     * @var RemoteAddress
     */
    protected $remoteAddress;
    /**
     * @var RegionFactory
     */
    protected $regionFactory;
    /**
     * @var CustomerSession
     */
    protected $customerSession;
    /**
     * @var Copy
     */
    protected $objectCopyService;
    /**
     * @var PaymentMethodListInterface
     */
    protected $paymentMethodListInterface;
    /**
     * @var LoggerInterface
     */
    protected $log;
    /**
     * @var TotalsCollector
     */
    protected $totalsCollector;
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
     * @param ScopeConfigInterface $scopeConfig
     * @param MockManager $mockManager
     * @param ShippingMethodConverter $converter
     * @param ServiceOutputProcessor $serviceOutputProcessor
     * @param PaymentMethodManagementInterface $paymentMethodManagement
     * @param DirectoryList $dir
     * @param RemoteAddress $remoteAddress
     * @param RegionFactory $regionFactory
     * @param CustomerSession $customerSession
     * @param Copy $objectCopyService
     * @param PaymentMethodListInterface $paymentMethodListInterface
     * @param LoggerInterface $logger
     * @param TotalsCollector $totalsCollector
     * @param Session $checkoutSession
     * @param Data $helper
     */
    public function __construct(
        ScopeConfigInterface             $scopeConfig,
        MockManager                      $mockManager,
        ShippingMethodConverter          $converter,
        ServiceOutputProcessor           $serviceOutputProcessor,
        PaymentMethodManagementInterface $paymentMethodManagement,
        DirectoryList                    $dir,
        RemoteAddress                    $remoteAddress,
        RegionFactory                    $regionFactory,
        CustomerSession                  $customerSession,
        Copy                             $objectCopyService,
        PaymentMethodListInterface       $paymentMethodListInterface,
        LoggerInterface                  $logger,
        TotalsCollector                  $totalsCollector,
        Session                          $checkoutSession,
        Data                             $helper
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->mockManager = $mockManager;
        $this->converter = $converter;
        $this->serviceOutputProcessor = $serviceOutputProcessor;
        $this->paymentMethodManagement = $paymentMethodManagement;
        $this->dir = $dir;
        $this->remoteAddress = $remoteAddress;
        $this->regionFactory = $regionFactory;
        $this->customerSession = $customerSession;
        $this->objectCopyService =  $objectCopyService;
        $this->paymentMethodListInterface = $paymentMethodListInterface;
        $this->log = $logger;
        $this->totalsCollector = $totalsCollector;
        $this->checkoutSession = $checkoutSession;
        $this->helper = $helper;
    }

    /**
     * Add default data to address objects
     *
     * @param Observer $observer
     * @event sales_quote_collect_totals_before
     *
     * @return void
     */
    public function execute(Observer $observer)
    {
        if (!$this->helper->isEnabled()) {
            return;
        }

        $this->callDefaults($observer);
    }

    /**
     * Call defaults method
     *
     * @param Varien_Event_Observer $observer
     */
    private function callDefaults(Observer $observer)
    {
        $this->setAddressDefaults($observer);
        $this->setShippingDefaults($observer);
        $this->setPaymentDefaults($observer);
    }

    /**
     * Add default address data to address objects
     *
     * @param Observer $observer
     * @event sales_quote_collect_totals_before
     *
     * @return void
     */
    private function setAddressDefaults(Observer $observer)
    {
        /** @var Quote $quote */
        $quote = $observer->getEvent()->getQuote();

        /**
         * and if the object of quote is not present or related objects have any data
         * we will return as data is either set by customer or other parts of code
         */
        if (! is_object($quote)) {
            return;
        }

        $billingAddress = $quote->getBillingAddress();
        $shippingAddress = $quote->getShippingAddress();
        if (!$billingAddress->getQuoteId() || !$shippingAddress->getQuoteId()) {

            $scopeStore = ScopeInterface::SCOPE_STORE;
            $baOpen = (int)$this->scopeConfig->getValue('onestepcheckout_iosc/billingfields/open', $scopeStore);
            $baOpenState = $this->checkoutSession->getOscBaOpenState();
            if ($baOpenState === null) {
                $this->checkoutSession->setOscBaOpenState($baOpen);
            }
            if (!empty($quote->getCustomer()) && !empty($quote->getCustomer()->getAddresses())) {
                $customer = $this->customerSession->getCustomer();

                if (!$billingAddress->getQuoteId()) {
                    $this->copyAddressData($customer->getPrimaryBillingAddress(), $billingAddress);
                }

                if (!$shippingAddress->getQuoteId()) {
                    $this->copyAddressData($customer->getPrimaryShippingAddress(), $shippingAddress);
                }
            } else { // guest or without addresses

                /**
                 * get the defaults from store config
                 */
                $shippingConfig = $this->helper->getConfig([], [
                    'shippingfields'
                ], 'shippingfields');
                $billingConfig = $this->helper->getConfig([], [
                    'billingfields'
                ], 'billingfields');

                if (!$shippingAddress->getQuoteId()) {
                    $currentShipping = [];
                    if ($this->scopeConfig->getValue('onestepcheckout_iosc/geoip/enabled', $scopeStore)) {
                        $currentShipping = $this->getGeoIp2($quote);
                    }

                    if (empty($currentShipping)) {
                        $currentShipping = $this->_hasDataSet($shippingAddress, $shippingConfig);
                    }

                    if (!empty($currentShipping)) {
                        $shippingAddress->addData($currentShipping);
                    }
                }

                if (!$billingAddress->getQuoteId()) {
                    $currentBilling = $this->_hasDataSet($billingAddress, $billingConfig);
                    if (!empty($currentBilling)) {
                        $billingAddress->addData($currentBilling);
                    }
                }
            }
        }
    }

    /**
     * Copy Address Data
     *
     * @param $source
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

    /**
     * Set Shipping defaults
     *
     * @param Observer $observer
     * @return void|\Onestepcheckout\Iosc\Observer\Frontend\Setdefaults
     */
    public function setShippingDefaults(Observer $observer)
    {

        /** @var Quote $quote */
        $quote = $observer->getEvent()->getQuote();

        /**
         * and if the object of quote is not present or related objects have any data
         * we will return as data is either set by customer or other parts of code
         */
        if (! is_object($quote)) {
            return;
        }

        $oldCode = $quote->getShippingAddress()->getShippingMethod();
        if (!empty($oldCode)) {
            return;
        }
        $quote->setIoscAutoshippingMethod("");

        $scopeStore = ScopeInterface::SCOPE_STORE;
        $newCode = $this->scopeConfig
            ->getValue('onestepcheckout_iosc/shipping/default', $scopeStore);
        $freeAvailable = $this->scopeConfig
            ->getValue('onestepcheckout_iosc/shipping/freeifavailable', $scopeStore);
        $defaultIfOne = $this->scopeConfig
            ->getValue('onestepcheckout_iosc/shipping/defaultifone', $scopeStore);

        if (empty($newCode) && !$freeAvailable && !$defaultIfOne) {
            return;
        }

        $ratesData = $this
            ->getShippingRates($quote, $this->converter, $this->serviceOutputProcessor);
        $codes = [];
        $lastZeroRate = false;

        foreach ($ratesData as $rates) {
            $rateCode = $rates['carrier_code'] . '_' . $rates['method_code'];
            $codes[] = $rateCode;
            if (isset($rates['amount']) && $rates['amount'] == "0") {
                $lastZeroRate = $rateCode;
            }
        }

        if (empty($codes)) {
            return;
        }
        $tablerate = array_search('tablerate_', $codes);
        if (isset($tablerate) && $tablerate && $tablerate !== null) {
            $codes[$tablerate] = 'tablerate_bestway';
        }

        $codeCount = (int)count($codes);
        $freeshippingCode = 'freeshipping_freeshipping';
        $isFreeShipping = in_array($freeshippingCode, $codes);
        if ($lastZeroRate && !$isFreeShipping) {
            $freeshippingCode = in_array($lastZeroRate, $codes) ? $lastZeroRate : $freeshippingCode;
            $isFreeShipping = ($freeshippingCode == $lastZeroRate) ? true : false;
        }
        if ($isFreeShipping && $newCode != $freeshippingCode && $freeAvailable) {
            $newCode = $freeshippingCode;
        } else {
            if ($codeCount === 1 && $defaultIfOne) {
                $newCode = current($codes);
            }
        }
        if ($quote->getIoscAutoshipping() && $codeCount !== 1) {
            return;
        }

        if (!empty($codes) &&
            (empty($oldCode) || !in_array($oldCode, $codes))
        ) {
            if (in_array($newCode, $codes)) {
                $quote->getShippingAddress()->setShippingMethod($newCode);
                $quote->setIoscAutoshippingMethod($newCode);
            }
        }
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
        if ($quote->getOscCollectRates() !== "1") {
            $shippingAddress->setCollectShippingRates(true);
        } else {
            $shippingAddress->setCollectShippingRates(false);
        }

        $this->totalsCollector->collectAddressTotals($quote, $shippingAddress);

        $shippingRates = $shippingAddress->getGroupedAllShippingRates();

        foreach ($shippingRates as $carrierRates) {
            foreach ($carrierRates as $rate) {
                $rates[] = $converter->modelToDataObject($rate, $quote->getQuoteCurrencyCode());
            }
        }
        $rates = $serviceOutputProcessor
            ->convertValue($rates, '\Magento\Quote\Api\Data\ShippingMethodInterface[]');

        return $rates;
    }

    /**
     * Set default payment method for the user
     *
     * @param Varien_Event_Observer $observer
     * @return Onestepcheckout_OneStepCheckout_Model_Observers_PresetDefaults
     */
    private function setPaymentDefaults(Observer $observer)
    {
        /** @var Quote $quote */
        $quote = $observer->getEvent()->getQuote();

        $scopeStore = ScopeInterface::SCOPE_STORE;
        $newCode = $this->scopeConfig->getValue('onestepcheckout_iosc/payments/methods', $scopeStore);
        if (empty($newCode) || !is_object($quote)) {
            return;
        }
        $oldCode = $quote->getPayment()->getMethod();
        if ($oldCode == $newCode) {
            return;
        }

        $storeId = $quote->getStoreId();
        $codes = $this->getPaymentMethodCodes($this->paymentMethodListInterface->getActiveList($storeId));
        if (empty($codes)) {
            return;
        }

        $codeCount = (int)count($codes);
        if ($codeCount === 1 && current($codes) !='free') {
            $newCode = current($codes);
        }
        if (!empty($codes) && (empty($oldCode) || !in_array($oldCode, $codes))) {
            if (in_array($newCode, $codes)) {
                if ($quote->isVirtual()) {
                    $quote->getBillingAddress()->setPaymentMethod($newCode);
                } else {
                    $quote->getShippingAddress()->setPaymentMethod($newCode);
                }

                try {
                    $quote->getPayment()->setQuote($quote)
                        ->setMethod($newCode)->getMethodInstance();
                    // phpcs:ignore Magento2.CodeAnalysis.EmptyBlock.DetectedCatch
                } catch (Exception $e) {
                    /**
                     * log if needed
                     */
                }
            }
        }
    }

    /**
     * Get GeoIp2 data
     *
     * @return array
     */
    private function getGeoIp2()
    {
        $data = [];

        $scopeStore = ScopeInterface::SCOPE_STORE;
        $database = $this->dir
        ->getRoot() .
            '/' .
            str_replace(
                '../',
                '/',
                $this->scopeConfig->getValue('onestepcheckout_iosc/geoip/dbpath', $scopeStore)
            );

        try {
            $reader = new  Reader($database);
            if (is_object($reader) && method_exists($reader, 'city')) {
                $record = $reader->city($this->remoteAddress->getRemoteAddress());

                if (! empty($record->country->isoCode)) {
                    $data['country_id'] = $record->country->isoCode;
                }

                if (! empty($record->mostSpecificSubdivision->isoCode)) {
                    $region = $this->regionFactory->create();
                    $data['region_id'] = $region
                        ->loadByName(
                            $record->mostSpecificSubdivision->names['en'],
                            $record->country->isoCode
                        )->getRegionId();
                }

                if (! empty($record->city) && $record->city != '') {
                    $data['city'] = mb_convert_encoding($record->city->name, 'UTF-8');
                }

                if (! empty($record->postal->code)) {
                    $data['postcode'] = $record->postal->code;
                }
            } else {
                $this->log
                    ->system('GeoIp2 database %s is not installed properly or is
                            inaccessible or region information for ip: %s not found');
            }
        } catch (Exception $e) {
            $this->log->critical($e);
            return $data;
        }

        return $data;
    }

    /**
     * Get PaymentMethodCodes
     *
     * @param array $methods
     * @return array []
     */
    private function getPaymentMethodCodes($methods)
    {
        $codes = [];
        foreach ($methods as $method) {
            $codes[] = $method->getCode();
        }
        return $codes;
    }

    /**
     * Check if object has values or default values set
     *
     * @param Address $address
     * @param array $defaults
     * @return array();
     */
    private function _hasDataSet(Address $address, array $defaults = []): array
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
}
