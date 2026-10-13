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
use Magento\Quote\Model\Quote;
use Onestepcheckout\Iosc\Helper\Data;

class Comment implements OutputManagementInterface
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
     * Get outputKey
     *
     * @return string
     */
    public function getOutputKey()
    {
        return 'customerNote';
    }

    /**
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param Data $helper
     * @param Escaper $escaper
     * @param Session $checkoutSession
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Data $helper,
        Escaper $escaper,
        Session $checkoutSession
    ) {

        $this->scopeConfig = $scopeConfig;
        $this->helper = $helper;
        $this->escaper = $escaper;
        $this->checkoutSession = $checkoutSession;
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

        $response = false;

        if ($quote->getId()) {
            $input = (isset($input[$this->getOutputKey()])) ? $input[$this->getOutputKey()] : false;

            if ($input) {
                $input = $this->escaper->escapeHtml(current($input));
                $quote->setCustomerNote($input);
                $quote->setCustomerNoteNotify(0);
                $response = true;
            }

            $quote->save();
        }
        $data = $response;
        return $data;
    }
}
