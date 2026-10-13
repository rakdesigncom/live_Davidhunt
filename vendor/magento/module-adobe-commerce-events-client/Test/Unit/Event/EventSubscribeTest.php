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

namespace Magento\AdobeCommerceEventsClient\Test\Unit\Event;

use Magento\AdobeCommerceEventsClient\Api\Data\EventDataInterface;
use Magento\AdobeCommerceEventsClient\Event\Data\EventDataConverter;
use Magento\AdobeCommerceEventsClient\Event\Event;
use Magento\AdobeCommerceEventsClient\Event\EventList;
use Magento\AdobeCommerceEventsClient\Event\EventProvider\ProviderConfigChecker;
use Magento\AdobeCommerceEventsClient\Event\EventSubscribe;
use Magento\AdobeCommerceEventsClient\Event\EventSubscriber;
use Magento\AdobeCommerceEventsClient\Event\Validator\ValidatorException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Tests for @see EventSubscribe class
 */
class EventSubscribeTest extends TestCase
{
    /**
     * @var EventDataConverter|MockObject
     */
    private EventDataConverter|MockObject $eventDataConverterMock;

    /**
     * @var EventSubscriber|MockObject
     */
    private EventSubscriber|MockObject $eventSubscriberMock;

    /**
     * @var EventList|MockObject
     */
    private EventList|MockObject $eventListMock;

    /**
     * @var ProviderConfigChecker|MockObject
     */
    private ProviderConfigChecker|MockObject $providerConfigCheckerMock;

    /**
     * @var EventSubscribe
     */
    private EventSubscribe $eventSubscribe;

    protected function setUp(): void
    {
        $this->eventDataConverterMock = $this->createMock(EventDataConverter::class);
        $this->eventSubscriberMock = $this->createMock(EventSubscriber::class);
        $this->eventListMock = $this->createMock(EventList::class);
        $this->providerConfigCheckerMock = $this->createMock(ProviderConfigChecker::class);

        $this->eventSubscribe = new EventSubscribe(
            $this->eventDataConverterMock,
            $this->eventSubscriberMock,
            $this->eventListMock,
            $this->providerConfigCheckerMock
        );
    }

    public function testSubscribeProviderIsNotConfigured()
    {
        $this->expectException(ValidatorException::class);
        $this->expectExceptionMessage('No event provider is configured. Please configure the event provider');

        $providerId = 'providerId';
        $eventDataMock = $this->createMock(EventDataInterface::class);
        $eventDataMock->expects(self::once())
            ->method('getProviderId')
            ->willReturn($providerId);

        $this->providerConfigCheckerMock->expects(self::once())
            ->method('check')
            ->with($providerId)
            ->willReturn(false);
        $this->eventDataConverterMock->expects(self::never())
            ->method('convert');
        $this->eventSubscriberMock->expects(self::never())
            ->method('subscribe');

        $this->eventSubscribe->subscribe($eventDataMock);
    }

    public function testSuccessSubscription()
    {
        $eventDataMock = $this->createMock(EventDataInterface::class);
        $providerId = 'providerId';
        $eventDataMock->expects(self::once())
            ->method('getProviderId')
            ->willReturn($providerId);

        $this->providerConfigCheckerMock->expects(self::once())
            ->method('check')
            ->willReturn(true);
        $eventMock = $this->createMock(Event::class);
        $this->eventDataConverterMock->expects(self::once())
            ->method('convert')
            ->with($eventDataMock)
            ->willReturn($eventMock);
        $this->eventSubscriberMock->expects(self::once())
            ->method('subscribe')
            ->with($eventMock, true);

        $this->eventSubscribe->subscribe($eventDataMock, true);
    }

    public function testUnsubscribeProviderIsNotConfigured()
    {
        $this->expectException(ValidatorException::class);
        $this->expectExceptionMessage('No event provider is configured. Please configure the event provider');

        $eventName = 'observer.test';
        $eventMock = $this->createMock(Event::class);
        $this->eventListMock->expects(self::once())
            ->method('get')
            ->with($eventName)
            ->willReturn($eventMock);

        $providerId = 'providerId';
        $eventMock->expects(self::once())
            ->method('getProviderId')
            ->willReturn($providerId);

        $this->providerConfigCheckerMock->expects(self::once())
            ->method('check')
            ->with($providerId)
            ->willReturn(false);
        $this->eventSubscriberMock->expects(self::never())
            ->method('unsubscribe');

        $this->eventSubscribe->unsubscribe($eventName);
    }

    public function testSuccessUnsubscribe()
    {
        $eventName = 'observer.test';
        $eventMock = $this->createMock(Event::class);
        $this->eventListMock->expects(self::once())
            ->method('get')
            ->with($eventName)
            ->willReturn($eventMock);

        $providerId = 'providerId';
        $eventMock->expects(self::once())
            ->method('getProviderId')
            ->willReturn($providerId);

        $this->providerConfigCheckerMock->expects(self::once())
            ->method('check')
            ->with($providerId)
            ->willReturn(true);
        $this->eventSubscriberMock->expects(self::once())
            ->method('unsubscribe')
            ->with($eventMock);

        $this->eventSubscribe->unsubscribe($eventName);
    }
}
