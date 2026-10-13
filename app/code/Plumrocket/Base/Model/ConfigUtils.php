<?php
/**
 * @package     Plumrocket_Base
 * @copyright   Copyright (c) 2023 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

declare(strict_types=1);

namespace Plumrocket\Base\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * @since 2.10.0
 * Using interface can cause "Cannot instantiate interface"
 * because <preference> applies only after cache cleaning.
 * To avoid helpdesk tickets it's better to use model instead of interface.
 */
class ConfigUtils
{

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Receive magento config value by store
     *
     * @param string                                           $path  full path, eg: "pr_base/general/enabled"
     * @param string|int|\Magento\Framework\App\ScopeInterface $store store model, store view code, or website code
     * @return mixed
     */
    public function getStoreConfig(string $path, $store = null)
    {
        return $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE, $store);
    }

    /**
     * Receive magento global config value.
     *
     * @param string $path  full path, eg: "pr_base/general/enabled"
     * @return mixed
     * @since 2.11.4
     */
    public function getGlobalConfig(string $path)
    {
        return $this->scopeConfig->getValue($path);
    }

    /**
     * Receive magento config value by store or by other scope type
     *
     * @param string      $path      full path, eg: "pr_base/general/enabled"
     * @param string|int  $scopeCode store view code or website code
     * @param string|null $scopeType
     * @return mixed
     */
    public function getConfig(string $path, $scopeCode = null, $scopeType = null)
    {
        if ($scopeType === null) {
            $scopeType = ScopeInterface::SCOPE_STORE;
        }
        return $this->scopeConfig->getValue($path, $scopeType, $scopeCode);
    }

    /**
     * Convert multiline text into array.
     *
     * @param string $fieldValue
     * @return array
     */
    public function splitTextareaValueByLine(string $fieldValue): array
    {
        $lines = explode(PHP_EOL, $fieldValue);

        if (empty($lines)) {
            return [];
        }

        return array_filter(array_map('trim', $lines));
    }

    /**
     * Convert multiselect value into array.
     *
     * @param string $value
     * @param bool   $clearEmpty
     * @return array
     */
    public function prepareMultiselectValue(string $value, bool $clearEmpty = true): array
    {
        $values = explode(',', $value);
        return $clearEmpty ? array_filter($values) : $values;
    }

    /**
     * Is flag set.
     *
     * @param string $path
     * @param null   $scopeCode
     * @param null   $scopeType
     * @return bool
     */
    public function isSetFlag(string $path, $scopeCode = null, $scopeType = null): bool
    {
        if ($scopeType === null) {
            $scopeType = ScopeInterface::SCOPE_STORE;
        }
        return $this->scopeConfig->isSetFlag($path, $scopeType, $scopeCode);
    }
}
