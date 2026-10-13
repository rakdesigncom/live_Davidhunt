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

use Magento\Authorization\Model\UserContextInterface;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Quote\Api\Data\CartInterface;
use Onestepcheckout\Iosc\Helper\Data;

class PersistentUserIsAllowed
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
     * @var UserContextInterface
     */
    protected $userContext;
    /**
     * @var Data
     */
    protected $helper;

    /**
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param Session $checkoutSession
     * @param UserContextInterface $userContext
     * @param Data $helper
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Session $checkoutSession,
        UserContextInterface $userContext,
        Data $helper
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->checkoutSession = $checkoutSession;
        $this->userContext = $userContext;
        $this->helper = $helper;
    }

     /**
      * @inheritdoc
      */
    public function beforeIsAllowed($subject, CartInterface $quote)
    {
        if ($this->helper->isEnabled()) {
            if ($quote->getCustomerId() &&
                !$this->userContext->getUserId() &&
                $this->userContext->getUserId() != $quote->getCustomerId()
            ) {

                $quote->setCustomerId(null);
                $quote->setCustomerIsGuest(true);
                $quote->setCustomerEmail(null);
                $quote->setCustomerGroupId(0);
                $quote->setCustomerFirstname(null);
                $quote->setCustomerLastname(null);
                $quote->setCustomerDob(null);
                $quote->setCustomerGender(null);
                $quote->setCustomerTaxvat(null);
                $quote->setCheckoutMethod('guest');

                foreach ($quote->getAllAddresses() as $address) {
                    $address->setCustomerId(null);
                    $address->setCustomerAddressId(null);
                }
            }

        }

        return [$quote];
    }
}
