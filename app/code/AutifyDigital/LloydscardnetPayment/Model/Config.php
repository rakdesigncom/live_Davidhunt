<?php
/**
 * @copyright Copyright (c) 2022
 * @license   Open Software License (OSL 3.0)
 */
declare(strict_types=1);

namespace AutifyDigital\LloydscardnetPayment\Model;

use Magento\Framework\Registry;
use Magento\Framework\App\Config\ScopeConfigInterface;
 use Magento\Framework\Config\ScopeInterface;
 use Magento\Store\Model\ScopeInterface as StoreScopeInterface;
 use Magento\Framework\Encryption\EncryptorInterface;
 use Magento\Store\Model\StoreManagerInterface;
 use Magento\Framework\App\Filesystem\DirectoryList;
 
/**
 * Configuration Class
 */
class Config
{   
    /**
     * @var $_liveUrl
     */

    private $_liveUrl = "https://www.ipg-online.com/connect/gateway/processing";

    /**
     * @var $_testUrl
     */

    private $_testUrl = "https://test.ipg-online.com/connect/gateway/processing";

    /**
     * @var $scopeConfig
     *
     */
    private $scopeConfig;
    
    /**
     * @var \Magento\Framework\App\Filesystem\DirectoryList
     * */
    protected $_directorylist;
 
     /**
     * @var EncryptorInterface
     */
    private $encryptorInterface;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * construct
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param EncryptorInterface $encryptorInterface
     * @param StoreManagerInterface $storeManager
     * @param DirectoryList $directorylist
     *
     */
    public function __construct(
        Registry $registry,
        ScopeConfigInterface $scopeConfig,
        EncryptorInterface $encryptorInterface,
        StoreManagerInterface $storeManager,
        DirectoryList $directorylist
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->encryptorInterface = $encryptorInterface;
        $this->storeManager = $storeManager;
        $this->_directorylist = $directorylist;
    }

    /**
     * Get Config Data
     *
     * @param string $configPath
     *
     * @return string | bool
     */
    public function getConfig($configPath)
    {
        return $this->scopeConfig->getValue(
            $configPath,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Decrypyt Data
     *
     * @param string $password
     * @return string
     */
    public function decrypt($password)
    {
        return $this->encryptorInterface->decrypt($password);
    }

    /**
     * Get Store URL
     *
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getStoreUrl()
    {
        return $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_WEB);
    }

    /**
     * Get Media URL
     *
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getMediaUrl()
    {
        return $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
    }

    /**
     * Get Media Path
     *
     * @return string
     */
    public function getMediaPath()
    {
        return $this->_directorylist->getPath('media');
    }

    /**
     * Get Shared Secret
     *
     * @return string
     */
    public function getSharedSecret()
    {
        $mode = $this->getConfig('payment/lcnetredirect/lloyds_mode');
        if ($mode == 'Live') {
            $sharedSecret = $this->getConfig('payment/autifydigital/lcnet/basic/live_credential/shared_secret');
        } else {
            $sharedSecret = $this->getConfig('payment/autifydigital/lcnet/basic/test_credential/shared_secret');
        }
        
        return $this->decrypt($sharedSecret);
    }

    /**
     * Get basic Configuration by Mode
     *
     * @param string $mode
     *
     * @return array
     */
    public function getBasicConfigurations($mode = 'Test')
    {
        $config = [];

        $mediaUrl = $this->getMediaPath();

        if ($mode == 'Live') {
            $credential_piece = 'live_credential';
            $liveFileName = $this->getConfig(
                'payment/autifydigital/lcnet/basic/'.$credential_piece.'/certificate_file'
            );
            $certificateFilePath = $mediaUrl.'/lcnet_certificates/live/'.$liveFileName;
            $processingUrl = $this->_liveUrl;
            $restUrl = 'https://prod.api.firstdata.com/';
        } else {
            $credential_piece = 'test_credential';
            $testFileName = $this->getConfig(
                'payment/autifydigital/lcnet/basic/'.$credential_piece.'/certificate_file'
            );
            $certificateFilePath = $mediaUrl.'/lcnet_certificates/test/'.$testFileName;
            $processingUrl = $this->_testUrl;
            $restUrl = 'https://cert.api.firstdata.com/';
        }
        
        $storeId = $this->getConfig('payment/autifydigital/lcnet/basic/'.$credential_piece.'/store_id');
        $decryptStoreId = $this->decrypt($storeId);

        $sharedSecret = $this->getConfig('payment/autifydigital/lcnet/basic/'.$credential_piece.'/shared_secret');
        $decryptSharedSecret = $this->decrypt($sharedSecret);

        $apiKey = $this->getConfig('payment/autifydigital/lcnet/basic/'.$credential_piece.'/api_key');
        $decryptApiKey = $this->decrypt($apiKey);

        $apiSecret = $this->getConfig('payment/autifydigital/lcnet/basic/'.$credential_piece.'/api_secret');
        $decryptApiSecret = $this->decrypt($apiSecret);

        $userId = $this->getConfig('payment/autifydigital/lcnet/basic/'.$credential_piece.'/user_id');

        // $userPassword = $this->getConfig('payment/autifydigital/lcnet/basic/'.$credential_piece.'/user_password');
        // $decryptUserPassword = $this->decrypt($userPassword);

        // $certificatePassword = $this->getConfig(
        //     'payment/autifydigital/lcnet/basic/'.$credential_piece.'/certificate_password'
        // );
        // $decryptCertPassword = $this->decrypt($certificatePassword);

        $paymentRefPrefix = $this->getConfig('payment/autifydigital/lcnet/basic/payment_ref_prefix');
        $paymentRefLeadingZeros = $this->getConfig('payment/autifydigital/lcnet/basic/payment_leading_zeros');

        $config = [
            'store_id'              => $decryptStoreId,
            'shared_secret'         => $decryptSharedSecret,
            'api_key'               => $decryptApiKey,
            'api_secret'            => $decryptApiSecret,
            'payment_ref_prefix'    => $paymentRefPrefix,
            'payment_leading_zeros' => $paymentRefLeadingZeros,
            'pay_mode'              => 'payonly',
            'page_option'           => 'combinedpage',
            'processing_url'        => $processingUrl,
            'rest_url'              => $restUrl
        ];

        return $config;
    }
}
