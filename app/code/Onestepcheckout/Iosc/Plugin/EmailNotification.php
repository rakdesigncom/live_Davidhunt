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
namespace Onestepcheckout\Iosc\Plugin;

use Closure;
use Magento\Checkout\Model\Session;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\EmailNotificationInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Onestepcheckout\Iosc\Helper\Data;

class EmailNotification
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
     * @var Data
     */
    protected $helper;

    /**
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param Session $checkoutSession
     * @param Data $helper
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Session $checkoutSession,
        Data $helper
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->checkoutSession = $checkoutSession;
        $this->helper = $helper;
    }

     /**
      * @inheritdoc
      */
    public function beforeNewAccount(
        $parent,
        CustomerInterface $customer,
        $type = EmailNotificationInterface::NEW_ACCOUNT_EMAIL_REGISTERED,
        $backUrl = '',
        $storeId = 0,
        $sendemailStoreId = null
    ) {
        if ($this->helper->isEnabled()) {
            $autoRegister = $this->scopeConfig
                ->getValue(
                    'onestepcheckout_iosc/registration/autoregister',
                    ScopeInterface::SCOPE_STORE
                );
            if ((int)$autoRegister === 1 &&
                $this->checkoutSession->getQuote()->getIoscRegistered() == "2"
            ) {
                $type = EmailNotificationInterface::NEW_ACCOUNT_EMAIL_REGISTERED_NO_PASSWORD;
            }
        }

        return [$customer, $type, $backUrl, $storeId, $sendemailStoreId];
    }

    /**
     * @inheritdoc
     */
    public function aroundNewAccount(
        \Magento\Customer\Model\EmailNotification $subject,
        Closure $proceed,
        CustomerInterface $customer,
        $type = EmailNotificationInterface::NEW_ACCOUNT_EMAIL_REGISTERED,
        $backUrl = '',
        $storeId = 0,
        $sendemailStoreId = null
    ) {
        if ($this->helper->isEnabled()) {
            $skipEmail = $this->scopeConfig
            ->getValue('onestepcheckout_iosc/registration/skipemail', ScopeInterface::SCOPE_STORE);
            if ((int)$skipEmail === 1 &&
                (int)$this->checkoutSession->getQuote()->getIoscRegistered() >= 1
            ) {
                return;
            }
        }

        return  $proceed($customer, $type, $backUrl, $storeId, $sendemailStoreId);
    }
}
