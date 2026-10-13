<?php
/**
 * @package     Plumrocket_LayeredNavigationLite
 * @copyright   Copyright (c) 2022 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

declare(strict_types=1);

namespace Plumrocket\LayeredNavigationLite\Model\FacetedData;

use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Layer;
use Magento\Catalog\Model\Layer\Filter\Item\DataBuilder;
use Magento\Framework\Api\Filter;
use Magento\Framework\Filter\StripTags;

/**
 * @since 1.0.0
 */
class CategoryResolver
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
     * @var \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory
     */
    private $categoryCollectionFactory;

    /**
     * @var \Magento\Framework\Escaper
     */
    private $escaper;

    /**
     * @var \Plumrocket\LayeredNavigationLite\Model\FacetedData\GetLayerFilters
     */
    private $getLayerFilters;

    /**
     * @var \Plumrocket\LayeredNavigationLite\Model\FacetedData\Search
     */
    private $search;

    /**
     * @param \Magento\Catalog\Model\Layer\Filter\Item\DataBuilder                $itemDataBuilder
     * @param \Magento\Framework\Filter\StripTags                                 $tagFilter
     * @param \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory     $categoryCollectionFactory
     * @param \Magento\Framework\Escaper                                          $escaper
     * @param \Plumrocket\LayeredNavigationLite\Model\FacetedData\GetLayerFilters $getLayerFilters
     * @param \Plumrocket\LayeredNavigationLite\Model\FacetedData\Search          $search
     */
    public function __construct(
        DataBuilder $itemDataBuilder,
        StripTags $tagFilter,
        \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryCollectionFactory,
        \Magento\Framework\Escaper $escaper,
        \Plumrocket\LayeredNavigationLite\Model\FacetedData\GetLayerFilters $getLayerFilters,
        \Plumrocket\LayeredNavigationLite\Model\FacetedData\Search $search
    ) {
        $this->itemDataBuilder = $itemDataBuilder;
        $this->tagFilter = $tagFilter;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->escaper = $escaper;
        $this->getLayerFilters = $getLayerFilters;
        $this->search = $search;
    }

    /**
     * Resolve faced data for attribute with ability to choose other attribute values.
     *
     * @param string                          $requestVar
     * @param \Magento\Catalog\Model\Category $category
     * @param \Magento\Catalog\Model\Layer    $layer
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\StateException
     */
    public function resolve(
        string $requestVar,
        Category $category,
        Layer $layer
    ): array {
        $filters = $layer->getState()->getFilters();
        $categoryFilterItems = [];
        foreach ($filters as $filterItem) {
            if ($requestVar === $filterItem->getFilter()->getRequestVar()) {
                $categoryFilterItems[] = $filterItem;
            }
        }

        return $this->getItemsData($category, $layer, $categoryFilterItems);
    }

    /**
     * Get data array for building attribute filter items
     *
     * @param \Magento\Catalog\Model\Category                                     $category
     * @param \Magento\Catalog\Model\Layer                                        $layer
     * @param \Plumrocket\LayeredNavigationLite\Model\Catalog\Layer\Filter\Item[] $attrFilterItems
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\StateException
     */
    protected function getItemsData(
        Category $category,
        Layer $layer,
        array $attrFilterItems
    ): array {
        $options = $this->getChildrenCategoriesOptions($category);
        $optionsFacetedData = $this->getFacetedData('category', $layer);

        foreach ($options as $option) {
            $this->buildOptionData($option, $optionsFacetedData, $attrFilterItems);
        }

        return $this->itemDataBuilder->build();
    }

    /**
     * Return field faceted data from faceted search result
     *
     * @param string                       $field
     * @param \Magento\Catalog\Model\Layer $layer
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\StateException
     */
    public function getFacetedData(string $field, Layer $layer): array
    {
        $filters = $this->getLayerFilters->execute($layer);
        $otherFilters = array_filter($filters, static function (Filter $filter) {
            return ! ($filter->getField() === 'category_ids' && is_array($filter->getValue()));
        });
        return $this->search->search($field, $otherFilters);
    }

    /**
     * Build option data
     *
     * @param array                                                               $option
     * @param array                                                               $optionsFacetedData
     * @param \Plumrocket\LayeredNavigationLite\Model\Catalog\Layer\Filter\Item[] $attrFilterItems
     * @return void
     */
    protected function buildOptionData(
        array $option,
        array $optionsFacetedData,
        array $attrFilterItems
    ): void {
        // todo: do not remove all categories from filters, only children
        $value = $this->getOptionValue($option);
        if ($value === false) {
            return;
        }
        $count = $this->getOptionCount($value, $optionsFacetedData);

        if ($count === 0 && !$this->isActiveFilter($value, $attrFilterItems)) {
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
     * @param int|string                                                          $value
     * @param \Plumrocket\LayeredNavigationLite\Model\Catalog\Layer\Filter\Item[] $attrFilterItems
     * @return bool
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

    /**
     * Return child categories
     *
     * @param \Magento\Catalog\Model\Category $category
     * @return array[]
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getChildrenCategoriesOptions(Category $category): array
    {
        /* @var \Magento\Catalog\Model\ResourceModel\Category\Collection $collection */
        $collection = $this->categoryCollectionFactory->create();

        $collection->addAttributeToSelect('url_key')
                   ->addAttributeToSelect('name')
                   ->addAttributeToSelect('all_children')
                   ->addAttributeToSelect('is_anchor')
                   ->addAttributeToFilter('is_active', 1)
                   ->addIdFilter($category->getChildren())
                   ->setOrder(
                       'position',
                       \Magento\Framework\DB\Select::SQL_ASC
                   );

        $options = [];
        /** @var \Magento\Catalog\Model\Category $childCategory */
        foreach ($collection->getItems() as $childCategory) {
            if (! $childCategory->getIsActive()) {
                continue;
            }

            $options[] = [
                'value' => $childCategory->getId(),
                'label' => $this->escaper->escapeHtml($childCategory->getName()),
            ];
        }

        return $options;
    }
}
