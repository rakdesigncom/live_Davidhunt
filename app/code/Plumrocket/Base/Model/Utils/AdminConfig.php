<?php
/**
 * @package     Plumrocket_Base
 * @copyright   Copyright (c) 2023 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

declare(strict_types=1);

namespace Plumrocket\Base\Model\Utils;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Store\Model\ScopeInterface;
use Plumrocket\Base\Model\ConfigUtils;

/**
 * @since 2.9.4
 */
class AdminConfig
{

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    private $request;

    /**
     * @var \Plumrocket\Base\Model\ConfigUtils
     */
    private $configUtils;

    /**
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Plumrocket\Base\Model\ConfigUtils      $configUtils
     */
    public function __construct(
        RequestInterface $request,
        ConfigUtils $configUtils
    ) {
        $this->request = $request;
        $this->configUtils = $configUtils;
    }

    /**
     * Get config for current scope selected in admin.
     *
     * @param string $path
     * @return string
     */
    public function getScopedConfig(string $path): string
    {
        if ($scopeCode = $this->request->getParam('website')) {
            $scopeType = ScopeInterface::SCOPE_WEBSITE;
        } elseif ($scopeCode = $this->request->getParam('store')) {
            $scopeType = ScopeInterface::SCOPE_STORE;
        } else {
            $scopeCode = null;
            $scopeType = ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
        }
        return (string) $this->configUtils->getConfig($path, $scopeCode, $scopeType);
    }
}
