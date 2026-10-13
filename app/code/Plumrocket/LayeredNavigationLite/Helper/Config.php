<?php
/**
 * @package     Plumrocket_LayeredNavigationLite
 * @copyright   Copyright (c) 2022 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

declare(strict_types=1);

namespace Plumrocket\LayeredNavigationLite\Helper;

use Magento\Catalog\Model\Product\ProductList\Toolbar;
use Magento\CatalogUrlRewrite\Model\CategoryUrlPathGenerator;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;

/**
 * @since 1.0.0
 */
class Config extends AbstractHelper
{

    public const FILTER_PARAM_SEPARATOR = '-';

    public const XML_PATH_IS_MODULE_ENABLED = 'prproductfilter/general/enabled';
    public const XML_PATH_SCROLL_UP = 'prproductfilter/main/scroll_up';

    /**
     * Toolbar variables
     * @var array
     */
    protected $toolbarVars = [
        Toolbar::PAGE_PARM_NAME,
        Toolbar::ORDER_PARAM_NAME,
        Toolbar::DIRECTION_PARAM_NAME,
        Toolbar::MODE_PARAM_NAME,
        Toolbar::LIMIT_PARAM_NAME
    ];

    /**
     * Array of allowed handles
     *
     * @var array
     */
    protected $allowedHandles = [
        'catalog_category_view',
        'catalog_category_view_type_layered',
        'catalogsearch_result_index',
    ];

    /**
     * @var \Plumrocket\Base\Model\Utils\Config
     */
    private $configUtils;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Plumrocket\Base\Model\Utils\Config   $configUtils
     */
    public function __construct(
        Context $context,
        \Plumrocket\Base\Model\Utils\Config $configUtils
    ) {
        parent::__construct($context);
        $this->configUtils = $configUtils;
    }

    /**
     * Check if module is enabled in configs.
     *
     * @param int|string|null $store
     * @param string|null     $scope
     * @return bool
     */
    public function isModuleEnabled($store = null, $scope = null): bool
    {
        return $this->configUtils->isSetFlag(self::XML_PATH_IS_MODULE_ENABLED, $store, $scope);
    }

    /**
     * Retrieve category suffix.
     *
     * @return string
     */
    public function getCategoryUrlSuffix(): string
    {
        return (string) $this->configUtils->getConfig(CategoryUrlPathGenerator::XML_PATH_CATEGORY_URL_SUFFIX);
    }

    /**
     * Check if we should scroll to top after filtering or other actions.
     *
     * @param int|string|null $store
     * @return bool
     */
    public function shouldScrollUpAfterUpdate($store = null): bool
    {
        return $this->configUtils->isSetFlag(self::XML_PATH_SCROLL_UP, $store);
    }

    /**
     * Get element to witch we should scroll up.
     *
     * @return string
     */
    public function getScrollUpSelector(): string
    {
        return '.toolbar-products';
    }

    /**
     * Retrieve toolbar vars
     *
     * @return array
     */
    public function getToolbarVars(): array
    {
        return $this->toolbarVars;
    }

    /**
     * Retrieve canonical url
     *
     * @return string
     */
    public function getCanonicalUrl(): string
    {
        $currentUrl = $this->_urlBuilder->getCurrentUrl();
        $parts = explode('?', $currentUrl);
        return $parts[0];
    }

    /**
     * Retrieve array of allowed handles
     *
     * @return array
     */
    public function getAllowedHandles(): array
    {
        return $this->allowedHandles;
    }
}
