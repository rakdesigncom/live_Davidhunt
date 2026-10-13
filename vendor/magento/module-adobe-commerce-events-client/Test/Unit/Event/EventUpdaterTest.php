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

namespace Magento\AdobeCommerceEventsClient\Test\Unit\Event;

use Magento\AdobeCommerceEventsClient\Event\Data\EventData;
use Magento\AdobeCommerceEventsClient\Event\Data\EventDataConverter;
use Magento\AdobeCommerceEventsClient\Event\Event;
use Magento\AdobeCommerceEventsClient\Event\EventInitializationException;
use Magento\AdobeCommerceEventsClient\Event\EventList;
use Magento\AdobeCommerceEventsClient\Event\EventSubscriptionUpdaterInterface;
use Magento\AdobeCommerceEventsClient\Event\EventUpdater;
use Magento\AdobeCommerceEventsClient\Event\Merger\EventMerger;
use Magento\AdobeCommerceEventsClient\Event\Validator\ValidatorException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Tests for @see EventUpdater class
 */
class EventUpdaterTest extends TestCase
{
    /**
     * @var EventSubscriptionUpdaterInterface|MockObject
     */
    private EventSubscriptionUpdaterInterface|MockObject $eventSubscriptionUpdaterMock;

    /**
     * @var EventList|MockObject
     */
    private EventList|MockObject $eventListMock;

    /**
     * @var EventMerger|MockObject
     */
    private EventMerger|MockObject $eventMergerMock;

    /**
     * @var EventDataConverter|MockObject
     */
    private EventDataConverter|MockObject $eventDataConverterMock;

    /**
     * @var EventUpdater
     */
    private EventUpdater $eventUpdater;

    protected function setUp(): void
    {
        $this->eventSubscriptionUpdaterMock = $this->createMock(EventSubscriptionUpdaterInterface::class);
        $this->eventListMock = $this->createMock(EventList::class);
        $this->eventMergerMock = $this->createMock(EventMerger::class);
        $this->eventDataConverterMock = $this->createMock(EventDataConverter::class);

        $this->eventUpdater = new EventUpdater(
            $this->eventSubscriptionUpdaterMock,
            $this->eventListMock,
            $this->eventMergerMock,
            $this->eventDataConverterMock,
        );
    }

    public function testUpdateSuccess()
    {
        $eventName = 'observer.test';
        $eventDataMock = $this->createMock(EventData::class);
        $eventDataMock->expects(self::once())
            ->method('getName')
            ->willReturn($eventName);

        $baseEventMock = $this->createMock(Event::class);
        $baseEventMock->expects(self::once())
            ->method('isEnabled')
            ->willReturn(true);
        $eventToMergeMock = $this->createMock(Event::class);
        $updatedEventMock = $this->createMock(Event::class);

        $this->eventListMock->expects(self::once())
            ->method('get')
            ->with($eventName)
            ->willReturn($baseEventMock);
        $this->eventDataConverterMock->expects(self::once())
            ->method('convert')
            ->with($eventDataMock)
            ->willReturn($eventToMergeMock);
        $this->eventMergerMock->expects(self::once())
            ->method('merge')
            ->with($baseEventMock, $eventToMergeMock)
            ->willReturn($updatedEventMock);
        $this->eventSubscriptionUpdaterMock->expects(self::once())
            ->method('update')
            ->with($updatedEventMock);

        $this->eventUpdater->update($eventDataMock);
    }

    public function testUpdateEventInitializationException()
    {
        $eventInitializationExceptionMessage = 'error';
        $this->expectException(ValidatorException::class);
        $this->expectExceptionMessage(
            sprintf(
                'Unable to read the list of current event subscriptions: %s',
                $eventInitializationExceptionMessage
            )
        );

        $eventName = 'observer.test';
        $eventDataMock = $this->createMock(EventData::class);
        $eventDataMock->expects(self::once())
            ->method('getName')
            ->willReturn($eventName);

        $this->eventListMock->expects(self::once())
            ->method('get')
            ->willThrowException(new EventInitializationException(__($eventInitializationExceptionMessage)));
        $this->eventDataConverterMock->expects(self::never())
            ->method('convert');
        $this->eventMergerMock->expects(self::never())
            ->method('merge');
        $this->eventSubscriptionUpdaterMock->expects(self::never())
            ->method('update');

        $this->eventUpdater->update($eventDataMock);
    }

    public function testUpdateNonexistentSubscription()
    {
        $eventName = 'observer.test';
        $this->expectException(ValidatorException::class);
        $this->expectExceptionMessage(
            sprintf(
                'Unable to update the event subscription. A subscription for %s does not exist',
                $eventName
            )
        );

        $eventDataMock = $this->createMock(EventData::class);
        $eventDataMock->expects(self::exactly(2))
            ->method('getName')
            ->willReturn($eventName);

        $this->eventListMock->expects(self::once())
            ->method('get')
            ->with($eventName);
        $this->eventDataConverterMock->expects(self::never())
            ->method('convert');
        $this->eventMergerMock->expects(self::never())
            ->method('merge');
        $this->eventSubscriptionUpdaterMock->expects(self::never())
            ->method('update');

        $this->eventUpdater->update($eventDataMock);
    }

    public function testUpdateDisabledSubscription()
    {
        $eventName = 'observer.test';
        $this->expectException(ValidatorException::class);
        $this->expectExceptionMessage(
            sprintf(
                'Unable to update the event subscription. The subscription for %s is not enabled',
                $eventName
            )
        );

        $eventDataMock = $this->createMock(EventData::class);
        $eventDataMock->expects(self::exactly(2))
            ->method('getName')
            ->willReturn($eventName);
        $eventDataMock->expects(self::exactly(2))
            ->method('getName')
            ->willReturn($eventName);

        $eventMock = $this->createMock(Event::class);
        $eventMock->expects(self::once())
            ->method('isEnabled')
            ->willReturn(false);

        $this->eventListMock->expects(self::once())
            ->method('get')
            ->with($eventName)
            ->willReturn($eventMock);

        $this->eventDataConverterMock->expects(self::never())
            ->method('convert');
        $this->eventMergerMock->expects(self::never())
            ->method('merge');
        $this->eventSubscriptionUpdaterMock->expects(self::never())
            ->method('update');

        $this->eventUpdater->update($eventDataMock);
    }
}
