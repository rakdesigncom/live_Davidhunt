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

class AuthProcessor implements LayoutProcessorInterface
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
     * @var Session
     */
    protected $customerSession;

    /**
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param Data $helper
     * @param Session $customerSession
     */
    public function __construct(
        ScopeConfigInterface            $scopeConfig,
        Data                            $helper,
        Session $customerSession
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->helper = $helper;
        $this->customerSession = $customerSession;
    }

    /**
     * @inheritdoc
     */
    public function process($jsLayout)
    {

        if ($this->helper->isEnabled()) {
            $enabled = $this->scopeConfig->getValue(
                'onestepcheckout_iosc/registration/showlogin',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );

            if (!$enabled) {
                unset(
                    $jsLayout['components']['checkout']
                        ['children']['authentication']
                );
            }
            $enabled = $this->scopeConfig->getValue(
                'onestepcheckout_iosc/registration/optionalpwd',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
            $requiredPwd = $this->scopeConfig->getValue(
                'onestepcheckout_iosc/registration/requiredpwd',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );

            $isLoggedIn = $this->customerSession->getId() ?? false;
            $isEmulatedLogin = $this->customerSession->getIsCustomerEmulated() ?? false;

            $layoutPath = $jsLayout['components']['checkout']
                            ['children']['sidebar']
                            ['children']['registration-fields'] ?? false ;

            if ((!$isLoggedIn || $isEmulatedLogin) &&
                ($enabled || $requiredPwd) &&
                $layoutPath
            ) {
                $fields = $layoutPath;
                $pwdLength = $this->scopeConfig
                    ->getValue(\Magento\Customer\Model\AccountManagement::XML_PATH_MINIMUM_PASSWORD_LENGTH);
                $pwdComplexity = $this->scopeConfig
                    ->getValue(\Magento\Customer\Model\AccountManagement::XML_PATH_REQUIRED_CHARACTER_CLASSES_NUMBER);
                $fields['pwdrules']['password-min-length'] = (int)$pwdLength;
                $fields['pwdrules']['password-min-character-sets'] = (int)$pwdComplexity;

                $registration = $jsLayout['components']['checkout']
                                    ['children']['iosc']
                                    ['children']['registration'];

                $regFields = array_merge($fields, $registration);

                $jsLayout['components']['checkout']
                    ['children']['sidebar']
                    ['children']['registration-fields'] = $regFields;

                unset(
                    $jsLayout['components']['checkout']
                        ['children']['iosc']
                        ['children']['registration']
                );
            } elseif ($layoutPath) {
                unset(
                    $jsLayout['components']['checkout']
                        ['children']['sidebar']
                        ['children']['registration-fields']
                );
            }
        }

        return $jsLayout;
    }
}
