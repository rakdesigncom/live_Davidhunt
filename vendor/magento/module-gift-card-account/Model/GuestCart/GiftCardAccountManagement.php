<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftCardAccount\Model\GuestCart;

use Magento\GiftCardAccount\Api\GuestGiftCardAccountManagementInterface;
use Magento\GiftCardAccount\Api\GiftCardAccountManagementInterface;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Magento\Framework\App\ObjectManager;
use Magento\Quote\Model\GuestCart\GetGuestCart;

/**
 * Gift card account management class for guest carts.
 */
class GiftCardAccountManagement implements GuestGiftCardAccountManagementInterface
{
    /**
     * @var GiftCardAccountManagementInterface
     */
    protected $giftCartAccountManagement;

    /**
     * @var QuoteIdMaskFactory
     */
    protected $quoteIdMaskFactory;

    /**
     * @var GetGuestCart|null
     */
    private $getGuestCart;

    /**
     * @param GiftCardAccountManagementInterface $giftCartAccountManagement
     * @param QuoteIdMaskFactory $quoteIdMaskFactory
     * @param GetGuestCart|null $getGuestCart
     */
    public function __construct(
        GiftCardAccountManagementInterface $giftCartAccountManagement,
        QuoteIdMaskFactory $quoteIdMaskFactory,
        ?GetGuestCart $getGuestCart = null
    ) {
        $this->giftCartAccountManagement = $giftCartAccountManagement;
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->getGuestCart = $getGuestCart ?? ObjectManager::getInstance()->get(GetGuestCart::class);
    }

    /**
     * @inheritDoc
     */
    public function addGiftCard(
        $cartId,
        \Magento\GiftCardAccount\Api\Data\GiftCardAccountInterface $giftCardAccountData
    ) {
        $quoteIdMask = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id');
        $this->getGuestCart->execute($cartId, (int)$quoteIdMask->getQuoteId());
        return $this->giftCartAccountManagement->saveByQuoteId($quoteIdMask->getQuoteId(), $giftCardAccountData);
    }

    /**
     * @inheritDoc
     */
    public function checkGiftCard($cartId, $giftCardCode)
    {
        $quoteIdMask = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id');
        $this->getGuestCart->execute($cartId, (int)$quoteIdMask->getQuoteId());
        return $this->giftCartAccountManagement->checkGiftCard($quoteIdMask->getQuoteId(), $giftCardCode);
    }

    /**
     * @inheritDoc
     */
    public function deleteByQuoteId($cartId, $giftCardCode)
    {
        $quoteIdMask = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id');
        $this->getGuestCart->execute($cartId, (int)$quoteIdMask->getQuoteId());
        return $this->giftCartAccountManagement->deleteByQuoteId($quoteIdMask->getQuoteId(), $giftCardCode);
    }
}
