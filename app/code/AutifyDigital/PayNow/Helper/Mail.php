<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace AutifyDigital\PayNow\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\ScopeInterface;

class Mail extends AbstractHelper
{
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Framework\Mail\Template\TransportBuilder
     */
    protected $transportBuilder;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->transportBuilder = $transportBuilder;
        $this->storeManager = $storeManager;
        parent::__construct($context);
    }

    /**
     * @param string $template configuration path of email template
     * @param string $sender configuration path of email identity
     * @param array $to email and name of the receiver
     * @param array $templateParams
     * @param int|null $storeId
     * @throws \Magento\Framework\Exception\MailException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function sendEmailTemplate(
        $template,
        $sender,
        $to = [],
        $templateParams = [],
        $storeId = null
    ) {
        if (!isset($to['email']) || empty($to['email'])) {
            throw new LocalizedException(
                __('We could not send the email because the receiver data is invalid.')
            );
        }
        $storeId = $storeId ? $storeId : $this->storeManager->getStore()->getId();
        $name = isset($to['name']) ? $to['name'] : '';

        /** @var \Magento\Framework\Mail\TransportInterface $transport */
        $transport = $this->transportBuilder->setTemplateIdentifier(
            $this->scopeConfig->getValue($template, ScopeInterface::SCOPE_STORE, $storeId)
        )->setTemplateOptions(
            ['area' => \Magento\Framework\App\Area::AREA_FRONTEND, 'store' => $storeId]
        )->setTemplateVars(
            $templateParams
        )->setFromByScope(
            array(
                'email' => $sender['email'],
                'name' => $sender['name']
            )
        )->addTo(
            $to['email'],
            $name
        )->getTransport();
        $transport->sendMessage();
    }

    /**
     * Send the SuccessTransactionCustomer Email
     */
    public function sendSuccessTransactionCustomerEmail(
        $sender,
        $to = ['email' => '', 'name' => ''],
        $transactionData = []
    ) {
        $this->sendEmailTemplate(
            'paynow/email_configuration/success_transaction_customer',
            $sender,
            $to,
            $transactionData
        );
    }

    /**
     * Send the FailedTransactionCustomer Email
     */
    public function sendFailedTransactionCustomerEmail(
        $sender = 'example@example.com',
        $to = ['email' => '', 'name' => ''],
        $transactionData = []
    ) {
        $this->sendEmailTemplate(
            'paynow/email_configuration/failed_transaction_customer',
            $sender,
            $to,
            $transactionData
        );
    }

    /**
     * Send the SuccessTransactionAdmin Email
     */
    public function sendSuccessTransactionAdminEmail(
        $sender = 'example@example.com',
        $to = ['email' => '', 'name' => ''],
        $transactionData = []
    ) {
        $this->sendEmailTemplate(
            'paynow/email_configuration/success_transaction_admin',
            $sender,
            $to,
            $transactionData
        );
    }

    /**
     * Send the FailedTransactionAdmin Email
     */
    public function sendFailedTransactionAdminEmail(
        $sender = 'example@example.com',
        $to = ['email' => '', 'name' => ''],
        $transactionData = []
    ) {
        $this->sendEmailTemplate(
            'paynow/email_configuration/failed_transaction_admin',
            $sender,
            $to,
            $transactionData
        );
    }
}

