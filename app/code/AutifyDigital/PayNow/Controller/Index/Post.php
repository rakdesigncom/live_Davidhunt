<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace AutifyDigital\PayNow\Controller\Index;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Controller\ResultFactory;

class Post extends \Magento\Framework\App\Action\Action implements CsrfAwareActionInterface
{

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var \AutifyDigital\PayNow\Helper\Data
     */
    protected $helper;
    private $_liveUrl = 'https://www.ipg-online.com/connect/gateway/processing';

    private $_testUrl = 'https://test.ipg-online.com/connect/gateway/processing';

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param PageFactory $resultPageFactory
     * @param \AutifyDigital\PayNow\Helper\Data $helper
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        PageFactory $resultPageFactory,
        \AutifyDigital\PayNow\Helper\Data $helper
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->helper = $helper;
    }

    /**
     * Execute view action
     *
     * @return ResultInterface
     */
    public function execute()
    {
        $this->helper->addLog("POST REQUEST PAYMENT START");
        $this->helper->addLog($this->getRequest()->getParams(), true);

        try{
            if($this->getRequest()->getParams()) {
                if(
                    $this->getRequest()->getParam('name') &&
                    $this->getRequest()->getParam('email_address') &&
                    $this->getRequest()->getParam('invoice_number') &&
                    $this->getRequest()->getParam('amount')
                ) {
                    $storeId = $this->helper->getConfig('paynow/configuration/store_id');
                    date_default_timezone_set('Europe/London');
                    $transactionTime = date("Y:m:d-H:i:s");

                    $amount = floatval($this->getRequest()->getParam('amount'));
                    $chargeTotal = number_format( (float) $amount, 2, '.', '' );
                    $currency = "826";

                    $hashValue = $this->helper->createHash($storeId, $transactionTime, $chargeTotal, $currency);

                    $newTxCode = strtoupper(bin2hex(openssl_random_pseudo_bytes(16)));

                    $returnUrl = $this->_url->getUrl('paynow/index/confirmation', ['transaction_id' => $newTxCode]);

                    // Save Payment Model
                    $paymentFactory = $this->helper->getTransactionFactory();

                    $billName = $this->getRequest()->getParam('billto_name') ?: $this->getRequest()->getParam('name');

                    $paymentData = [
                        "name"              => $this->getRequest()->getParam('name'),
                        "email_address"     => $this->getRequest()->getParam('email_address'),
                        "invoice_number"    => $this->getRequest()->getParam('invoice_number'),
                        "amount"            => $chargeTotal,
                        "company_name"      => $this->getRequest()->getParam('company_name'),
                        "billto_name"       => $this->getRequest()->getParam('billto_name'),
                        "billto_street1"    => $this->getRequest()->getParam('billto_street1'),
                        "billto_street2"    => $this->getRequest()->getParam('billto_street2'),
                        "billto_city"       => $this->getRequest()->getParam('billto_city'),
                        "billto_state"      => $this->getRequest()->getParam('billto_state'),
                        "billto_postcode"   => $this->getRequest()->getParam('billto_postcode'),
                        "billto_country"    => $this->getRequest()->getParam('billto_country'),
                        "billto_phone"      => $this->getRequest()->getParam('billto_phone'),
                        "shipto_name"       => $this->getRequest()->getParam('shipto_name'),
                        "shipto_street1"    => $this->getRequest()->getParam('shipto_street1'),
                        "shipto_street2"    => $this->getRequest()->getParam('shipto_street2'),
                        "shipto_city"       => $this->getRequest()->getParam('shipto_city'),
                        "shipto_state"      => $this->getRequest()->getParam('shipto_state'),
                        "shipto_postcode"   => $this->getRequest()->getParam('shipto_postcode'),
                        "shipto_country"    => $this->getRequest()->getParam('shipto_country'),
                        "shipto_phone"      => $this->getRequest()->getParam('shipto_phone'),
                        "merchant_transaction_id" => $newTxCode,
                        "payment_status" => 1
                    ];

                    $paymentFactory->setData($paymentData)->save();

                    $liveMode = $this->helper->getConfig('paynow/configuration/mode');

                    $_processingUrl = ( $liveMode && $liveMode == 1 ) ? $this->_liveUrl : $this->_testUrl;

                    $transactionNotificationUrl = $this->_url->getUrl('paynow/index/paymentwebhook', ['transaction_id' => $newTxCode]);

                    $merchantDynamicName = $this->helper->getConfig('paynow/configuration/merchant_dynamic_name');

                    $lloydsRedirectForm = "<form id='lloydscardsnetredirect-form' name='lloydscardsnetredirect-form' action='{$_processingUrl}' method='post'>
				      	<input type='hidden' name='txntype' value='sale'>
			            <input type='hidden' name='timezone' value='Europe/London'/>
			            <input type='hidden' name='txndatetime' value='{$transactionTime}'/>
			            <input type='hidden' name='hash_algorithm' value='SHA256'/>
			            <input type='hidden' name='hash' value='{$hashValue}'/>
			            <input type='hidden' name='storename' value='{$storeId}'/>
			            <input type='hidden' name='mode' value='payonly'/>
			            <input type='hidden' name='checkoutoption' value='combinedpage'/>
			            <input type='hidden' name='email' value='{$this->getRequest()->getParam('email_address')}'/>
                        <input type='hidden' name='comments' value='Autify Digital Magento 2 PayNow Plugin' />
			            <input type='hidden' name='threeDSRequestorChallengeIndicator' value='1' />
			            <input type='hidden' name='chargetotal' value='{$chargeTotal}'/>
			            <input type='hidden' name='currency' value='826'/>
			            <input type='hidden' name='merchantTransactionId' value='{$newTxCode}'/>
			            <input type='hidden' name='invoicenumber' value='{$this->getRequest()->getParam('invoice_number')}'/>
			            <input type='hidden' name='responseFailURL' value='".$returnUrl."'/>
			            <input type='hidden' name='responseSuccessURL' value='".$returnUrl."'/>
			            <input type='hidden' name='transactionNotificationURL' value='".$transactionNotificationUrl."'/>
			            <input type='hidden' name='authenticateTransaction' value='true'/>";
                        
                    if (!empty($merchantDynamicName)) {
                        $lloydsRedirectForm .= "<input type='hidden' name='dynamicMerchantName' value='{$merchantDynamicName}'/>";
                    }

                    if($billName) {
                        $lloydsRedirectForm .= "<input type='hidden' name='bcompany' value='{$this->getRequest()->getParam('company_name')}'>
                            <input type='hidden' name='bname' value='{$billName}'/>
                            <input type='hidden' name='baddr1' value='{$this->getRequest()->getParam('billto_street1')}'/>
                            <input type='hidden' name='baddr2' value='{$this->getRequest()->getParam('billto_street2')}'/>
                            <input type='hidden' name='bcity' value='{$this->getRequest()->getParam('billto_city')}'/>
                            <input type='hidden' name='bstate' value='{$this->getRequest()->getParam('billto_state')}'/>
                            <input type='hidden' name='bcountry' value='{$this->getRequest()->getParam('billto_country')}'/>
                            <input type='hidden' name='bzip' value='{$this->getRequest()->getParam('billto_postcode')}'/>
                            <input type='hidden' name='phone' value='{$this->getRequest()->getParam('billto_phone')}'/>
                            <input type='hidden' name='sname' value='{$this->getRequest()->getParam('shipto_name')}'/>
                            <input type='hidden' name='saddr1' value='{$this->getRequest()->getParam('shipto_street1')}'/>
                            <input type='hidden' name='saddr2' value='{$this->getRequest()->getParam('shipto_street2')}'/>
                            <input type='hidden' name='scity' value='{$this->getRequest()->getParam('shipto_city')}'/>
                            <input type='hidden' name='sstate' value='{$this->getRequest()->getParam('shipto_state')}'/>
                            <input type='hidden' name='scountry' value='{$this->getRequest()->getParam('shipto_country')}'/>
                            <input type='hidden' name='szip' value='{$this->getRequest()->getParam('shipto_postcode')}'/>";
                    }

                    $lloydsRedirectForm .= "</form>
			        <script type='text/javascript'>document.forms['lloydscardsnetredirect-form'].submit();</script>";
                    $this->helper->addLog($lloydsRedirectForm);
                    $this->helper->addLog("POST REQUEST PAYMENT END");
                    $resultPage = $this->resultFactory->create(ResultFactory::TYPE_RAW);
                    $resultPage->setContents($lloydsRedirectForm);
                    $this->helper->addLog("POST REQUEST PAYMENT END");
                    return $resultPage;
                } else {
                    $this->messageManager->addErrorMessage(__("Something Went Wrong. Please contact to support team."));
                    $this->helper->addLog("POST REQUEST PAYMENT END");
                    return $this->_redirect($this->helper->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_WEB) . 'paynow');
                }
            } else {
                $this->messageManager->addErrorMessage(__("Something Went Wrong. Please contact to support team."));
                $this->helper->addLog("POST REQUEST PAYMENT END");
                return $this->_redirect($this->helper->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_WEB) . 'paynow');
            }
        }catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__("Something Went Wrong. Please contact to support team."));
            $this->helper->addLog($e->getMessage(), true);
            return $this->_redirect($this->helper->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_WEB) . 'paynow');
        }

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

