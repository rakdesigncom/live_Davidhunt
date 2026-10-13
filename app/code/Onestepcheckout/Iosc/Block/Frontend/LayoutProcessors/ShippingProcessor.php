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
namespace Onestepcheckout\Iosc\Block\Frontend\LayoutProcessors;

use Magento\Checkout\Block\Checkout\LayoutProcessorInterface;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Webapi\ServiceOutputProcessor;
use Magento\Quote\Api\ShipmentEstimationInterface;
use Magento\Quote\Model\Cart\ShippingMethodConverter;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\TotalsCollector;
use Onestepcheckout\Iosc\Helper\Data;

class ShippingProcessor implements LayoutProcessorInterface
{
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;
    /**
     * @var Session
     */
    protected $checkoutSession;
    /**
     * @var ShipmentEstimationInterface
     */
    protected $shippingMethodManagement;
    /**
     * @var ShippingMethodConverter
     */
    protected $converter;
    /**
     * @var ServiceOutputProcessor
     */
    protected $serviceOutputProcessor;
    /**
     * @var TotalsCollector
     */
    protected $totalsCollector;
    /**
     * @var Data
     */
    protected $helper;

    /**
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param Session $checkoutSession
     * @param ShippingMethodConverter $converter
     * @param ShipmentEstimationInterface $shippingMethodManagement
     * @param ServiceOutputProcessor $serviceOutputProcessor
     * @param TotalsCollector $totalsCollector
     * @param Data $helper
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Session $checkoutSession,
        ShippingMethodConverter $converter,
        ShipmentEstimationInterface $shippingMethodManagement,
        ServiceOutputProcessor $serviceOutputProcessor,
        TotalsCollector $totalsCollector,
        Data $helper
    ) {

        $this->scopeConfig = $scopeConfig;
        $this->checkoutSession = $checkoutSession;
        $this->shippingMethodManagement = $shippingMethodManagement;
        $this->converter = $converter;
        $this->serviceOutputProcessor = $serviceOutputProcessor;
        $this->totalsCollector = $totalsCollector;
        $this->helper = $helper;
    }

    /**
     * @inheritdoc
     */
    public function process($jsLayout)
    {

        if ($this->helper->isEnabled()) {
            $shippingMethods = $this->getShippingRates(
                $this->checkoutSession->getQuote(),
                $this->converter,
                $this->serviceOutputProcessor
            );
            $jsLayout['components']['checkout']
                ['children']['iosc']
                ['children']['shipping']
                ['cnf']['availableRates'] = $shippingMethods;
        }

        return $jsLayout;
    }

    /**
     * Get available shipping rates
     *
     * @param Quote $quote
     * @param ShippingMethodConverter $converter
     * @param ServiceOutputProcessor $serviceOutputProcessor
     * @return array
     */
    public function getShippingRates(
        Quote $quote,
        ShippingMethodConverter $converter,
        ServiceOutputProcessor $serviceOutputProcessor
    ) {
        $rates = [];

        $shippingAddress = $quote->getShippingAddress();

        $shippingAddress->setCollectShippingRates(true);
        $this->totalsCollector->collectAddressTotals($quote, $shippingAddress);

        $shippingRates = $shippingAddress->getGroupedAllShippingRates();

        foreach ($shippingRates as $carrierRates) {
            foreach ($carrierRates as $rate) {
                $rates[] = $converter->modelToDataObject($rate, $quote->getQuoteCurrencyCode());
            }
        }
        $rates = $serviceOutputProcessor->convertValue($rates, '\Magento\Quote\Api\Data\ShippingMethodInterface[]');

        return $rates;
    }
}
