<?php
/**
 * @package     Plumrocket_ProductFilter
 * @copyright   Copyright (c) 2016 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

namespace Plumrocket\LayeredNavigationLite\Model\Filter;

use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\LocalizedException;
use Plumrocket\LayeredNavigationLite\Helper\Config;
use Plumrocket\LayeredNavigationLite\Helper\Elasticsearch;
use Plumrocket\LayeredNavigationLite\Helper\SearchEngine;
use Plumrocket\LayeredNavigationLite\Model\CollectionFilterApplier;
use Plumrocket\LayeredNavigationLite\Model\FacetedData\CategoryResolver;

/**
 * @since 1.0.0
 */
class Category extends \Magento\CatalogSearch\Model\Layer\Filter\Category
{

    /**
     * @var \Magento\Catalog\Model\Layer\Filter\DataProvider\Category
     */
    private $dataProvider;

    /**
     * @var \Plumrocket\LayeredNavigationLite\Helper\Config
     */
    private $config;

    /**
     * @var \Plumrocket\LayeredNavigationLite\Model\FacetedData\CategoryResolver
     */
    private $facetedDataResolver;

    /**
     * @var mixed|\Plumrocket\LayeredNavigationLite\Model\CollectionFilterApplier
     */
    private $collectionFilterApplier;

    /**
     * @var mixed|\Plumrocket\LayeredNavigationLite\Helper\SearchEngine
     */
    private $searchEngine;

    /**
     * @param \Magento\Catalog\Model\Layer\Filter\ItemFactory                      $filterItemFactory
     * @param \Magento\Store\Model\StoreManagerInterface                           $storeManager
     * @param \Magento\Catalog\Model\Layer                                         $layer
     * @param \Magento\Catalog\Model\Layer\Filter\Item\DataBuilder                 $itemDataBuilder
     * @param \Magento\Framework\Escaper                                           $escaper
     * @param \Magento\Catalog\Model\Layer\Filter\DataProvider\CategoryFactory     $categoryDataProviderFactory
     * @param \Plumrocket\LayeredNavigationLite\Helper\Elasticsearch               $elasticsearchHelper
     * @param \Plumrocket\LayeredNavigationLite\Helper\Config                      $config
     * @param \Plumrocket\LayeredNavigationLite\Model\FacetedData\CategoryResolver $facetedDataResolver
     * @param array                                                                $data
     * @param \Plumrocket\LayeredNavigationLite\Model\CollectionFilterApplier|null $collectionFilterApplier
     * @param \Plumrocket\LayeredNavigationLite\Helper\SearchEngine|null           $searchEngine
     */
    public function __construct(
        \Magento\Catalog\Model\Layer\Filter\ItemFactory $filterItemFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Model\Layer $layer,
        \Magento\Catalog\Model\Layer\Filter\Item\DataBuilder $itemDataBuilder,
        \Magento\Framework\Escaper $escaper,
        \Magento\Catalog\Model\Layer\Filter\DataProvider\CategoryFactory $categoryDataProviderFactory,
        Elasticsearch $elasticsearchHelper,
        Config $config,
        CategoryResolver $facetedDataResolver,
        array $data = [],
        CollectionFilterApplier $collectionFilterApplier = null,
        SearchEngine $searchEngine = null
    ) {
        parent::__construct(
            $filterItemFactory,
            $storeManager,
            $layer,
            $itemDataBuilder,
            $escaper,
            $categoryDataProviderFactory,
            $data
        );
        $this->dataProvider = $categoryDataProviderFactory->create(['layer' => $this->getLayer()]);
        $this->config = $config;
        $this->facetedDataResolver = $facetedDataResolver;
        $this->collectionFilterApplier = $collectionFilterApplier
            ?? ObjectManager::getInstance()->get(CollectionFilterApplier::class);
        $this->searchEngine = $searchEngine
            ?? ObjectManager::getInstance()->get(SearchEngine::class);
    }

    /**
     * @inheritDoc
     */
    public function apply(\Magento\Framework\App\RequestInterface $request)
    {
        if (! $this->config->isModuleEnabled() || ! $request->getParam($this->_requestVar)) {
            return parent::apply($request);
        }

        $categoryId = $request->getParam($this->_requestVar) ?: $request->getParam('id');
        if (empty($categoryId)) {
            return $this;
        }

        $categoryIds = explode(',', $categoryId);
        $this->dataProvider
            ->setCanProceed(true)
            ->setCategoryIds($categoryIds);

        $categories = $this->dataProvider->getCategories();

        $this->addCategoriesFilter($this->getLayer()->getProductCollection(), $categories->getAllIds());

        foreach ($categories as $category) {
            if (in_array($category->getId(), $categoryIds, false)) {
                $this->getLayer()
                    ->getState()
                    ->addFilter(
                        $this->_createItem(
                            $category->getName(),
                            $category->getId()
                        )->setIsActive(true)
                    );
            }
        }

        return $this;
    }

    /**
     * @inheritDoc
     */
    protected function _getItemsData()
    {
        if (! $this->config->isModuleEnabled()) {
            return parent::_getItemsData();
        }

        $currentCategory = $this->getLayer()->getCurrentCategory();
        if (! $currentCategory->getIsActive()) {
            return $this->itemDataBuilder->build();
        }

        try {
            return $this->facetedDataResolver->resolve(
                'cat',
                $currentCategory,
                $this->getLayer()
            );
        } catch (LocalizedException $exception) {
            return parent::_getItemsData();
        }
    }

    /**
     * Filter products by categories
     *
     * @param \Magento\Catalog\Model\ResourceModel\Product\Collection $collection
     * @param array                                                   $categoryIds
     * @return $this
     */
    protected function addCategoriesFilter(Collection $collection, array $categoryIds): Category
    {
        if ($this->searchEngine->isElasticSearch()
            || $this->searchEngine->isLiveSearch()
            || $this->searchEngine->isOpenSearch()
        ) {
            $this->collectionFilterApplier->applyInCondition(
                $collection,
                'category_ids',
                $categoryIds
            );
            return $this;
        }

        $connection = $collection->getConnection();
        $categorySelect = $connection->select()->from(
            ['cat' => $collection->getTable('catalog_category_product_index')],
            'cat.product_id'
        )->where($connection->prepareSqlCondition('cat.category_id', ['in' => $categoryIds]))
            ->where($connection->prepareSqlCondition('cat.store_id', ['eq' => $this->getStoreId()]));

        $collection->getSelect()->where(
            $connection->prepareSqlCondition('e.entity_id', ['in' => $categorySelect])
        );
        return $this;
    }
}
