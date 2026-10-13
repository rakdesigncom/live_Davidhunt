<?php

namespace Fyb\Theme\Observer;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Exception\NoSuchEntityException;

class SetConfigurableName implements ObserverInterface
{
    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    public function __construct(
        ProductRepositoryInterface $productRepository
    ) {
        $this->productRepository = $productRepository;
    }

    /**
     * Observer for sales_quote_item_set_product
     *
     * @param Observer $observer
     * @return void
     * @throws NoSuchEntityException
     */
    public function execute(Observer $observer)
    {
        $quoteItem = $observer->getData('quote_item');

        if ($quoteItem->getProductType() == \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE) {
            $configurableSimpleProduct = $this->productRepository->get($quoteItem->getSku());
            $quoteItem->setName($configurableSimpleProduct->getName());
            $quoteItem->getProduct()->setName($configurableSimpleProduct->getName());
            $quoteItem->getProduct()->setIsSuperMode(true);
        }
    }
}
