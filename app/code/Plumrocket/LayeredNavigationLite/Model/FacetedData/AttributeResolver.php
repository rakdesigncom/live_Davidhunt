<?php
/**
 * @package     Plumrocket_ProductFilter
 * @copyright   Copyright (c) 2022 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

declare(strict_types=1);

namespace Plumrocket\LayeredNavigationLite\Model\FacetedData;

use Magento\Catalog\Model\Layer;
use Magento\Catalog\Model\Layer\Filter\Item\DataBuilder;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Framework\Api\Filter;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filter\StripTags;

/**
 * @since 1.0.0
 */
class AttributeResolver
{

    /**
     * @var \Magento\Catalog\Model\Layer\Filter\Item\DataBuilder
     */
    private $itemDataBuilder;

    /**
     * @var \Magento\Framework\Filter\StripTags
     */
    private $tagFilter;

    /**
     * @var \Plumrocket\LayeredNavigationLite\Model\FacetedData\GetLayerFilters
     */
    private $getLayerFilters;

    /**
     * @var \Plumrocket\LayeredNavigationLite\Model\FacetedData\Search
     */
    private $search;

    /**
     * @param \Magento\Catalog\Model\Layer\Filter\Item\DataBuilder            $itemDataBuilder
     * @param \Magento\Framework\Filter\StripTags                             $tagFilter
     * @param \Plumrocket\LayeredNavigationLite\Model\FacetedData\GetLayerFilters $getLayerFilters
     * @param \Plumrocket\LayeredNavigationLite\Model\FacetedData\Search          $search
     */
    public function __construct(
        DataBuilder $itemDataBuilder,
        StripTags $tagFilter,
        GetLayerFilters $getLayerFilters,
        Search $search
    ) {
        $this->itemDataBuilder = $itemDataBuilder;
        $this->tagFilter = $tagFilter;
        $this->getLayerFilters = $getLayerFilters;
        $this->search = $search;
    }

    /**
     * Resolve faced data for attribute with ability to choose other attribute values.
     *
     * @param string                                             $requestVar
     * @param \Magento\Catalog\Model\ResourceModel\Eav\Attribute $attribute
     * @param \Magento\Catalog\Model\Layer                       $layer
     * @param bool                                               $isAttributeFilterable
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\StateException
     */
    public function resolve(
        string $requestVar,
        Attribute $attribute,
        Layer $layer,
        bool $isAttributeFilterable
    ): array {
        $filters = $layer->getState()->getFilters();
        $attrFilterItems = [];
        foreach ($filters as $filterItem) {
            if ($requestVar === $filterItem->getFilter()->getRequestVar()) {
                $attrFilterItems[] = $filterItem;
            }
        }

        if ($attrFilterItems) {
            return $this->getItemsData(
                $attribute,
                $layer,
                $isAttributeFilterable,
                $attrFilterItems
            );
        }

        throw new LocalizedException(__('Do not need custom faced data resolving.'));
    }

    /**
     * Get data array for building attribute filter items
     *
     * @param \Magento\Catalog\Model\ResourceModel\Eav\Attribute              $attribute
     * @param \Magento\Catalog\Model\Layer                                    $layer
     * @param bool                                                            $isAttributeFilterable
     * @param \Plumrocket\LayeredNavigationLite\Model\Catalog\Layer\Filter\Item[] $attrFilterItems
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\StateException
     */
    protected function getItemsData(
        Attribute $attribute,
        Layer $layer,
        bool $isAttributeFilterable,
        array $attrFilterItems
    ): array {
        $optionsFacetedData = $this->getFacetedData($attribute->getAttributeCode(), $layer);
        if (! $isAttributeFilterable && count($optionsFacetedData) === 0) {
            return $this->itemDataBuilder->build();
        }

        foreach ($attribute->getFrontend()->getSelectOptions() as $option) {
            $this->buildOptionData($option, $isAttributeFilterable, $optionsFacetedData, $attrFilterItems);
        }
        return $this->itemDataBuilder->build();
    }

    /**
     * Return field faceted data from faceted search result
     *
     * @param string                       $field
     * @param \Magento\Catalog\Model\Layer $layer
     * @return array
     * @throws \Magento\Framework\Exception\StateException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getFacetedData(string $field, Layer $layer): array
    {
        $filters = $this->getLayerFilters->execute($layer);
        $otherFilters = array_filter($filters, static function (Filter $filter) use ($field) {
            return $field !== $filter->getField();
        });

        return $this->search->search($field, $otherFilters);
    }

    /**
     * Build option data
     *
     * @param array                                                           $option
     * @param bool                                                            $isAttributeFilterable
     * @param array                                                           $optionsFacetedData
     * @param \Plumrocket\LayeredNavigationLite\Model\Catalog\Layer\Filter\Item[] $attrFilterItems
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function buildOptionData(
        array $option,
        bool $isAttributeFilterable,
        array $optionsFacetedData,
        array $attrFilterItems
    ): void {
        $value = $this->getOptionValue($option);
        if ($value === false) {
            return;
        }

        $count = $this->getOptionCount($value, $optionsFacetedData);
        if ($isAttributeFilterable && ($count === 0 && !$this->isActiveFilter($value, $attrFilterItems))) {
            return;
        }

        $this->itemDataBuilder->addItemData(
            $this->tagFilter->filter($option['label']),
            $value,
            $count
        );
    }

    /**
     * Retrieve option value if it exists
     *
     * @param array $option
     * @return bool|string
     */
    private function getOptionValue(array $option)
    {
        if (empty($option['value']) && !is_numeric($option['value'])) {
            return false;
        }
        return $option['value'];
    }

    /**
     * Retrieve count of the options
     *
     * @param int|string $value
     * @param array $optionsFacetedData
     * @return int
     */
    private function getOptionCount($value, array $optionsFacetedData): int
    {
        return isset($optionsFacetedData[$value]['count'])
            ? (int) $optionsFacetedData[$value]['count']
            : 0;
    }

    /**
     * Check if option is selected.
     *
     * @param int|string                                                      $value
     * @param \Plumrocket\LayeredNavigationLite\Model\Catalog\Layer\Filter\Item[] $attrFilterItems
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function isActiveFilter($value, array $attrFilterItems): bool
    {
        foreach ($attrFilterItems as $filterItem) {
            if ((string) $value === (string) $filterItem->getValueString()) {
                return true;
            }
        }
        return false;
    }
}
