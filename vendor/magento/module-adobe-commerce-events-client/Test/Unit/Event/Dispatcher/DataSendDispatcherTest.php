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

namespace Magento\AdobeCommerceEventsClient\Test\Unit\Event\Dispatcher;

use Magento\AdobeCommerceEventsClient\Event\Dispatcher\DataSendDispatcher;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests for @see DataSendDispatcher
 */
class DataSendDispatcherTest extends TestCase
{
    /**
     * @var ManagerInterface|MockObject
     */
    private ManagerInterface|MockObject $eventManagerMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private LoggerInterface|MockObject $loggerMock;

    /**
     * @var DateTime|MockObject
     */
    private DateTime|MockObject $dateTimeMock;

    /**
     * @var string
     */
    private string $senderName;

    /**
     * @var DataSendDispatcher
     */
    private DataSendDispatcher $dataSendDispatcher;

    protected function setUp(): void
    {
        $this->eventManagerMock = $this->createMock(ManagerInterface::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->dateTimeMock = $this->createMock(DateTime::class);
        $this->senderName = 'Test Sender';

        $this->dataSendDispatcher = new DataSendDispatcher(
            $this->eventManagerMock,
            $this->loggerMock,
            $this->dateTimeMock,
            $this->senderName
        );
    }

    public function testDispatch(): void
    {
        $payload = ['key' => 'value'];
        $type = 'testType';
        $this->dateTimeMock->expects(self::once())
            ->method('timestamp')
            ->willReturn(1234567890);
        $this->eventManagerMock->expects(self::once())
            ->method('dispatch')
            ->with(
                'data_sent_outside',
                [
                    'sender' => $this->senderName,
                    'destination' => 'Commerce Eventing Service',
                    'timestamp' => 1234567890,
                    'type' => $type,
                    'data' => $payload
                ]
            );

        $this->dataSendDispatcher->dispatch($payload, $type);
    }

    public function testDispatchWithException(): void
    {
        $payload = ['key' => 'value'];
        $type = 'testType';
        $exceptionMessage = 'Test exception';

        $this->dateTimeMock->expects(self::once())
            ->method('timestamp')
            ->willReturn(1234567890);
        $this->eventManagerMock->expects(self::once())
            ->method('dispatch')
            ->will($this->throwException(new \Exception($exceptionMessage)));
        $this->loggerMock->expects(self::once())
            ->method('error')
            ->with('Error while dispatching data sent to the eventing service: ' . $exceptionMessage);

        $this->dataSendDispatcher->dispatch($payload, $type);
    }
}
