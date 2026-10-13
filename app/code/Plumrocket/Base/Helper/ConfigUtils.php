<?php
/**
 * @package     Plumrocket_Base
 * @copyright   Copyright (c) 2020 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license/  End-user License Agreement
 */

declare(strict_types=1);

namespace Plumrocket\Base\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

/**
 * Provides methods for work with config value
 *
 * Generally we create const like this:
 *     public const XML_PATH_IS_ENABLED = 'pr_base/general/enabled';
 *
 * We also have a lightweight model as alternative to this helper.
 * @see \Plumrocket\Base\Model\ConfigUtils
 *
 * @since 2.3.1
 */
class ConfigUtils extends AbstractHelper
{

    /**
     * Receive magento config value
     *
     * @param string      $path      full path, eg: "pr_base/general/enabled"
     * @param string|int  $scopeCode store view code or website code
     * @param string|null $scopeType
     * @return mixed
     */
    public function getConfig($path, $scopeCode = null, $scopeType = null)
    {
        if ($scopeType === null) {
            $scopeType = ScopeInterface::SCOPE_STORE;
        }
        return $this->scopeConfig->getValue($path, $scopeType, $scopeCode);
    }

    /**
     * Split textarea.
     *
     * @param string $fieldValue
     * @return array
     * @deprecated since 2.6.0 - was moved
     * @see \Plumrocket\Base\Model\ConfigUtils::splitTextareaValueByLine()
     */
    protected function splitTextareaValueByLine($fieldValue): array
    {
        $lines = explode(PHP_EOL, $fieldValue);

        if (empty($lines)) {
            return [];
        }

        return array_filter(array_map('trim', $lines));
    }

    /**
     * Parse multiselect value.
     *
     * @param string $value
     * @param bool   $clearEmpty
     * @return array
     * @deprecated since 2.6.0 - was moved
     * @see \Plumrocket\Base\Model\ConfigUtils::prepareMultiselectValue()
     */
    protected function prepareMultiselectValue(string $value, bool $clearEmpty = true): array
    {
        $values = explode(',', $value);
        return $clearEmpty ? array_filter($values) : $values;
    }
}
