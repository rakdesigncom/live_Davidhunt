<?php
/**
 * @package     Plumrocket_LayeredNavigationLite
 * @copyright   Copyright (c) 2022 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

declare(strict_types=1);

namespace Plumrocket\LayeredNavigationLite\Helper\Config;

use Magento\Catalog\Model\Layer\FilterList;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Store\Model\ScopeInterface;
use Plumrocket\Base\Model\Utils\Config;

/**
 * @since 1.0.0
 */
class Attribute extends AbstractHelper
{

    public const XML_PATH_ATTRS = 'prproductfilter/settings/attributes';
    public const XML_PATH_SHOW_EMPTY = 'prproductfilter/settings/empty_option';

    /**
     * @var \Plumrocket\Base\Model\Utils\Config
     */
    private $configUtils;

    /**
     * @var \Magento\Framework\Serialize\SerializerInterface
     */
    private $serializer;

    /**
     * @var \Magento\Framework\Registry
     */
    private $registry;

    /**
     * @param \Magento\Framework\App\Helper\Context            $context
     * @param \Plumrocket\Base\Model\Utils\Config              $configUtils
     * @param \Magento\Framework\Serialize\SerializerInterface $serializer
     * @param \Magento\Framework\Registry                      $registry
     */
    public function __construct(
        Context $context,
        Config $configUtils,
        SerializerInterface $serializer,
        \Magento\Framework\Registry $registry
    ) {
        parent::__construct($context);
        $this->configUtils = $configUtils;
        $this->serializer = $serializer;
        $this->registry = $registry;
    }

    /**
     * Get attributes configuration.
     *
     * @param int|null $storeId
     * @return array
     */
    public function getSelectedAttributesConfig(int $storeId = null): array
    {
        if (null !== $storeId) {
            $selectedAttrs = (string) $this->configUtils->getStoreConfig(self::XML_PATH_ATTRS, $storeId);
        } else {
            $selectedAttrs = $this->getRelatedConfig(self::XML_PATH_ATTRS);
        }
        if (! $selectedAttrs) {
            return [];
        }
        return $this->serializer->unserialize($selectedAttrs);
    }

    /**
     * Show filters with empty results.
     *
     * @return bool
     */
    public function shouldShowEmpty(): bool
    {
        return $this->configUtils->isSetFlag(self::XML_PATH_SHOW_EMPTY);
    }

    /**
     * Get config by current store/website.
     *
     * @param  string $path
     * @return string
     */
    public function getRelatedConfig(string $path): string
    {
        $scopeCode = null;
        $scopeType = null;

        if ('prproductfilter' === $this->_request->getParam('section')) {
            if ($scopeCode = $this->_request->getParam('website')) {
                $scopeType = ScopeInterface::SCOPE_WEBSITE;
            } elseif ($scopeCode = $this->_request->getParam('store')) {
                $scopeType = ScopeInterface::SCOPE_STORE;
            } else {
                $scopeCode = 0;
            }
        } elseif ($category = $this->registry->registry('current_category')) {
            $scopeCode = $category->getStoreId();
        }
        return (string) $this->configUtils->getConfig($path, $scopeCode, $scopeType);
    }

    /**
     * Get selected attribute codes.
     *
     * @return string[]
     */
    public function getSelectedAttributeCodes(): array
    {
        $selectedAttrs = $this->getSelectedAttributesConfig();
        return $this->fixCodes(array_keys($selectedAttrs));
    }

    /**
     * Checking is category filter enabled.
     *
     * @return bool
     */
    public function isCategoryFilterEnabled(): bool
    {
        return in_array(FilterList::CATEGORY_FILTER, $this->getSelectedAttributeCodes(), true);
    }

    /**
     * Fix category code.
     *
     * @param array $codes
     * @return string[]
     */
    protected function fixCodes(array $codes): array
    {
        return array_replace(
            $codes,
            array_fill_keys(
                array_keys($codes, 'categorie'),
                'category'
            )
        );
    }
}
