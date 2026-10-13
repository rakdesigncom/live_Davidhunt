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

use Magento\Checkout\Model\Session;
use Magento\Framework\Stdlib\DateTime\Filter\Date;
use Onestepcheckout\Iosc\Helper\Data;

class DobManager
{
    /**
     * @var Data
     */
    protected $helper;
    /**
     * @var Date
     */
    protected $dateFilter;
    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     *
     * @param Session $checkoutSession
     * @param Date $dateFilter
     * @param Data $helper
     */
    public function __construct(
        Session $checkoutSession,
        Date $dateFilter,
        Data $helper
    ) {

        $this->helper = $helper;
        $this->dateFilter = $dateFilter;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * Soft set dob value to customer_dob
     *
     * @param $parent
     * @param $value
     */
    public function afterSetDob(
        $parent,
        $value
    ) {

        if (! $this->helper->isEnabled()) {
            return;
        }

        $isSet = $this->checkoutSession->getQuote()->getCustomer()->getDob();
        if (!$isSet) {
            $value = ($parent->getDob()) ? $this->dateFilter->filter($parent->getDob()) : '' ;
            $this->checkoutSession->getQuote()->setCustomerDob($value);
        }
    }
}
