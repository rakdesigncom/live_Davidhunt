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

class EmailProcessor implements LayoutProcessorInterface
{
    /**
     * @var Data
     */
    private $helper;
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

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

        if ($this->helper->isEnabled()) {
            $scopeStore = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
            if ($this->scopeConfig
                    ->getValue(
                        'onestepcheckout_iosc/shippingfields/separateemail',
                        $scopeStore
                    )
            ) {
                $layoutPath = $jsLayout['components']['checkout']
                    ['children']['steps']
                    ['children']['shipping-step']
                    ['children']['shippingAddress']
                    ['children']['customer-email'];

                unset(
                    $jsLayout['components']['checkout']
                        ['children']['steps']
                        ['children']['shipping-step']
                        ['children']['shippingAddress']
                        ['children']['customer-email']
                );
                $jsLayout['components']['checkout']
                    ['children']['steps']
                    ['children']['shipping-step']
                    ['children']['shippingAddress']
                    ['children']['shipping-address-fieldset']
                    ['children']['email'] = $layoutPath;
            }
        }

        return $jsLayout;
    }
}
