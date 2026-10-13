<?php

namespace Fyb\Product\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Exception\LocalizedException;

class PalletDeliveryCart implements ObserverInterface
{
    /**
     * Observer for checkout_cart_product_add_before
     *
     * @param Observer $observer
     *
     * @return void
     */
    public function execute(Observer $observer)
    {
        $product = $observer->getProduct();

        if ($product->getData('pallet_delivery')) {
            throw new LocalizedException(__('Purchase can be only through a specialist retailer', $product->getName()));
        }

        return;
    }
}
