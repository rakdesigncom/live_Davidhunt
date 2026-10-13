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
use Magento\Customer\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Onestepcheckout\Iosc\Helper\Data;

class PickupProcessor implements LayoutProcessorInterface
{
    public const SP_ENABLED = 'carriers/instore/active';
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;
    /**
     * @var Session
     */
    protected $customerSession;
    /**
     * @var Data
     */
    protected $helper;

    /**
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param Session $customerSession
     * @param Data $helper
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Session $customerSession,
        Data $helper
    ) {

        $this->scopeConfig = $scopeConfig;
        $this->customerSession = $customerSession;
        $this->helper = $helper;
    }

    /**
     * @inheritdoc
     */
    public function process($jsLayout)
    {
        $configKey = 'store-pickup';
        $isLoggedIn = $this->customerSession->getId() ?? false;
        $layoutPath = $jsLayout['components']['checkout']
                                ['children']['steps']
                                ['children'][$configKey] ?? false;
        $spEnabled = (bool)$this->scopeConfig
                        ->getValue(
                            self::SP_ENABLED,
                            ScopeInterface::SCOPE_WEBSITE
                        ) ?? false;

        if ($this->helper->isEnabled() && $layoutPath && $spEnabled) {
            unset(
                $jsLayout['components']['checkout']
                        ['children']['steps']
                        ['children'][$configKey]
            );

            unset($layoutPath['children']['store-selector']['children']['customer-email']);

            $layoutPath['component'] = 'Onestepcheckout_Iosc/js/pickup';

            if ($isLoggedIn) {
                $layoutPath['displayArea'] = 'customer-email';
                $jsLayout['components']['checkout']
                    ['children']['steps']
                    ['children']['shipping-step']
                    ['children']['shippingAddress']
                    ['children'][$configKey] = $layoutPath;
            } else {
                $jsLayout['components']['checkout']
                    ['children']['steps']
                    ['children']['shipping-step']
                    ['children']['shippingAddress']
                    ['children']['before-fields']
                    ['children'][$configKey] = $layoutPath;
            }
        }

        return $jsLayout;
    }
}
