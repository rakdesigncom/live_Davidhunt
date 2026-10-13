<?php
/**
 * @package     Plumrocket_LayeredNavigationLite
 * @copyright   Copyright (c) 2022 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

namespace Plumrocket\LayeredNavigationLite\Plugin\Model\CatalogSearch\Layer\Category;

/**
 * @since 1.0.0
 */
class ItemCollectionProvider
{

    /**
     * @var \Plumrocket\LayeredNavigationLite\Helper\Config
     */
    protected $config;

    /**
     * @var \Plumrocket\LayeredNavigationLite\Model\ResourceModel\Product\CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @param \Plumrocket\LayeredNavigationLite\Model\ResourceModel\Product\CollectionFactory $collectionFactory
     * @param \Plumrocket\LayeredNavigationLite\Helper\Config                                 $config
     */
    public function __construct(
        \Plumrocket\LayeredNavigationLite\Model\ResourceModel\Product\CollectionFactory $collectionFactory,
        \Plumrocket\LayeredNavigationLite\Helper\Config $config
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->config = $config;
    }

    /**
     * Around get collection
     *
     * @param \Magento\CatalogSearch\Model\Layer\Category\ItemCollectionProvider $provider
     * @param \Closure                                                           $result
     * @param \Magento\Catalog\Model\Category                                    $category
     * @return array
     */
    public function aroundGetCollection(
        \Magento\CatalogSearch\Model\Layer\Category\ItemCollectionProvider $provider,
        $result,
        \Magento\Catalog\Model\Category $category
    ) {

        if ($this->config->isModuleEnabled()) {
            $collection = $this->collectionFactory->create();
            $collection->addCategoryFilter($category);
            return $collection;
        }

        return $result($category);
    }
}
