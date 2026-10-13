<?php
/**
 * @package     Plumrocket_LayeredNavigationLite
 * @copyright   Copyright (c) 2022 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

namespace Plumrocket\LayeredNavigationLite\Model\Catalog\Layer\Filter\DataProvider;

use Magento\Framework\Exception\LocalizedException;

class Category extends \Magento\Catalog\Model\Layer\Filter\DataProvider\Category
{

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Category\Collection|null
     */
    protected $categories;

    /**
     * @var array
     */
    protected $categoryIds;

    /**
     * @var \Magento\Catalog\Model\CategoryFactory
     */
    protected $categoryFactory;

    /**
     * @var \Magento\Catalog\Model\Layer
     */
    protected $layer;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry;

    /**
     * Can procced filter logic
     *
     * @var boolean
     */
    protected $canProceed;

    /**
     * @var int
     */
    protected $categoryId;

    /**
     * @param \Magento\Framework\Registry            $coreRegistry
     * @param \Magento\Catalog\Model\CategoryFactory $categoryFactory
     * @param \Magento\Catalog\Model\Layer           $layer
     */
    public function __construct(
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        \Magento\Catalog\Model\Layer $layer
    ) {
        parent::__construct($coreRegistry, $categoryFactory, $layer);
        $this->coreRegistry = $coreRegistry;
        $this->layer = $layer;
        $this->categoryFactory = $categoryFactory;
    }

    /**
     * @inheritdoc
     */
    public function getCategory()
    {
        if ($this->canProceed) {
            return $this->getCategories();
        }

        return parent::getCategory();
    }

    /**
     * Retrieve categories
     *
     * @return \Magento\Catalog\Model\ResourceModel\Category\Collection
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getCategories()
    {
        if ($this->categories === null) {
            if ($this->categoryIds === null) {
                if ($this->categoryId) {
                    $this->categoryIds = [$this->categoryId];
                } elseif ($this->getLayer()->getCurrentCategory()) {
                    $this->categoryIds = [$this->getLayer()->getCurrentCategory()->getId()];
                }
            }

            if (!is_array($this->categoryIds)) {
                throw new LocalizedException(__('Category Ids must be array'));
            }

            /** @var \Magento\Catalog\Model\ResourceModel\Category\Collection $categories */
            $categories = $this->categoryFactory->create()
                ->setStoreId(
                    $this->getLayer()
                        ->getCurrentStore()
                        ->getId()
                )
                ->getCollection()
                ->addAttributeToSelect(['name', 'url_key'])
                ->addFieldToFilter('entity_id', ['in' => $this->categoryIds]);

            $this->coreRegistry->register('current_category_filter', $categories, true);
            $this->categories = $categories;
        }

        return $this->categories;
    }

    /**
     * Can proceed
     *
     * @param bool $val
     */
    public function setCanProceed($val)
    {
        $this->canProceed = (bool) $val;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setCategoryId($categoryId)
    {
        parent::setCategoryId($categoryId);
        $this->categoryId = $categoryId;
        return $this;
    }

    /**
     * Category
     *
     * @param array $ids
     */
    public function setCategoryIds($ids)
    {
        if (!is_array($ids)) {
            $ids = [$ids];
        }

        $this->categoryIds = $ids;
        return $this;
    }

    /**
     * Get layer
     *
     * @return \Magento\Catalog\Model\Layer
     */
    protected function getLayer()
    {
        return $this->layer;
    }
}
