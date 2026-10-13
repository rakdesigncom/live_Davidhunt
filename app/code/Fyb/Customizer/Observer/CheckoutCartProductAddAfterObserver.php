<?php

namespace Fyb\Customizer\Observer;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\StateException;

class CheckoutCartProductAddAfterObserver implements ObserverInterface
{

    protected $_request;

    /**
     * @param RequestInterface $request
     * @param \Magento\Checkout\Model\Cart $cart
     */
    public function __construct(
        RequestInterface $request,
    )
    {
        $this->_request = $request;
    }

    /**
     * @param EventObserver $observer
     *
     * @throws StateException
     */
    public function execute(EventObserver $observer)
    {
        if ($this->_request->getActionName() == 'reorder') {
            return;
        }

        $_item = $observer->getQuoteItem();
        $product = $observer->getProduct();

        if ($customPrice = $product->getCustomizerPrice()) {
            $_item->setCustomPrice($customPrice);
            $_item->setOriginalCustomPrice($customPrice);
        }

        if ($customizerImage = $product->getCustomizerImg()) {
            $_item->setCustomizerImg($customizerImage);
        }
    }
}
