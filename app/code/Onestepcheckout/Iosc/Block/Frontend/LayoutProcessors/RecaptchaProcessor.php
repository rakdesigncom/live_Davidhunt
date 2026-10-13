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
use Magento\Store\Model\ScopeInterface;
use Onestepcheckout\Iosc\Helper\Data;

class RecaptchaProcessor implements LayoutProcessorInterface
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

        $shippingEmailCaptcha = $jsLayout['components']['checkout']
                            ['children']['steps']
                            ['children']['shipping-step']
                            ['children']['shippingAddress']
                            ['children']['customer-email']
                            ['children']['recaptcha'] ?? false;
        $billingEmailCaptcha = $jsLayout['components']['checkout']
                                ['children']['steps']
                                ['children']['billing-step']
                                ['children']['payment']
                                ['children']['customer-email']
                                ['children']['recaptcha'] ?? false;

        $placeOrderCaptcha = $jsLayout['components']['checkout']
                                ['children']['steps']
                                ['children']['billing-step']
                                ['children']['payment']
                                ['children']['beforeMethods']
                                ['children']['place-order-recaptcha-container']
                                ['children']['place-order-recaptcha'] ?? false;

        $authRecaptcha  = $jsLayout['components']['checkout']
                            ['children']['authentication']
                            ['children']['recaptcha'] ?? false;

        if ($this->helper->isEnabled()) {

            $scope = ScopeInterface::SCOPE_WEBSITE;
            $enabled = $this->scopeConfig->getValue('recaptcha_frontend/type_for/customer_login') ?? false;

            if ($shippingEmailCaptcha && !$enabled) {
                unset(
                    $jsLayout['components']['checkout']
                    ['children']['steps']
                    ['children']['shipping-step']
                    ['children']['shippingAddress']
                    ['children']['customer-email']
                    ['children']['recaptcha']
                );
            }

            if ($billingEmailCaptcha && !$enabled) {
                unset(
                    $jsLayout['components']['checkout']
                    ['children']['steps']
                    ['children']['billing-step']
                    ['children']['payment']
                    ['children']['customer-email']
                    ['children']['recaptcha']
                );
            }

            if ($authRecaptcha && !$enabled) {
                unset(
                    $jsLayout['components']['checkout']
                    ['children']['authentication']
                    ['children']['recaptcha']
                );
            }

            $enabled = $this->scopeConfig->getValue('recaptcha_frontend/type_for/place_order') ?? false;

            if ($placeOrderCaptcha && !$enabled) {
                unset(
                    $jsLayout['components']['checkout']
                    ['children']['steps']
                    ['children']['billing-step']
                    ['children']['payment']
                    ['children']['beforeMethods']
                    ['children']['place-order-recaptcha-container']
                );
            }

            if ($placeOrderCaptcha && $enabled) {

                $componentPath = $jsLayout['components']['checkout']
                                            ['children']['steps']
                                            ['children']['billing-step']
                                            ['children']['payment']
                                            ['children']['beforeMethods']
                                            ['children']['place-order-recaptcha-container'] ?? false;

                $sidebarPath = $jsLayout['components']['checkout']
                                        ['children']['sidebar']
                                        ['children'] ?? false;

                if ($componentPath && $sidebarPath) {

                    $componentPath['sortOrder'] = 980;

                    $jsLayout['components']['checkout']
                                ['children']['sidebar']
                                ['children']['place-order-recaptcha-container'] = $componentPath;

                    unset(
                        $jsLayout['components']['checkout']
                        ['children']['steps']
                        ['children']['billing-step']
                        ['children']['payment']
                        ['children']['beforeMethods']
                        ['children']['place-order-recaptcha-container']
                    );
                }
            }
        }

        return $jsLayout;
    }
}
