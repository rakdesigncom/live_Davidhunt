<?php
/**
 * @package     Plumrocket_LayeredNavigationLite
 * @copyright   Copyright (c) 2022 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

declare(strict_types=1);

namespace Plumrocket\LayeredNavigationLite\Model\FacetedData;

use Magento\CatalogSearch\Model\Search\RequestGenerator;
use Magento\Framework\Api\Search\SearchCriteriaBuilderFactory;
use Magento\Framework\Api\Search\SearchResultFactory;
use Magento\Framework\Api\Search\SearchResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\StateException;
use Magento\Framework\Search\Request\EmptyRequestDataException;
use Magento\Framework\Search\Request\NonExistingRequestNameException;
use Magento\Search\Api\SearchInterface;

/**
 * @since 1.0.0
 */
class Search
{

    /**
     * @var \Magento\Framework\Api\Search\SearchCriteriaBuilderFactory
     */
    private $searchCriteriaBuilderFactory;

    /**
     * @var \Magento\Search\Api\SearchInterface
     */
    private $search;

    /**
     * @var \Magento\Framework\Api\Search\SearchResultFactory
     */
    private $searchResultFactory;

    /**
     * @param \Magento\Framework\Api\Search\SearchCriteriaBuilderFactory $searchCriteriaBuilder
     * @param \Magento\Search\Api\SearchInterface                        $search
     * @param \Magento\Framework\Api\Search\SearchResultFactory          $searchResultFactory
     */
    public function __construct(
        SearchCriteriaBuilderFactory $searchCriteriaBuilder,
        SearchInterface $search,
        SearchResultFactory $searchResultFactory
    ) {
        $this->searchCriteriaBuilderFactory = $searchCriteriaBuilder;
        $this->search = $search;
        $this->searchResultFactory = $searchResultFactory;
    }

    /**
     * Search faced data by filters.
     *
     * @param string                          $field
     * @param \Magento\Framework\Api\Filter[] $filters
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\StateException
     */
    public function search(string $field, array $filters): array
    {
        $searchResult = $this->searchProducts($filters);
        return $this->extractFacedData($field, $searchResult);
    }

    /**
     * Extract faced data from search results.
     *
     * @param string                                              $field
     * @param \Magento\Framework\Api\Search\SearchResultInterface $searchResult
     * @return array
     * @throws \Magento\Framework\Exception\StateException
     */
    private function extractFacedData(string $field, SearchResultInterface $searchResult): array
    {
        $result = [];
        $aggregations = $searchResult->getAggregations();

        // This behavior is for case with empty object when we got EmptyRequestDataException
        if (null !== $aggregations) {
            $bucket = $aggregations->getBucket($field . RequestGenerator::BUCKET_SUFFIX);
            if ($bucket) {
                foreach ($bucket->getValues() as $value) {
                    $metrics = $value->getMetrics();
                    $result[$metrics['value']] = $metrics;
                }
            } else {
                throw new StateException(__("The bucket doesn't exist."));
            }
        }
        return $result;
    }

    /**
     * Search by filters.
     *
     * @param \Magento\Framework\Api\Filter[] $filters
     * @return \Magento\Framework\Api\Search\SearchResultInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function searchProducts(array $filters): SearchResultInterface
    {
        $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();

        $isSearch = false;
        foreach ($filters as $filter) {
            if ($filter->getField() === 'search_term') {
                $isSearch = true;
            }
            $searchCriteriaBuilder->addFilter($filter);
        }

        $searchCriteria = $searchCriteriaBuilder->create();
        $searchCriteria->setRequestName($isSearch ? 'quick_search_container' : 'catalog_view_container');
        $searchCriteria->setSortOrders([]);

        try {
            return $this->search->search($searchCriteria);
        } catch (EmptyRequestDataException $e) {
            return $this->searchResultFactory->create()->setItems([]);
        } catch (NonExistingRequestNameException $e) {
            throw new LocalizedException(__('An error occurred. For details, see the error log.'));
        }
    }
}
