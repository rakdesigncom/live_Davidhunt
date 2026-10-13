<?php
/**
 * @package     Plumrocket_LayeredNavigationLite
 * @copyright   Copyright (c) 2022 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

namespace Plumrocket\LayeredNavigationLite\Model\Catalog\Layer\Category;

/**
 * Applies 'Enable Filter Attributes' option.
 *
 * @since 1.0.0
 */
class FilterableAttributeList extends \Magento\Catalog\Model\Layer\Category\FilterableAttributeList
{

    /**
     * @var \Plumrocket\LayeredNavigationLite\Helper\Config
     */
    protected $config;

    /**
     * @var \Plumrocket\LayeredNavigationLite\Helper\Config\Attribute
     */
    private $attributeConfig;

    /**
     * @param \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory $collectionFactory
     * @param \Magento\Store\Model\StoreManagerInterface                               $storeManager
     * @param \Plumrocket\LayeredNavigationLite\Helper\Config                          $config
     * @param \Plumrocket\LayeredNavigationLite\Helper\Config\Attribute                $attributeConfig
     */
    public function __construct(
        \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory $collectionFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Plumrocket\LayeredNavigationLite\Helper\Config $config,
        \Plumrocket\LayeredNavigationLite\Helper\Config\Attribute $attributeConfig
    ) {
        parent::__construct($collectionFactory, $storeManager);
        $this->config = $config;
        $this->attributeConfig = $attributeConfig;
    }

    /**
     * Add additional filter based on attributes that customer selected in admin panel.
     *
     * @param  \Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection $collection
     * @return \Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection
     */
    protected function _prepareAttributeCollection($collection)
    {
        if ($this->config->isModuleEnabled()) {
            $selectedAttributes = $this->attributeConfig->getSelectedAttributeCodes();
            $collection->addFieldToFilter('attribute_code', ['in' => $selectedAttributes]);
        }
        return parent::_prepareAttributeCollection($collection);
    }
}
