<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace AutifyDigital\PayNow\Controller\Index;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\View\Result\PageFactory;

class Confirmation extends \Magento\Framework\App\Action\Action implements CsrfAwareActionInterface
{

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var \AutifyDigital\PayNow\Helper\Data
     */
    protected $helper;

    /**
     * @var string
     */
    protected $baseUrl;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param PageFactory $resultPageFactory
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \AutifyDigital\PayNow\Helper\Data $helper
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        PageFactory $resultPageFactory,
        \Magento\Framework\Registry $coreRegistry,
        \AutifyDigital\PayNow\Helper\Data $helper
    ) {
        parent::__construct($context);
        $this->coreRegistry = $coreRegistry;
        $this->resultPageFactory = $resultPageFactory;
        $this->helper = $helper;
        $this->baseUrl = $this->helper->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_WEB);
    }

    public function payByLinkErrorCallback($failRc)
    {
        if (!empty($failRc)) {
            $errorCodeList = [
                '32000', '50001', '50002', '50003', '50004', '50005', '50006', '50007', '50008', '50010', '50011',
                '50012', '50013', '50014', '50015', '50016', '50716', '50019', '50020', '50021', '50022', '50023',
                '50030', '50031', '50033', '50034', '50035', '50036', '50037', '50038', '50039', '50041', '50042',
                '50043', '50051', '50052', '50053', '50054', '50055', '50056', '50057', '50058', '50061', '50062',
                '50063', '50065', '50066', '50067', '50068', '50070', '50075', '50078', '50082', '50087', '50090',
                '50091', '50092', '50093', '50094', '50095', '50096', '50098', '500I1', '500I2', '500N0', '500O6',
                '500P9', '500S4', '500T6', '500T8', '500U0', '500U1', '500U2', '500U3', '500U4', '500U5', '500U6',
                '500U7', '500U8', '500V0', '500V1', '500V2', '500V3', '500V4', '500V7', '500V8', '500V9', '5001A',
                '500M1', '500M2', '500M3', '500N7', '500NB', '500NC', '500X1', '500X2', '500X3', '500X4', '5102', '5101'
            ];

            if ($failRc == '5993') {
                return __('The payment was not successful; kindly attempt it once more.');
            } elseif (in_array($failRc, $errorCodeList)) {
                return __('Declined: Your bank has declined the payment. Please try again or use an alternative payment method.');
            } else {
                return __('An internal error has occurred, please try again. If the error persists please contact the Seller.');
            }
        }

        return null;
    }
    /**
     * Execute view action
     *
     * @return ResultInterface
     */
    public function execute()
    {
        $this->helper->addLog("RESPONSE PAYMENT START");
        $this->helper->addLog($this->getRequest()->getParams(), true);

        try{
            if($this->getRequest()->getParams()) {
                $this->coreRegistry->register('gateway_transaction_data', $this->getRequest()->getParams());

                if($this->getRequest()->getParam('transaction_id') && $this->getRequest()->getParam('response_hash')) {
                    $responseHash = $this->getRequest()->getParam('response_hash');
                    $transactionId = $this->getRequest()->getParam('transaction_id');
                    $failRc = $this->getRequest()->getParam('fail_rc');

                    $paymentModel = $this->helper->getTransactionFactory()->getCollection()->addFieldToFilter("merchant_transaction_id", array("eq" => $transactionId))->getFirstItem();

                    if($paymentModel) {

                        $this->processConfirmation($paymentModel);

                    } else {
                        $this->messageManager->addErrorMessage(__("Something went wrong. Please contact support team."));
                        return $this->_redirect($this->baseUrl.'paynow');

                    }

                } else {
                    //Error
                    $this->messageManager->addErrorMessage(__("Something went wrong. Please contact support team."));
                    return $this->_redirect($this->baseUrl.'paynow');
                }
            } else {
                //Error
                $this->messageManager->addErrorMessage(__("Something went wrong. Please contact support team."));
                return $this->_redirect($this->baseUrl.'paynow');
            }
        }catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__("Something Went Wrong. Please contact to support team."));
            $this->helper->addLog($e->getMessage(), true);
            $this->_redirect($this->baseUrl . 'paynow');
        }

        $pageFactory = $this->resultPageFactory->create();

        // Add title which is got by the configuration via backend
        $pageFactory->getConfig()->getTitle()->set(
           __("Pay Now Confirmation")
        );
        $this->helper->addLog("RESPONSE PAYMENT END");
        return $pageFactory;
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

    public function processConfirmation($paymentModel)
    {
        $responseHash = $this->getRequest()->getParam('response_hash');
        $transactionId = $this->getRequest()->getParam('transaction_id');
        $transactionTime = (null !== $this->getRequest()->getParam('txndatetime'))?$this->getRequest()->getParam('txndatetime'):null;
        $approvalCode = (null !== $this->getRequest()->getParam('approval_code'))?$this->getRequest()->getParam('approval_code'):null;
        $currency = (null !== $this->getRequest()->getParam('currency'))?$this->getRequest()->getParam('currency'):null;
        $merchantTransactionId = (null !== $this->getRequest()->getParam('merchantTransactionId'))?$this->getRequest()->getParam('merchantTransactionId'):null;
        $fail_reason = (null !== $this->getRequest()->getParam('fail_reason'))?$this->getRequest()->getParam('fail_reason'):null;
        $status = (null !== $this->getRequest()->getParam('status'))?$this->getRequest()->getParam('status'):null;
        $lloydsOrderId = (null !== $this->getRequest()->getParam('oid'))?$this->getRequest()->getParam('oid'):null;
        $chargeTotal = (null !== $this->getRequest()->getParam('chargetotal'))?$this->getRequest()->getParam('chargetotal'):null;

        $storeId = $this->helper->getConfig('paynow/configuration/store_id');

        $verifyResponse = $this->helper->verifyResponse($responseHash, $transactionTime, $approvalCode, $chargeTotal, $currency, $storeId);

        $redirectToPage = $this->helper->getConfig('paynow/configuration/redirectto_page');

        $paymentModel->setData('gateway_transaction_id', $lloydsOrderId);

        $generalEmail = $this->helper->getGeneralEmail();
        $salesEmail = $this->helper->getSalesEmail();
        $adminOnlyEmail = $this->helper->getConfig('paynow/email_configuration/admin_email_only');
        $transactionEmailLists = $this->helper->getConfig('paynow/email_configuration/transaction_admin_email');
        $generalEmailName = $this->helper->getConfig('trans_email/ident_general/name');

        $errorMessage = $this->payByLinkErrorCallback($this->getRequest()->getParam('fail_rc'));

        if ($verifyResponse && ($this->helper->startsWith($approvalCode, 'Y:') || strpos(strtolower($approvalCode), 'waiting 3dsecure') !== false) && $status === 'APPROVED') {

            $paymentModel->setData('payment_status', 2);
            $paymentModel->save();
            $this->coreRegistry->register('transaction_data', $paymentModel);

            //Send emails
            if($paymentModel->getEmailSent() != 1) {
                $this->helper->sendEmail($paymentModel, 'success');
                $paymentModel->setEmailSent(1);
                $paymentModel->save();
            }

            $this->messageManager->addSuccessMessage(__('Your Transaction for Invoice #%1 is successful', $paymentModel->getInvoiceNumber()));

            //Return to page factory with response
        } elseif (strpos(strtolower($approvalCode), 'cancel') !== false) {
            $paymentModel->setData('payment_status', 3);
            $paymentModel->save();

            $this->coreRegistry->register('transaction_data', $paymentModel);

            if($paymentModel->getEmailSent() != 1) {
                $this->helper->sendEmail($paymentModel, 'failed');
                $paymentModel->setEmailSent(1);
                $paymentModel->save();
            }

            if($errorMessage) {
                $this->messageManager->addErrorMessage($errorMessage);
            }

            if($redirectToPage == 1) {
                return $this->_redirect($this->baseUrl.'paynow');
            }

        } else {
            $paymentModel->setData('payment_status', 4);
            $paymentModel->save();

            $this->coreRegistry->register('transaction_data', $paymentModel);


            if($paymentModel->getEmailSent() != 1) {
                $this->helper->sendEmail($paymentModel, 'failed');
                $paymentModel->setEmailSent(1);
                $paymentModel->save();
            }

            if($errorMessage) {
                $this->messageManager->addErrorMessage($errorMessage);
            }

            if($redirectToPage == 1) {
                return $this->_redirect($this->baseUrl.'paynow');
            }

        }
    }

}

