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
namespace Onestepcheckout\Iosc\Plugin\Adminhtml;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Onestepcheckout\Iosc\Helper\Data;

class IsGuestOrder
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
    public function beforeGetCustomerViewUrl($subject)
    {
        if ($this->helper->isEnabled()) {
            if ($subject->getOrder()->getCustomerIsGuest() &&
                $subject->getOrder()->getCustomerId()
            ) {
                $subject->getOrder()->setCustomerIsGuest(false);
                $subject->getOrder()->setCustomerIsGuestManipulated(true);
            }
        }

        return null;
    }

    /**
     * @inheritdoc
     */
    public function afterGetCustomerViewUrl($subject, $result)
    {
        if ($this->helper->isEnabled()) {
            if ($subject->getOrder()->setCustomerIsGuestManipulated() === true) {
                $subject->getOrder()->setCustomerIsGuest(true);
            }
        }

        return $result;
    }
}
