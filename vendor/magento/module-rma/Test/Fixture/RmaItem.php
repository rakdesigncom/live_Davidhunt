<?php
/************************************************************************
 * Copyright 2025 Adobe
 * All Rights Reserved.
 *
 * NOTICE: All information contained herein is, and remains
 * the property of Adobe and its suppliers, if any. The intellectual
 * and technical concepts contained herein are proprietary to Adobe
 * and its suppliers and are protected by all applicable intellectual
 * property laws, including trade secret and copyright laws.
 * Dissemination of this information or reproduction of this material
 * is strictly forbidden unless prior written permission is obtained
 * from Adobe.
 * ***********************************************************************
 */
declare(strict_types=1);

namespace Magento\Rma\Test\Fixture;

use Magento\Framework\DataObject;
use Magento\Framework\Exception\InvalidArgumentException;
use Magento\Rma\Api\Data\RmaInterface;
use Magento\Rma\Api\RmaRepositoryInterface;
use Magento\Rma\Model\Item;
use Magento\Rma\Model\ItemFactory;
use Magento\Rma\Model\Rma\Source\Status;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Model\OrderRepository;
use Magento\TestFramework\Fixture\DataFixtureInterface;

class RmaItem implements DataFixtureInterface
{
    private const DEFAULT_DATA = [
        'rma_entity_id' => null,
        'qty_requested' => 1,
        'qty_authorized' => 1,
        'qty_returned' => 1,
        'qty_approved' => 1,
        'status' => Status::STATE_AUTHORIZED,
    ];

    /**
     * @var RmaRepositoryInterface
     */
    private RmaRepositoryInterface $rmaRepository;

    /**
     * @var ItemFactory
     */
    private $rmaItemFactory;

    /**
     * @var OrderRepository
     */
    private $orderRepository;

    /**
     * @param RmaRepositoryInterface $rmaRepository
     * @param ItemFactory $rmaItemFactory
     * @param OrderRepository $orderRepository
     */
    public function __construct(
        RmaRepositoryInterface $rmaRepository,
        ItemFactory $rmaItemFactory,
        OrderRepository $orderRepository
    ) {
        $this->rmaRepository = $rmaRepository;
        $this->rmaItemFactory = $rmaItemFactory;
        $this->orderRepository = $orderRepository;
    }

    /**
     * @inheritdoc
     */
    public function apply(array $data = []): ?DataObject
    {
        if (empty($data['rma_entity_id'])) {
            throw new InvalidArgumentException(
                __(
                    '"%field" value is required to create a RMA item',
                    [
                        'field' => 'rma_entity_id'
                    ]
                )
            );
        }

        $rma = $this->rmaRepository->get($data['rma_entity_id']);

        if (empty($data['sku'])) {
            throw new InvalidArgumentException(
                __(
                    '"%field" value is required to create a RMA item',
                    [
                        'field' => 'sku'
                    ]
                )
            );
        }

        $order = $this->orderRepository->get($rma->getOrderId());

        foreach ($order->getItems() as $orderItem) {
            if ($orderItem->getSku() === $data['sku']) {
                return $this->getRmaItem($orderItem, $data, $rma);
            }
        }

        throw new InvalidArgumentException(__('SKU not found'));
    }

    /**
     * Add an RMA item to an RMA
     *
     * @param OrderItemInterface $orderItem
     * @param array $data
     * @param RmaInterface $rma
     * @return Item
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    private function getRmaItem(OrderItemInterface $orderItem, array $data, RmaInterface $rma): Item
    {
        $data['order_item_id'] = $orderItem->getItemId();
        $rmaItem = $this->rmaItemFactory->create();
        $rmaItem->setData(array_merge($data, self::DEFAULT_DATA));
        $rma->setItems([$rmaItem]);
        $this->rmaRepository->save($rma);
        return $rmaItem;
    }
}
