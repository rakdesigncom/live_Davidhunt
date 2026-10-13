<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace AutifyDigital\PayNow\Controller\Index;

use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\InvalidRequestException;

class PaymentWebhook extends \Magento\Framework\App\Action\Action implements CsrfAwareActionInterface
{

    /**
     * @var JsonFactory
     */
    protected $jsonResultFactory;

    /**
     * @var \AutifyDigital\PayNow\Helper\Data
     */
    protected $helper;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\Controller\Result\JsonFactory $jsonResultFactory
     * @param \AutifyDigital\PayNow\Helper\Data $helper
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $jsonResultFactory,
        \AutifyDigital\PayNow\Helper\Data $helper
    ) {
        parent::__construct($context);
        $this->jsonResultFactory = $jsonResultFactory;
        $this->helper = $helper;
    }

    /**
     * Execute view action
     *
     * @return ResultInterface
     */
    public function execute()
    {
        $this->helper->addLog("WebHOOK REQUEST PAYMENT START");
        $this->helper->addLog($this->getRequest()->getParams(), true);
        /** @var \Magento\Framework\Controller\Result\Json $result */
        $result = $this->jsonResultFactory->create();

        try{
            if($this->getRequest()->getParam('transaction_id') && $this->getRequest()->getParam('notification_hash')) {
                $notificationHash = $this->getRequest()->getParam('notification_hash');
                $transactionId = $this->getRequest()->getParam('transaction_id');

                $paymentModel = $this->helper->getTransactionFactory()->getCollection()->addFieldToFilter("merchant_transaction_id", array("eq" => $transactionId))->getFirstItem();

                if($paymentModel) {

                    $transactionTime = (null !== $this->getRequest()->getParam('txndatetime'))?$this->getRequest()->getParam('txndatetime'):null;
                    $approvalCode = (null !== $this->getRequest()->getParam('approval_code'))?$this->getRequest()->getParam('approval_code'):null;
                    $currency = (null !== $this->getRequest()->getParam('currency'))?$this->getRequest()->getParam('currency'):null;
                    $merchantTransactionId = (null !== $this->getRequest()->getParam('merchantTransactionId'))?$this->getRequest()->getParam('merchantTransactionId'):null;
                    $fail_reason = (null !== $this->getRequest()->getParam('fail_reason'))?$this->getRequest()->getParam('fail_reason'):null;
                    $status = (null !== $this->getRequest()->getParam('status'))?$this->getRequest()->getParam('status'):null;
                    $lloydsOrderId = (null !== $this->getRequest()->getParam('oid'))?$this->getRequest()->getParam('oid'):null;
                    $chargeTotal = (null !== $this->getRequest()->getParam('chargetotal'))?$this->getRequest()->getParam('chargetotal'):null;

                    $storeId = $this->helper->getConfig('paynow/configuration/store_id');

                    $verifyResponse = $this->helper->verifyResponseNotification($notificationHash, $transactionTime, $approvalCode, $chargeTotal, $currency, $storeId);

                    $paymentModel->setData('gateway_transaction_id', $lloydsOrderId);

                    if ($verifyResponse && ($this->helper->startsWith($approvalCode, 'Y:') || strpos(strtolower($approvalCode), 'waiting 3dsecure') !== false) && $status === 'APPROVED') {
                        $paymentModel->setData('payment_status', 2);
                        $paymentModel->save();

                        if($paymentModel->getEmailSent() != 1) {
                            $this->helper->sendEmail($paymentModel, 'success');
                            $paymentModel->setEmailSent(1);
                            $paymentModel->save();
                        }

                    } elseif (strpos(strtolower($approvalCode), 'cancel') !== false) {
                        $paymentModel->setData('payment_status', 3);
                        $paymentModel->save();

                        if($paymentModel->getEmailSent() != 1) {
                            $this->helper->sendEmail($paymentModel, 'failed');
                            $paymentModel->setEmailSent(1);
                            $paymentModel->save();
                        }

                    } else {
                        $paymentModel->setData('payment_status', 4);
                        $paymentModel->save();

                        if($paymentModel->getEmailSent() != 1) {
                            $this->helper->sendEmail($paymentModel, 'failed');
                            $paymentModel->setEmailSent(1);
                            $paymentModel->save();
                        }

                    }
                }
            }
        }catch (\Exception $e) {
            $this->helper->addLog($e->getMessage(), true);
        }


        $this->helper->addLog("WebHOOK REQUEST PAYMENT END");
        $result->setHttpResponseCode('200');
        $result->setData(['response' => __('Success.')]);
    }

    /**
     * @param RequestInterface $request
     * @return InvalidRequestException|null
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    /**
     * @param RequestInterface $request
     * @return bool|null
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
}

