<?php
/************************************************************************
 *
 * ADOBE CONFIDENTIAL
 * ___________________
 *
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
 * ************************************************************************
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Test\Unit\Event\EventStorageWriter;

use Magento\AdobeCommerceEventsClient\Event\Config;
use Magento\AdobeCommerceEventsClient\Event\Event;
use Magento\AdobeCommerceEventsClient\Event\EventStorageWriter\EventDataSizeValidator;
use Magento\Framework\Serialize\Serializer\Json;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use InvalidArgumentException;

/**
 * Tests for EventDataSizeValidator class
 */
class EventDataSizeValidatorTest extends TestCase
{
    /**
     * @var EventDataSizeValidator
     */
    private EventDataSizeValidator $validator;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var Config|MockObject
     */
    private $configMock;

    /**
     * @var Json|MockObject
     */
    private $jsonMock;

    /**
     * @var Event|MockObject
     */
    private $eventMock;

    protected function setUp(): void
    {
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->configMock = $this->createMock(Config::class);
        $this->jsonMock = $this->createMock(Json::class);
        $this->eventMock = $this->createMock(Event::class);

        $this->validator = new EventDataSizeValidator(
            $this->loggerMock,
            $this->configMock,
            $this->jsonMock
        );
    }

    public function testValidateReturnsTrueWhenSizeIsWithinLimit(): void
    {
        $eventData = ['foo' => 'bar'];
        $this->eventMock->expects(self::never())
            ->method('getName');
        $this->jsonMock->expects(self::once())
            ->method('serialize')
            ->with($eventData)
            ->willReturn('{"foo":"bar"}');
        $this->configMock->expects(self::once())
            ->method('getMaxEventDataSize')
            ->willReturn(100);
        $this->loggerMock->expects(self::never())->method('error');

        self::assertTrue($this->validator->validate($this->eventMock, $eventData));
    }

    public function testValidateReturnsFalseWhenSizeExceedsLimit(): void
    {
        $eventData = [];
        for ($i = 1; $i <= 3500; $i++) {
            $eventData["foo_$i"] = "bar_$i";
        }
        $this->eventMock->expects(self::once())
            ->method('getName')
            ->willReturn('event_name');
        $this->jsonMock->expects(self::once())
            ->method('serialize')
            ->with($eventData)
            ->willReturn(json_encode($eventData));
        $this->configMock->expects(self::once())
            ->method('getMaxEventDataSize')
            ->willReturn(65536);
        $this->loggerMock->expects(self::once())
            ->method('error')
            ->with(
                self::stringContains('Event data size for the event "event_name" exceeds maximum allowed size'),
                ['destination' => ['internal', 'external']]
            );

        self::assertFalse($this->validator->validate($this->eventMock, $eventData));
    }

    public function testValidateReturnsFalseOnSerializationException(): void
    {
        $eventData = ['foo' => 'bar'];
        $this->eventMock->expects(self::once())
            ->method('getName')
            ->willReturn('event_name');
        $this->jsonMock->expects(self::once())
            ->method('serialize')
            ->with($eventData)
            ->willThrowException(new InvalidArgumentException('bad json'));
        $this->loggerMock->expects(self::once())
            ->method('error')
            ->with(
                self::stringContains('Failed to serialize event data for the event "event_name"'),
                ['destination' => ['internal', 'external']]
            );

        self::assertFalse($this->validator->validate($this->eventMock, $eventData));
    }
}
