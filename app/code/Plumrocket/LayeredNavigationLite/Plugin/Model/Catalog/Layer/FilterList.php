<?php
/**
 * @package     Plumrocket_LayeredNavigationLite
 * @copyright   Copyright (c) 2022 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

namespace Plumrocket\LayeredNavigationLite\Plugin\Model\Catalog\Layer;

class FilterList
{

    /**
     * @var \Plumrocket\LayeredNavigationLite\Helper\Config
     */
    private $config;

    /**
     * @var \Plumrocket\LayeredNavigationLite\Helper\Config\Attribute
     */
    private $attributeConfig;

    /**
     * @param \Plumrocket\LayeredNavigationLite\Helper\Config           $config
     * @param \Plumrocket\LayeredNavigationLite\Helper\Config\Attribute $attributeConfig
     */
    public function __construct(
        \Plumrocket\LayeredNavigationLite\Helper\Config $config,
        \Plumrocket\LayeredNavigationLite\Helper\Config\Attribute $attributeConfig
    ) {
        $this->config = $config;
        $this->attributeConfig = $attributeConfig;
    }

    /**
     * Around get filter
     *
     * @param  \Magento\Catalog\Model\Layer\FilterList $filterList
     * @param  \Closure $result
     * @param  \Magento\Catalog\Model\Layer $layer
     * @return array
     */
    public function aroundGetFilters(
        \Magento\Catalog\Model\Layer\FilterList $filterList,
        $result,
        \Magento\Catalog\Model\Layer $layer
    ) {
        if (! $this->config->isModuleEnabled()) {
            return $result($layer);
        }

        $filters = $result($layer);

        //Remove first element from array, because category filter is first
        if (! $this->attributeConfig->isCategoryFilterEnabled()) {
            unset($filters[0]);
        }

        return $this->_sortFilters($filters);
    }

    /**
     * Sort filters
     *
     * @param array $filters
     * @return array
     */
    protected function _sortFilters(array $filters): array
    {
        $selectedAttributeCodes = $this->attributeConfig->getSelectedAttributeCodes();

        $sortedFilters = [];
        foreach ($filters as $filter) {
            if ($filter instanceof \Magento\Catalog\Model\Layer\Filter\Category
                || $filter instanceof \Magento\CatalogSearch\Model\Layer\Filter\Category
            ) {
                $_code = 'category';
            } elseif ($filter->getData('attribute_model')) {
                $_code = $filter->getAttributeModel()->getAttributeCode();
            } elseif ($filter->getLayer()->getCode()) {
                $_code = $filter->getLayer()->getCode();
            } else {
                $_code = $filter->getRequestVar();
            }

            if (in_array($_code, $selectedAttributeCodes, true)) {
                $position =  array_search($_code, $selectedAttributeCodes, true);
                $sortedFilters[$position] = $filter->setPfAttributeCode($_code);
            }
        }

        ksort($sortedFilters);
        return $sortedFilters;
    }
}
