<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace AutifyDigital\PayNow\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

class Data extends AbstractHelper
{

	/**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \AutifyDigital\PayNow\Model\Paynow
     */
    protected $paynowFactory;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $timeZoneInterface;

    /**
     * @var \Magento\Framework\Encryption\EncryptorInterface
     */
    protected $encryptorInterface;

    /**
     * @var \Magento\Directory\Model\CountryFactory
     */
    protected $countryFactory;

    /**
     * @var \Magento\Framework\App\ProductMetadataInterface
     */
    protected $productMetaDataInterface;

    /**
     * @var \AutifyDigital\PayNow\Helper\Mail
     */
    protected $mailHelper;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timeZoneInterface
     * @param \AutifyDigital\PayNow\Model\PaynowFactory $paynowFactory
     * @param \Magento\Directory\Model\CountryFactory $countryFactory
     * @param \Magento\Framework\Encryption\EncryptorInterface $encryptorInterface
     * @param \Magento\Framework\App\ProductMetadataInterface $productMetaDataInterface
     * @param \AutifyDigital\PayNow\Helper\Mail $mailHelper
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timeZoneInterface,
        \AutifyDigital\PayNow\Model\PaynowFactory $paynowFactory,
        \Magento\Directory\Model\CountryFactory $countryFactory,
        \Magento\Framework\Encryption\EncryptorInterface $encryptorInterface,
        \Magento\Framework\App\ProductMetadataInterface $productMetaDataInterface,
        \AutifyDigital\PayNow\Helper\Mail $mailHelper
    ) {
        parent::__construct($context);

        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->timeZoneInterface = $timeZoneInterface;
        $this->paynowFactory = $paynowFactory;
        $this->countryFactory = $countryFactory;
        $this->encryptorInterface = $encryptorInterface;
        $this->productMetaDataInterface = $productMetaDataInterface;
        $this->mailHelper = $mailHelper;
    }

    /**
     * @param $config_path
     * @return mixed
     */
    public function getConfig($config_path)
    {
        return $this->scopeConfig->getValue(
            $config_path,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return \Magento\Store\Api\Data\StoreInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getStore()
    {
    	return $this->storeManager->getStore();
    }

    /**
     * @return \AutifyDigital\PayNow\Model\Paynow
     */
    public function getTransactionFactory()
    {
    	return $this->paynowFactory->create();
    }

    /**
     * @return string
     */
    public function getCountryList()
    {
        $countries = "";
    
        $countryCollection = $this->countryFactory->create()->getCollection();
    
        foreach ($countryCollection as $country) {
            if (!empty($country->getId()) && !empty($country->getName())) {
                $selectedCountry = $country->getId() == 'GB' ? "selected" : "";
                $countries .= "<option value='" . $country->getId() . "' " . $selectedCountry . " >" . $country->getName() . "</option>";
            }
        }
    
        return $countries;
    }
    

    /**
     * @param $countryCode
     * @return string
     */
    public function getCountryName($countryCode)
    {
        $country = $this->countryFactory->create()->loadByCode($countryCode);
        return $country->getName();
    }

    /**
     * @param $string
     * @return string
     */
    public function decryptString($string)
    {
        return $this->encryptorInterface->decrypt($string);
    }

    /**
     * @param $storeName
     * @param $transactionTime
     * @param $chargeTotal
     * @param $currency
     * @return string
     */
    public function createHash($storeName, $transactionTime, $chargeTotal, $currency)
    {
        $sharedSecretEncrypted = $this->getConfig('paynow/configuration/shared_secret');
        $sharedSecret = $this->decryptString($sharedSecretEncrypted);

        $stringToHash = $storeName . $transactionTime . $chargeTotal . $currency . $sharedSecret;
        $ascii = bin2hex($stringToHash);
        return hash('sha256', $ascii);
    }

    /**
     * @param $response_hash
     * @param $transactionTime
     * @param $approvalCode
     * @param $chargeTotal
     * @param $currency
     * @param $storeName
     * @return bool
     */
    public function verifyResponse($response_hash, $transactionTime, $approvalCode, $chargeTotal, $currency, $storeName)
    {
        $sharedSecretEncrypted = $this->getConfig('paynow/configuration/shared_secret');
        $sharedSecret = $this->decryptString($sharedSecretEncrypted);

        $stringToHash = $sharedSecret . $approvalCode . $chargeTotal . $currency . $transactionTime . $storeName;
        $ascii = bin2hex($stringToHash);
        $myHash = hash('sha256', $ascii);

        if ($myHash === $response_hash) {
            return true;
        }
        return false;
    }

    /**
     * @param $notification_hash
     * @param $transactionTime
     * @param $approvalCode
     * @param $chargeTotal
     * @param $currency
     * @param $storeName
     * @return bool
     */
    public function verifyResponseNotification($notification_hash, $transactionTime, $approvalCode, $chargeTotal, $currency, $storeName)
    {
        $sharedSecretEncrypted = $this->getConfig('paynow/configuration/shared_secret');
        $sharedSecret = $this->decryptString($sharedSecretEncrypted);

        $notificationStringToHash = $chargeTotal . $sharedSecret . $currency . $transactionTime . $storeName . $approvalCode;

        $asciinotificationNewHash = bin2hex($notificationStringToHash);
        $notificationNewHash = hash('sha256', $asciinotificationNewHash);
        if($notificationNewHash === $notification_hash){
            return true;
        }
        return false;
    }


    /**
     * Start With
     *
     * @param $haystack, $needle
     * @return bool
     * */
    public function startsWith($haystack, $needle)
    {
        if (substr($haystack, 0, strlen($needle)) === $needle) {
            return true;
        }
        return false;
    }

    /**
     * @param $message
     * @param $array
     * @return void
     * @throws Exception
     */
    public function addLog($message, $array = false)
    {
        $magentoVersion = $this->productMetaDataInterface->getVersion();
        if (version_compare($magentoVersion, '2.4.3', '>=')) {
            $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/paynow.log');
            $logger = new \Zend_Log();
        } else {
            $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/paynow.log');
            $logger = new \Zend\Log\Logger();
        }

        $logger->addWriter($writer);
        if ($array === true) {
            $logger->info(print_r($message, true)); // @codingStandardsIgnoreLine
        } else {
            $logger->info($message);
        }
    }

    /**
     * Get Time Zone
     *
     * @return \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    public function timezone()
    {
        return $this->timeZoneInterface;
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getGeneralEmail()
    {
        return [
            "email" => $this->scopeConfig->getValue('trans_email/ident_general/email', ScopeInterface::SCOPE_STORE, $this->getStore()->getId()),
            "name" => $this->scopeConfig->getValue('trans_email/ident_general/name', ScopeInterface::SCOPE_STORE, $this->getStore()->getId())
        ];
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getSalesEmail()
    {
        return [
            "email" => $this->scopeConfig->getValue('trans_email/ident_sales/email', ScopeInterface::SCOPE_STORE, $this->getStore()->getId()),
            "name" => $this->scopeConfig->getValue('trans_email/ident_sales/name', ScopeInterface::SCOPE_STORE, $this->getStore()->getId())
        ];
    }

    public function sendEmail($paymentModel, $emailType)
    {
        $generalEmail = $this->getGeneralEmail();
        $salesEmail = $this->getSalesEmail();
        $adminOnlyEmail = $this->getConfig('paynow/email_configuration/admin_email_only');
        $transactionEmailLists = $this->getConfig('paynow/email_configuration/transaction_admin_email');
        $generalEmailName = $this->getConfig('trans_email/ident_general/name');

        if($emailType == 'failed') {
            if($adminOnlyEmail == 1) {
                $this->mailHelper->sendFailedTransactionAdminEmail($generalEmail, $salesEmail, $paymentModel->getData());
            } else {
                if(!empty($transactionEmailLists)) {
                    $transactionEmails = explode(",", $transactionEmailLists ?? "");
                    foreach ($transactionEmails as $transactionEmail) {
                        $this->mailHelper->sendFailedTransactionAdminEmail($generalEmail, ["email" => $paymentModel->getEmailAddress(), "name" => $generalEmailName], $paymentModel->getData());
                    }
                }
            }
            $this->mailHelper->sendFailedTransactionCustomerEmail($generalEmail, ["email" => $paymentModel->getEmailAddress(), "name" => $paymentModel->getName()], $paymentModel->getData());
        } else {
            if($adminOnlyEmail == 1) {
                $this->mailHelper->sendSuccessTransactionAdminEmail($generalEmail, $salesEmail, $paymentModel->getData());
            } else {
                if(!empty($transactionEmailLists)) {
                    $transactionEmails = explode(",", $transactionEmailLists ?? "");
                    foreach ($transactionEmails as $transactionEmail) {
                        $this->mailHelper->sendSuccessTransactionAdminEmail($generalEmail, ["email" => $paymentModel->getEmailAddress(), "name" => $generalEmailName], $paymentModel->getData());
                    }
                }
            }
            $this->mailHelper->sendSuccessTransactionCustomerEmail($generalEmail, ["email" => $paymentModel->getEmailAddress(), "name" => $paymentModel->getName()], $paymentModel->getData());
        }

    }
}
