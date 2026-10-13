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
use Magento\Framework\App\Config\ScopeConfigInterface;
use Onestepcheckout\Iosc\Helper\Data;

class AmazonPayProcessor implements LayoutProcessorInterface
{
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var Data
     */
    protected $helper;

    /**
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param Data $helper
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Data $helper
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->helper = $helper;
    }

    /**
     * @inheritdoc
     */
    public function process($jsLayout)
    {

        if (!$this->helper->isEnabled()) {
            return $jsLayout;
        }

        $configKey = 'iosc_amazonpay';
        $component = 'Onestepcheckout_Iosc/js/view/payment/amazonpay';
        $componentv2 = 'Onestepcheckout_Iosc/js/view/payment/amazonpay_v2';
        $paymentIsactive = $this
                            ->scopeConfig
                            ->getValue(
                                'payment/amazon_payment_v2/active',
                                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                            ) ?? false;

        $layoutPath = $jsLayout['components']['checkout']
                        ['children']['steps']
                        ['children']['shipping-step']
                        ['children']['shippingAddress']
                        ['children']['before-form']
                        ['children']['amazon-widget-address'] ?? false;
        if (!$layoutPath && $paymentIsactive) {
            $layoutPath = $jsLayout['components']['checkout']
                            ['children']['steps']
                            ['children']['shipping-step']
                            ['children']['shippingAddress']
                            ['children']['before-form']
                            ['children']['amazon-pay-address'] ?? false;
            if ($layoutPath) {
                $component = $componentv2;
            }
        }

        if ($layoutPath) {
            $jsLayout['components']['checkout']
                ['children']['steps']
                ['children']['shipping-step']
                ['children']['shippingAddress']
                ['children']['before-form']
                ['children'][$configKey] = ['component' => $component ];
        } else {
            $listComponent = $jsLayout['components']['checkout']
                                ['children']['steps']
                                ['children']['shipping-step']
                                ['children']['shippingAddress']
                                ['children']['address-list']['component'] ?? false;

            if ($listComponent &&
                (
                    $listComponent == "Amazon_Payment/js/view/shipping-address/list" ||
                    $listComponent == "Amazon_Pay/js/view/shipping-address/list"
                )
            ) {
                 $jsLayout['components']['checkout']
                    ['children']['steps']
                    ['children']['shipping-step']
                    ['children']['shippingAddress']
                    ['children']['address-list']['component']
                    = "Magento_Checkout/js/view/shipping-address/list";
            }
        }

        return $jsLayout;
    }
}
