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

use Exception;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Escaper;
use Magento\Quote\Model\Quote;
use Onestepcheckout\Iosc\Helper\Data;
use Onestepcheckout\Iosc\Model\Extend\AccountManagement;

class Registration implements OutputManagementInterface
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
     * @var AccountManagement
     */
    protected $accountManagement;

    /**
     * Get outputKey
     *
     * @return string
     */
    public function getOutputKey()
    {
        return 'registration';
    }

    /**
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param Data $helper
     * @param Escaper $escaper
     * @param Session $checkoutSession
     * @param AccountManagement $accountManagement
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Data $helper,
        Escaper $escaper,
        Session $checkoutSession,
        AccountManagement $accountManagement
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->helper = $helper;
        $this->escaper = $escaper;
        $this->checkoutSession = $checkoutSession;
        $this->accountManagement = $accountManagement;
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
            $failure = false;
            $quote->setIoscRegistered(0);
            if (!empty($input['iosc-register-pwd']) &&
                !empty($input['iosc-register-pwd-confirm']) &&
                $input['iosc-register-pwd'] ==
                $input['iosc-register-pwd-confirm']
            ) {
                $pwdCandidate = trim($input['iosc-register-pwd']);
                try {
                    $this->accountManagement->checkPasswordStrength($pwdCandidate);
                } catch (Exception $e) {
                    $failure = true;
                }

                if (!$failure) {
                    $pwdCandidateHash = $this->accountManagement->getPasswordHash($pwdCandidate);
                    $quote->setPasswordHash($pwdCandidateHash);
                    $quote->setIoscRegistered(1);
                } else {
                    $quote->setPasswordHash('');
                    $quote->setIoscRegistered(0);
                }
            } else {
                $quote->setPasswordHash('');
            }
            $quote->save();
        }
        return $data;
    }
}
