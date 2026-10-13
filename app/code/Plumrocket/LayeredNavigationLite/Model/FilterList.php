<?php
/**
 * @package     Plumrocket_LayeredNavigationLite
 * @copyright   Copyright (c) 2022 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

namespace Plumrocket\LayeredNavigationLite\Model;

use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Catalog\Model\Layer\FilterList as MagentoFilterList;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory;
use Magento\Framework\DataObject;
use Psr\Log\LoggerInterface;

class FilterList
{

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory
     */
    protected $_filterableAttributes;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * @param \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory $filterableAttributes
     * @param \Psr\Log\LoggerInterface                                                 $logger
     */
    public function __construct(
        CollectionFactory $filterableAttributes,
        LoggerInterface $logger
    ) {
        $this->_filterableAttributes = $filterableAttributes;
        $this->logger = $logger;
    }

    /**
     * Get filters.
     *
     * @return array
     */
    public function getFilters(): array
    {
        /** @var \Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection $collection */
        $collection = $this->_filterableAttributes->create();
        $collection->setItemObjectClass(Attribute::class)
            ->setOrder('position', 'ASC');

        $collection->addFieldToFilter(
            ['additional_table.is_filterable', 'frontend_input'],
            [
                ['gt' => 0],
                ['in' => ['select', 'price', 'multiselect']],
            ]
        );

        $collection->addFieldToFilter('attribute_code', ['neq' => 'visibility']);

        $attributes = [];
        /** @var \Magento\Catalog\Api\Data\ProductAttributeInterface $attr */
        foreach ($collection->getItems() as $attr) {
            if (! $this->canUseAttribute($attr)) {
                continue;
            }
            $attributes[$attr->getAttributeCode()] = $attr;
        }

        return array_merge($attributes, $this->getAdditionalFilters());
    }

    /**
     * Retrieve attributes in format: code => label
     *
     * @return string[]
     */
    public function getAttributeList(): array
    {
        $attributeList = [];
        $attributes = $this->getFilters();
        foreach ($attributes as $code => $attribute) {
            /** @var \Magento\Catalog\Model\ResourceModel\Eav\Attribute $attribute */
            $attributeList[$code] = (string) $attribute->getFrontendLabel();
        }

        asort($attributeList);

        return $attributeList;
    }

    /**
     * Get additional filters.
     *
     * @return \Magento\Framework\DataObject[]
     */
    protected function getAdditionalFilters(): array
    {
        return [
            MagentoFilterList::CATEGORY_FILTER => new DataObject(
                [
                    'attribute_code' => MagentoFilterList::CATEGORY_FILTER,
                    'frontend_label' => __('Categories'),
                ]
            ),
        ];
    }

    /**
     * Check if attribute is suitable for filtering.
     *
     * @param \Magento\Catalog\Api\Data\ProductAttributeInterface $productAttribute
     * @return bool
     */
    private function canUseAttribute(ProductAttributeInterface $productAttribute): bool
    {
        try {
            $options = $productAttribute->getOptions();
            if (! is_array($options)) {
                return false;
            }
            if ($this->hasArrayValue($options)) {
                return false;
            }
            return true;
        } catch (\Exception $exception) {
            $this->logger->debug(
                "Attribute {$productAttribute->getAttributeCode()} is not included to filer list " .
                "because of error: {$exception->getMessage()}"
            );
        }
        return false;
    }

    /**
     * Check if any attribute option is array.
     *
     * We cannot work with that type of attributes.
     *
     * @param array $options
     * @return bool
     */
    private function hasArrayValue(array $options): bool
    {
        foreach ($options as $option) {
            if (is_array($option->getValue())) {
                return true;
            }
        }
        return false;
    }
}
