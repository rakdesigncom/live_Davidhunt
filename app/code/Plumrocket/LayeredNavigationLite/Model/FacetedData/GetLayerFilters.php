<?php
/**
 * @package     Plumrocket_Amp
 * @copyright   Copyright (c) 2022 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

declare(strict_types=1);

namespace Plumrocket\LayeredNavigationLite\Model\FacetedData;

use Magento\Catalog\Model\Layer;
use Magento\CatalogSearch\Model\ResourceModel\Fulltext\Collection;
use Magento\Framework\Exception\LocalizedException;

/**
 * @since 1.0.0
 */
class GetLayerFilters
{
    /**
     * Get Search Criteria Builder from product collection.
     *
     * @param \Magento\Catalog\Model\Layer $layer
     * @return \Magento\Framework\Api\Filter[]
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(Layer $layer): array
    {
        try {
            /** @var Collection $productCollection */
            $productCollection = $layer->getProductCollection();
            $reflectionSubject = new \ReflectionObject($productCollection);
            $reflectionProperty = $reflectionSubject->getParentClass()->getProperty('searchCriteriaBuilder');
            $reflectionProperty->setAccessible(true);
            /** @var \Magento\Framework\Api\Search\SearchCriteriaBuilder $searchCriteriaBuilder */
            $searchCriteriaBuilder = clone $reflectionProperty->getValue($productCollection);
            $reflectionProperty->setAccessible(false);

            $reflectionSubject = new \ReflectionObject($searchCriteriaBuilder);
            $reflectionProperty = $reflectionSubject->getProperty('filters');
            $reflectionProperty->setAccessible(true);
            $filters = $reflectionProperty->getValue($searchCriteriaBuilder);
            $reflectionProperty->setAccessible(false);
        } catch (\ReflectionException $e) {
            throw new LocalizedException(__('Cannot get existing filters'));
        }
        return $filters;
    }
}
