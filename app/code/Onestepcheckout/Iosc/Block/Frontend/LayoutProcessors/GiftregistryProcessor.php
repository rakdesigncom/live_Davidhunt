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
use Onestepcheckout\Iosc\Helper\Data;

class GiftregistryProcessor implements LayoutProcessorInterface
{
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
        if ($this->helper->isEnabled()) {

            $isLoggedIn = $this->customerSession->getId();
            if (! $isLoggedIn) {
                $layoutPath = $jsLayout['components']['checkout']
                                ['children']['steps']
                                ['children']['shipping-step']
                                ['children']['gift-registry-address-provider'] ?? false;

                if ($layoutPath) {
                    unset(
                        $jsLayout['components']['checkout']
                            ['children']['steps']
                            ['children']['shipping-step']
                            ['children']['gift-registry-address-provider']
                    );
                    unset(
                        $jsLayout['components']['checkout']
                            ['children']['steps']
                            ['children']['shipping-step']
                            ['children']['shippingAddress']
                            ['children']['address-list']
                            ['config']['deps']
                    );
                    unset(
                        $jsLayout['components']['checkout']
                            ['children']['steps']
                            ['children']['shipping-step']
                            ['children']['shippingAddress']
                            ['children']['address-list']
                            ['rendererTemplates']['gift-registry']
                    );
                }
            }
        }

        return $jsLayout;
    }
}
