<?php

namespace Fyb\Theme\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\ScopeInterface;

class StoreData extends AbstractHelper
{
    protected $storeManager;

    public function __construct(
        Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);

        $this->storeManager = $storeManager;
    }

    public function getStoreBaseUrl()
    {
        return $this->storeManager->getStore()->getBaseUrl();
    }

    public function getStoreCurrentUrl()
    {
        return $this->storeManager->getStore()->getCurrentUrl();
    }

    public function getStoreMediaUrl()
    {
        return $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
    }

    public function getStoreContactEmail()
    {
        return $this->getConfig('contact/email/recipient_email');
    }

    public function getStorePhone()
    {
        return $this->getConfig('general/store_information/phone');
    }

    public function getConfig($path, $scopeType = ScopeInterface::SCOPE_STORE, $scopeCode = null)
    {
        return $this->scopeConfig->getValue($path, $scopeType, $scopeCode);
    }
}
