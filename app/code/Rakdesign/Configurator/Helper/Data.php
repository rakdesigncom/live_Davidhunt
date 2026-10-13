<?php

namespace Rakdesign\Configurator\Helper;

use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ObjectManager;

class Data extends AbstractHelper
{
    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     */
    protected $productCollectionFactory;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory
     */
    public function __construct(
        Context $context,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);
        $this->productCollectionFactory = $productCollectionFactory;
        $this->storeManager = $storeManager;
    }

    /**
     * @return string
     */
    public function getBuildYourOwnLink()
    {
        /** @var \Magento\Catalog\Model\ResourceModel\Product\Collection $collection */
        $collection = $this->productCollectionFactory->create();
        $collection->addAttributeToFilter('combination_line_two', ['notnull' => true])
            ->addAttributeToFilter('status', Status::STATUS_ENABLED)
            ->addStoreFilter($this->storeManager->getStore())
            ->setPageSize(1);

        $product = $collection->getFirstItem();
        $url = '#';
        if ($product) {
            $url = $product->getProductUrl();
        }

        return $url;
    }
}
