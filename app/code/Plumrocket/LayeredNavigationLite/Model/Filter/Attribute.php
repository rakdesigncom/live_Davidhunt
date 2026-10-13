<?php
/**
 * @package     Plumrocket_ProductFilter
 * @copyright   Copyright (c) 2020 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

namespace Plumrocket\LayeredNavigationLite\Model\Filter;

use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Catalog\Model\Layer;
use Magento\Catalog\Model\Layer\Filter\Item\DataBuilder;
use Magento\Catalog\Model\Layer\Filter\ItemFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filter\StripTags;
use Magento\Store\Model\StoreManagerInterface;
use Plumrocket\LayeredNavigationLite\Api\FilterMetaRepositoryInterface;
use Plumrocket\LayeredNavigationLite\Model\CollectionFilterApplier;
use Plumrocket\LayeredNavigationLite\Model\FacetedData\AttributeResolver;

/**
 * @since 1.0.0
 */
class Attribute extends \Magento\CatalogSearch\Model\Layer\Filter\Attribute
{

    /**
     * @var \Plumrocket\LayeredNavigationLite\Model\FacetedData\AttributeResolver
     */
    private $facetedDataResolver;

    /**
     * @var \Plumrocket\LayeredNavigationLite\Api\FilterMetaRepositoryInterface
     */
    private $filterMetaRepository;

    /**
     * @var \Plumrocket\LayeredNavigationLite\Model\CollectionFilterApplier
     */
    private $filterApplier;

    /**
     * @param \Magento\Catalog\Model\Layer\Filter\ItemFactory                       $filterItemFactory
     * @param \Magento\Store\Model\StoreManagerInterface                            $storeManager
     * @param \Magento\Catalog\Model\Layer                                          $layer
     * @param \Magento\Catalog\Model\Layer\Filter\Item\DataBuilder                  $itemDataBuilder
     * @param \Magento\Framework\Filter\StripTags                                   $tagFilter
     * @param \Plumrocket\LayeredNavigationLite\Model\FacetedData\AttributeResolver $facetedDataResolver
     * @param \Plumrocket\LayeredNavigationLite\Api\FilterMetaRepositoryInterface   $filterMetaRepository
     * @param \Plumrocket\LayeredNavigationLite\Model\CollectionFilterApplier       $filterApplier
     * @param array                                                                 $data
     */
    public function __construct(
        ItemFactory $filterItemFactory,
        StoreManagerInterface $storeManager,
        Layer $layer,
        DataBuilder $itemDataBuilder,
        StripTags $tagFilter,
        AttributeResolver $facetedDataResolver,
        FilterMetaRepositoryInterface $filterMetaRepository,
        CollectionFilterApplier $filterApplier,
        array $data = []
    ) {
        parent::__construct($filterItemFactory, $storeManager, $layer, $itemDataBuilder, $tagFilter, $data);
        $this->facetedDataResolver = $facetedDataResolver;
        $this->filterMetaRepository = $filterMetaRepository;
        $this->filterApplier = $filterApplier;
    }

    /**
     * @inheritdoc
     */
    public function apply(RequestInterface $request)
    {
        $attributeValue = $request->getParam($this->_requestVar);
        if ('' === $attributeValue || null === $attributeValue) {
            return $this;
        }

        $attributeValue = explode(',', (string) $attributeValue);
        if (! \count($attributeValue)) {
            return $this;
        }

        $attribute = $this->getAttributeModel();
        $productCollection = $this->getLayer()
            ->getProductCollection();

        foreach ($attributeValue as $index => $value) {
            $attributeValue[$index] = $this->convertAttributeValue($attribute, $value);
        }

        $this->filterApplier->applyInCondition($productCollection, $attribute->getAttributeCode(), $attributeValue);

        foreach ($attributeValue as $attributeVal) {

            $label = $this->getOptionText($attributeVal);
            $this->getLayer()
                ->getState()
                ->addFilter($this->_createItem($label, $attributeVal)->setIsActive(true));

        }
        return $this;
    }

    /**
     * Convert attribute value according to its backend type.
     *
     * @param ProductAttributeInterface $attribute
     * @param mixed $value
     * @return int|string
     */
    private function convertAttributeValue(ProductAttributeInterface $attribute, $value)
    {
        if ($attribute->getBackendType() === 'int') {
            return (int) $value;
        }

        return $value;
    }

    /**
     * @inheritdoc
     */
    protected function isOptionReducesResults($optionCount, $totalSize)
    {
        return true;
    }

    /**
     * Get data array for building attribute filter items.
     *
     * We customize logic for ability to choose multiple attribute options.
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _getItemsData(): array
    {
        try {
            if (! $this->filterMetaRepository->get($this->getRequestVar())->isAttribute()) {
                return parent::_getItemsData();
            }

            $attribute = $this->getAttributeModel();
            $isAttributeFilterable =
                $this->getAttributeIsFilterable($attribute) === static::ATTRIBUTE_OPTIONS_ONLY_WITH_RESULTS;

            return $this->facetedDataResolver->resolve(
                $this->getRequestVar(),
                $attribute,
                $this->getLayer(),
                $isAttributeFilterable
            );
        } catch (LocalizedException $exception) {
            return parent::_getItemsData();
        }
    }
}
