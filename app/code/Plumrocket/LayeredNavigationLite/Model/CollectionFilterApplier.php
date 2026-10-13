<?php
/**
 * @package     Plumrocket_LayeredNavigationLite
 * @copyright   Copyright (c) 2022 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

declare(strict_types=1);

namespace Plumrocket\LayeredNavigationLite\Model;

use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\ObjectManagerInterface;
use Plumrocket\LayeredNavigationLite\Helper\SearchEngine;

/**
 * @since 1.3.0
 */
class CollectionFilterApplier
{

    /**
     * @var \Magento\Framework\App\ObjectManager
     */
    private $objectManager;

    /**
     * @var \Plumrocket\LayeredNavigationLite\Helper\SearchEngine
     */
    private $searchEngine;

    /**
     * @param \Magento\Framework\ObjectManagerInterface             $objectManager
     * @param \Plumrocket\LayeredNavigationLite\Helper\SearchEngine $searchEngine
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        SearchEngine $searchEngine
    ) {
        $this->objectManager = $objectManager;
        $this->searchEngine = $searchEngine;
    }

    /**
     * Apply filter to collection.
     *
     * @param \Magento\Catalog\Model\ResourceModel\Product\Collection $collection
     * @param string                                                  $code
     * @param array                                                   $values
     * @return void
     */
    public function applyInCondition(Collection $collection, string $code, array $values): void
    {
        if (! $values) {
            return;
        }

        if ($this->searchEngine->isLiveSearch()) {
            $this->fixForLiveSearch($code, $values);
            return;
        }

        $collection->addFieldToFilter($code, ['in' => $values]);
    }

    /**
     * Fix for Magento_LiveSearch
     *
     * Live Search cannot parse default collection 'in' condition,
     * so we add it to the search criteria with proper format.
     *
     * @param string $code
     * @param array  $values
     * @return void
     */
    private function fixForLiveSearch(string $code, array $values): void
    {
        // Use object manager because it doesn't work if we add them to the __construct.
        $searchCriteriaBuilder = $this->objectManager->get(SearchCriteriaBuilder::class);
        $filterBuilder = $this->objectManager->get(FilterBuilder::class);

        $filterBuilder->setField($code);
        if (count($values) > 1) {
            $filterBuilder->setValue(array_values($values));
            $filterBuilder->setConditionType('in');
        } else {
            $filterBuilder->setValue(array_values($values)[0]);
        }
        $searchCriteriaBuilder->addFilter($filterBuilder->create());
    }
}
