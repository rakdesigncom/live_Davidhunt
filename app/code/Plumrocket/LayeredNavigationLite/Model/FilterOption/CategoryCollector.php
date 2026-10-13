<?php
/**
 * @package     Plumrocket_LayeredNavigationLite
 * @copyright   Copyright (c) 2022 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

declare(strict_types=1);

namespace Plumrocket\LayeredNavigationLite\Model\FilterOption;

use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;

/**
 * @since 1.0.0
 */
class CategoryCollector implements CollectorInterface
{

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory
     */
    private $categoryCollectionFactory;

    /**
     * @param \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryCollectionFactory
     */
    public function __construct(CollectionFactory $categoryCollectionFactory)
    {
        $this->categoryCollectionFactory = $categoryCollectionFactory;
    }

    /**
     * Collect options codes and labels.
     *
     * @param array $options
     * @return array
     */
    public function collect(array $options): array
    {
        $categories = $this->categoryCollectionFactory
            ->create()
            ->addAttributeToSelect('name')
            ->addAttributeToSelect('url_key')
            ->addFieldToFilter('is_active', 1);

        foreach ($categories as $category) {
            $categoryId = $category->getId();
            $options['cat'][$categoryId] = [
                'code' => (string) $categoryId,
                'label' => strip_tags((string) $category->getName()),
            ];
        }
        return $options;
    }
}
