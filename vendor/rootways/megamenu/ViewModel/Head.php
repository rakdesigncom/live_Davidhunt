<?php
namespace Rootways\Megamenu\ViewModel;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\StoreManagerInterface;

class Head implements ArgumentInterface
{
    /**
     * @var StoreManagerInterface
     */
    private $_storeManager;

    /**
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        StoreManagerInterface $storeManager
    ) {
        $this->_storeManager = $storeManager;
    }

    public function getMediaUrl()
    {
        return $this->_storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
    }

    public function getStoreCss()
    {
        return $this->getMediaUrl(). 'rootways/megamenu/' . 'menu_' . $this->_storeManager->getStore()->getCode() . '.css';
    }
}
