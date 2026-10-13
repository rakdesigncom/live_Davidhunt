<?php

namespace Fyb\Theme\Block;

use \Magento\Store\Model\Store;

class StoreInfo extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Magento\Store\Model\Information
     */
    protected $storeInfo;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory
     */
    protected $categoryCollectionFactory;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Store\Model\Information $storeInfo
     * @param \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryCollectionFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Store\Model\Information $storeInfo,
        \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryCollectionFactory,
        array $data = []
    ) {
        $this->storeInfo = $storeInfo;
        $this->categoryCollectionFactory = $categoryCollectionFactory;

        parent::__construct($context, $data);
    }

    /**
     * @return \Magento\Framework\DataObject
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getStoreInformationObject()
    {
        return $this->storeInfo->getStoreInformationObject($this->_storeManager->getStore());
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getStoreShortAddress()
    {
        $storeData = $this->getStoreInformationObject();

        return $storeData->getData('street_line1') . ' ' .
            $storeData->getRegionId() . ', ' .
            $storeData->getPostcode();
    }

    public function getCategories()
    {
        /** @var \Magento\Catalog\Model\ResourceModel\Category\Collection $collection */
        $collection = $this->categoryCollectionFactory->create();
        $collection->setStore($this->_storeManager->getStore())
            ->setStoreId($this->_storeManager->getStore()->getId())
            ->addFieldToFilter('is_active', 1);

        if ($this->_storeManager->getStore()->getCode() == 'dhl') {
            $collection->addFieldToFilter('name', ['eq' => 'Categories']);
        } else {
            $collection->addFieldToFilter('name', ['eq' => 'Bespoke Shades & Lights']);
        }

        return $collection->getFirstItem()->getChildrenCategories();
    }
}
