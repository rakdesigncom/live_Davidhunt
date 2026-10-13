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
namespace Onestepcheckout\Iosc\Model\Output;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Quote\Model\Quote;

/**
 * SelectedShippingMethod implements OutputManagementInterface
 */
class SelectedShippingMethod implements OutputManagementInterface
{

    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     * Get outputKey
     *
     * @return string
     */
    public function getOutputKey()
    {
        return 'selectedShippingMethod';
    }

    /**
     * @var ScopeConfigInterface
     */
    public $scopeConfig = null;

    /**
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param Session $checkoutSession
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Session $checkoutSession
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * @inheritdoc
     *
     * @see \Onestepcheckout\Iosc\Model\Input\InputManagement::processPayload()
     */
    public function processPayload($input)
    {

        /** @var Quote $quote */
        $quote = $this->checkoutSession->getQuote();

        $data = $quote->getShippingAddress()->getShippingMethod();

        return $data;
    }
}
