<?php
/**
 * @package     Plumrocket_LayeredNavigationLite
 * @copyright   Copyright (c) 2022 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

namespace Plumrocket\LayeredNavigationLite\Model\Catalog\Layer\Filter;

use Magento\Framework\UrlInterface;
use Magento\Theme\Block\Html\Pager;
use Plumrocket\LayeredNavigationLite\Api\FilterItemUrlBuilderInterface;
use Plumrocket\LayeredNavigationLite\Helper\Config;

/**
 * @since 1.0.0
 */
class Item extends \Magento\Catalog\Model\Layer\Filter\Item
{

    /**
     * @var \Plumrocket\LayeredNavigationLite\Helper\Config
     */
    private $config;

    /**
     * @var \Plumrocket\LayeredNavigationLite\Api\FilterItemUrlBuilderInterface
     */
    private $itemUrl;

    /**
     * @param \Magento\Framework\UrlInterface                                     $url
     * @param \Magento\Theme\Block\Html\Pager                                     $htmlPagerBlock
     * @param \Plumrocket\LayeredNavigationLite\Helper\Config                     $config
     * @param \Plumrocket\LayeredNavigationLite\Api\FilterItemUrlBuilderInterface $itemUrl
     * @param array                                                               $data
     */
    public function __construct(
        UrlInterface $url,
        Pager $htmlPagerBlock,
        Config $config,
        FilterItemUrlBuilderInterface $itemUrl,
        array $data = []
    ) {
        $this->config = $config;
        $this->itemUrl = $itemUrl;
        parent::__construct($url, $htmlPagerBlock, $data);
    }

    /**
     * @inheritDoc
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getUrl()
    {
        if (! $this->config->isModuleEnabled()) {
            return parent::getUrl();
        }

        if ($this->getIsActive()) {
            return $this->getRemoveUrl();
        }

        return $this->itemUrl->getAddFilterUrl(
            $this->getFilter()->getRequestVar(),
            $this->getValueString(),
            (bool) $this->getFilter()->getIsRadio()
        );
    }

    /**
     * Rewrite default remove url.
     *
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getRemoveUrl()
    {
        if (! $this->config->isModuleEnabled()) {
            return parent::getUrl();
        }
        return $this->itemUrl->getRemoveFilterUrl(
            $this->getFilter()->getRequestVar(),
            $this->getValueString(),
            (bool) $this->getFilter()->getIsRadio()
        );
    }

    /**
     * Get item value as string.
     *
     * Price value should be imploded by underscore (_) instead of comma ','.
     *
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getValueString()
    {
        $value = $this->getValue();
        if ('price' === $this->getFilter()->getRequestVar() && is_array($value)) {
            return implode('_', $value);
        }
        return parent::getValueString();
    }
}
