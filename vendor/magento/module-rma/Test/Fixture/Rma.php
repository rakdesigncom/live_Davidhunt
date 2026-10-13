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
use Magento\Rma\Model\Rma\Source\Status;
use Magento\TestFramework\Fixture\Api\DataMerger;
use Magento\TestFramework\Fixture\Api\ServiceFactory;
use Magento\TestFramework\Fixture\RevertibleDataFixtureInterface;
use Magento\TestFramework\Fixture\Data\ProcessorInterface;

class Rma implements RevertibleDataFixtureInterface
{
    private const DEFAULT_DATA = [
        'status' => Status::STATE_APPROVED,
        'order_id' => null,
        'increment_id' => 1,
        'items' => [],
    ];

    /**
     * @var ServiceFactory
     */
    private ServiceFactory $serviceFactory;

    /**
     * @var DataMerger
     */
    private DataMerger $dataMerger;

    /**
     * @var ProcessorInterface
     */
    private ProcessorInterface $processor;

    /**
     * @var RmaRepositoryInterface
     */
    private RmaRepositoryInterface $rmaRepository;

    /**
     * @param ServiceFactory $serviceFactory
     * @param DataMerger $dataMerger
     * @param ProcessorInterface $processor
     * @param RmaRepositoryInterface $rmaRepository
     */
    public function __construct(
        ServiceFactory $serviceFactory,
        DataMerger $dataMerger,
        ProcessorInterface $processor,
        RmaRepositoryInterface $rmaRepository
    ) {
        $this->serviceFactory = $serviceFactory;
        $this->dataMerger = $dataMerger;
        $this->processor = $processor;
        $this->rmaRepository = $rmaRepository;
    }

    /**
     * @inheritdoc
     */
    public function apply(array $data = []): ?DataObject
    {
        if (empty($data['order_id'])) {
            throw new InvalidArgumentException(
                __(
                    '"%field" value is required to create an attribute',
                    [
                        'field' => 'order_id'
                    ]
                )
            );
        }

        $mergedData = $this->processor->process($this, $this->dataMerger->merge(self::DEFAULT_DATA, $data));

        /** @var RmaInterface $rma */
        return $this->serviceFactory->create(RmaRepositoryInterface::class, 'save')->execute(
            [
                'rmaDataObject' => $mergedData
            ]
        );
    }

    /**
     * @inheritdoc
     */
    public function revert(DataObject $data): void
    {
        $rma = $this->rmaRepository->get($data['entity_id']);
        $this->rmaRepository->delete($rma);
    }
}
