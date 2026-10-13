<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftRegistry\Model\GuestCart;

use Magento\GiftRegistry\Api\ShippingMethodManagementInterface;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Magento\Framework\App\ObjectManager;
use Magento\Quote\Model\GuestCart\GetGuestCart;

/**
 * Shipping method read service.
 */
class ShippingMethodManagement implements \Magento\GiftRegistry\Api\GuestCart\ShippingMethodManagementInterface
{
    /**
     * @var ShippingMethodManagementInterface
     */
    protected $shippingMethodManagement;

    /**
     * @var QuoteIdMaskFactory
     */
    protected $quoteIdMaskFactory;

    /**
     * @var GetGuestCart|null
     */
    private $getGuestCart;

    /**
     * @param ShippingMethodManagementInterface $shippingMethodManagement
     * @param QuoteIdMaskFactory $quoteIdMaskFactory
     * @param GetGuestCart|null $getGuestCart
     */
    public function __construct(
        ShippingMethodManagementInterface $shippingMethodManagement,
        QuoteIdMaskFactory $quoteIdMaskFactory,
        ?GetGuestCart $getGuestCart = null
    ) {
        $this->shippingMethodManagement = $shippingMethodManagement;
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->getGuestCart = $getGuestCart ?? ObjectManager::getInstance()->get(GetGuestCart::class);
    }

    /**
     * @inheritDoc
     */
    public function estimateByRegistryId($cartId, $registryId)
    {
        $quoteIdMask = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id');
        $this->getGuestCart->execute($cartId, (int)$quoteIdMask->getQuoteId());
        return $this->shippingMethodManagement->estimateByRegistryId($quoteIdMask->getQuoteId(), $registryId);
    }
}
