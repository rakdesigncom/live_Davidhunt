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
use Magento\TestFramework\Fixture\DataFixtureInterface;
use Magento\Rma\Model\Shipping;
use Magento\Rma\Api\TrackRepositoryInterface;

class RmaShipping implements DataFixtureInterface
{

    /**
     * @var TrackRepositoryInterface
     */
    private $trackRepository;

    /**
     * @var Shipping
     */
    private $shipping;

    /**
     * @param TrackRepositoryInterface $trackRepository
     * @param Shipping $shipping
     */
    public function __construct(
        TrackRepositoryInterface $trackRepository,
        Shipping $shipping
    ) {
        $this->trackRepository = $trackRepository;
        $this->shipping = $shipping;
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
                        'field' => 'rma_id'
                    ]
                )
            );
        }

        $trackingNumber = $this->shipping->setRmaEntityId($data['rma_entity_id'])
            ->setCarrierTitle('CarrierTitle')
            ->setCarrierCode('custom')
            ->setTrackNumber('TrackNumber');

        /** @var TrackRepositoryInterface $trackRepository */
        return $this->trackRepository->save($trackingNumber);
    }
}
