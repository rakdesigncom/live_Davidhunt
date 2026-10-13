<?php
namespace Sparsh\Banner\Helper;

/**
 * Class Data
 * @package Sparsh\Banner\Helper
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * Retrieve config value
     *
     * @return string
     */
    public function getConfig($config)
    {
        return $this->scopeConfig->getValue($config, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }
}
