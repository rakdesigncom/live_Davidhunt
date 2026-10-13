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
use Magento\Framework\Escaper;
use Magento\Framework\Validator\EmailAddress;
use Magento\Quote\Model\Quote;
use Onestepcheckout\Iosc\Helper\Data;

/**
 * Email implements OutputManagementInterface
 */
class Email implements OutputManagementInterface
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
     * @var Escaper
     */
    protected $escaper;
    /**
     * @var Session
     */
    protected $checkoutSession;
    /**
     * @var EmailAddress
     */
    protected $emailAddressValidator;

    /**
     * Get outputKey
     *
     * @return string
     */
    public function getOutputKey()
    {
        return 'customerEmail';
    }

    /**
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param Data $helper
     * @param Escaper $escaper
     * @param Session $checkoutSession
     * @param EmailAddress $emailAddress
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Data $helper,
        Escaper $escaper,
        Session $checkoutSession,
        EmailAddress $emailAddress
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->helper = $helper;
        $this->escaper = $escaper;
        $this->checkoutSession = $checkoutSession;
        $this->emailAddressValidator = $emailAddress;
    }

    /**
     * @inheritdoc
     *
     * @see \Onestepcheckout\Iosc\Model\Input\InputManagement::processPayload()
     */
    public function processPayload($input)
    {

        $data = [];

        /** @var Quote $quote */
        $quote = $this->checkoutSession->getQuote();

        if ($quote->getId()) {
            $input = (isset($input[$this->getOutputKey()])) ? $input[$this->getOutputKey()] : false;

            if ($input && $this->emailAddressValidator->isValid($input)) {
                $input = $this->escaper->escapeHtml($input);
                $quote->setCustomerEmail($input);
                $quote->getBillingAddress()->setEmail($input);
                $quote->getShippingAddress()->setEmail($input);
                $quote->save();
            }
        }
        return $data;
    }
}
