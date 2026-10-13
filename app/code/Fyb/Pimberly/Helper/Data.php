<?php

namespace Fyb\Pimberly\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

class Data extends AbstractHelper
{
    public const ACCESS_TOKEN = 'pimberly/general/access_token';
    public const ENABLED = 'pimberly/general/enabled';
    public const LIMIT = 'pimberly/general/limit';

    /**
     * @param string $path
     * @param mixed $scopeCode
     *
     * @return mixed
     */
    public function getConfig($path, $scopeCode = null)
    {
        return $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE, $scopeCode);
    }

    public function getAccessToken()
    {
        return $this->getConfig(self::ACCESS_TOKEN);
    }

    public function isEnabled($scopeCode = null)
    {
        return $this->getConfig(self::ENABLED, $scopeCode);
    }

    public function getLimit()
    {
        return $this->getConfig(self::LIMIT);
    }
}
