<?php
/************************************************************************
 *
 * ADOBE CONFIDENTIAL
 * ___________________
 *
 * Copyright 2024 Adobe
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
 * ************************************************************************
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Test\Unit\Controller\Adminhtml\Synchronization;

use Magento\AdobeCommerceEventsClient\Controller\Adminhtml\Synchronization\SynchronizeEvents;
use Magento\AdobeCommerceEventsClient\Event\Synchronizer\MetadataSynchronizerResults;
use Magento\AdobeCommerceEventsClient\Event\Synchronizer\SynchronizerException;
use Magento\AdobeCommerceEventsClient\Event\Synchronizer\AdobeIoEventMetadataSynchronizer;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\NotFoundException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests for @see SynchronizeEvents class
 */
class SynchronizeEventsTest extends TestCase
{
    /**
     * @var SynchronizeEvents
     */
    private SynchronizeEvents $synchronizeEvents;

    /**
     * @var Context|MockObject
     */
    private $contextMock;

    /**
     * @var JsonFactory|MockObject
     */
    private $jsonFactoryMock;

    /**
     * @var AdobeIoEventMetadataSynchronizer|MockObject
     */
    private $metadataSynchronizer;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    protected function setUp(): void
    {
        $this->contextMock = $this->createMock(Context::class);
        $this->jsonFactoryMock = $this->createMock(JsonFactory::class);
        $this->metadataSynchronizer = $this->createMock(AdobeIoEventMetadataSynchronizer::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);

        $this->synchronizeEvents = new SynchronizeEvents(
            $this->contextMock,
            $this->jsonFactoryMock,
            $this->metadataSynchronizer,
            $this->loggerMock
        );
    }

    public function testExecute(): void
    {
        $resultJsonMock = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->getMock();
        $resultJsonMock->expects(self::once())
            ->method('setData')
            ->with(['success' => true]);
        $this->jsonFactoryMock->expects(self::once())
            ->method('create')
            ->willReturn($resultJsonMock);

        $this->metadataSynchronizer->expects(self::once())
            ->method('run')
            ->willReturn(new MetadataSynchronizerResults(['success']));

        $this->loggerMock->expects(self::never())
            ->method('error');

        $this->synchronizeEvents->execute();
    }

    public function testExecuteWithFailedEvents(): void
    {
        $resultJsonMock = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->getMock();
        $resultJsonMock->expects(self::once())
            ->method('setData')
            ->with(['error' => 'Synchronization failed for the following: failedEvent1, failedEvent2']);
        $this->jsonFactoryMock->expects(self::once())
            ->method('create')
            ->willReturn($resultJsonMock);

        $this->metadataSynchronizer->expects(self::once())
            ->method('run')
            ->willReturn(new MetadataSynchronizerResults(['success'], ['failedEvent1', 'failedEvent2']));

        $this->loggerMock->expects(self::never())
            ->method('error');

        $this->synchronizeEvents->execute();
    }

    /**
     * @return void
     * @throws NotFoundException
     */
    public function testExecuteException(): void
    {
        $resultJsonMock = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->getMock();
        $resultJsonMock->expects(self::once())
            ->method('setData')
            ->with(['error' => 'Synchronization failed']);
        $this->jsonFactoryMock->expects(self::once())
            ->method('create')
            ->willReturn($resultJsonMock);

        $this->metadataSynchronizer->expects(self::once())
            ->method('run')
            ->willThrowException(new SynchronizerException(__('Synchronization failed')));

        $this->loggerMock->expects(self::once())
            ->method('error')
            ->with('Failed to synchronize events: Synchronization failed');

        $this->synchronizeEvents->execute();
    }
}
