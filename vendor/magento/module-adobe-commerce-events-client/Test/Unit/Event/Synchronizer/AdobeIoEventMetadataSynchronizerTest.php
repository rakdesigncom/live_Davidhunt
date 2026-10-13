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

namespace Magento\AdobeCommerceEventsClient\Test\Unit\Event\Synchronizer;

use Magento\AdobeCommerceEventsClient\Event\Event;
use Magento\AdobeCommerceEventsClient\Event\EventInitializationException;
use Magento\AdobeCommerceEventsClient\Event\Synchronizer\AdobeIoEventMetadataSynchronizer;
use Magento\AdobeCommerceEventsClient\Event\Synchronizer\DefaultProviderRetriever;
use Magento\AdobeCommerceEventsClient\Event\Synchronizer\EventSyncList;
use Magento\AdobeCommerceEventsClient\Event\Synchronizer\RegisteredEventsFetcher;
use Magento\AdobeCommerceEventsClient\Event\Synchronizer\RegisteredEventsFetchResult;
use Magento\AdobeCommerceEventsClient\Event\Synchronizer\SingleEventSynchronizer;
use Magento\AdobeCommerceEventsClient\Event\Synchronizer\SynchronizerException;
use Magento\AdobeIoEventsClient\Api\EventProviderInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests for @see AdobeIoEventMetadataSynchronizer
 */
class AdobeIoEventMetadataSynchronizerTest extends TestCase
{
    /**
     * @var EventSyncList|MockObject
     */
    private $eventSyncListMock;

    /**
     * @var DefaultProviderRetriever|MockObject
     */
    private $defaultProviderRetrieverMock;

    /**
     * @var RegisteredEventsFetcher|MockObject
     */
    private $registeredEventsFetcherMock;

    /**
     * @var SingleEventSynchronizer|MockObject
     */
    private $singleEventSynchronizerMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var AdobeIoEventMetadataSynchronizer
     */
    private AdobeIoEventMetadataSynchronizer $metadataSynchronizer;

    protected function setUp(): void
    {
        $this->eventSyncListMock = $this->createMock(EventSyncList::class);
        $this->defaultProviderRetrieverMock = $this->createMock(DefaultProviderRetriever::class);
        $this->registeredEventsFetcherMock = $this->createMock(RegisteredEventsFetcher::class);
        $this->singleEventSynchronizerMock = $this->createMock(SingleEventSynchronizer::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);

        $this->metadataSynchronizer = new AdobeIoEventMetadataSynchronizer(
            $this->eventSyncListMock,
            $this->defaultProviderRetrieverMock,
            $this->registeredEventsFetcherMock,
            $this->singleEventSynchronizerMock,
            $this->loggerMock
        );
    }

    public function testRun()
    {
        // Event to successfully create metadata for
        $providerIdOne = 'providerOne';
        $eventOne = $this->createMock(Event::class);
        $eventOne->expects(self::once())
            ->method('getName')
            ->willReturn('eventOne');
        $eventOne->expects(self::once())
            ->method('getProviderId')
            ->willReturn($providerIdOne);

        // Event to fail due to exception
        $eventTwo = $this->createMock(Event::class);
        $eventTwoCode = 'eventTwo';
        $eventTwo->expects(self::exactly(2))
            ->method('getName')
            ->willReturn($eventTwoCode);
        $providerIdTwo = 'providerTwo';
        $eventTwo->expects(self::once())
            ->method('getProviderId')
            ->willReturn($providerIdTwo);

        // Event to fail due to provider failure
        $eventThree = $this->createMock(Event::class);
        $eventThreeCode = 'eventThree';
        $eventThree->expects(self::once())
            ->method('getName')
            ->willReturn($eventThreeCode);
        $eventThree->expects(self::once())
            ->method('getProviderId')
            ->willReturn('providerThree');

        // Event to skip due to no registered event result for its provider
        $eventFour = $this->createMock(Event::class);
        $eventFour->expects(self::never())
            ->method('getName');
        $eventFour->expects(self::once())
            ->method('getProviderId')
            ->willReturn('providerFour');

        $this->eventSyncListMock->expects(self::once())
            ->method('getList')
            ->willReturn([$eventOne, $eventTwo, $eventThree, $eventFour]);

        $defaultProvider = $this->createMock(EventProviderInterface::class);
        $this->defaultProviderRetrieverMock->expects(self::once())
            ->method('retrieve')
            ->willReturn($defaultProvider);

        $registeredEventsFetchResult = new RegisteredEventsFetchResult(
            ['providerOne' => ['other_event'], 'providerTwo' => []],
            ['providerThree']
        );
        $this->registeredEventsFetcherMock->expects(self::once())
            ->method('getRegisteredEvents')
            ->willReturn($registeredEventsFetchResult);

        $this->singleEventSynchronizerMock->expects(self::exactly(2))
            ->method('synchronize')
            ->willReturnCallback(function (
                $event,
                $providerId,
                $events,
                $provider
            ) use (
                $eventOne,
                $eventTwo,
                $providerIdOne,
                $providerIdTwo,
                $defaultProvider
            ) {
                static $count = 0;
                switch ($count++) {
                    case 0:
                        self::assertEquals($eventOne, $event);
                        self::assertEquals($providerIdOne, $providerId);
                        self::assertCount(1, $events);
                        self::assertEquals($defaultProvider, $provider);
                        return true;
                    case 1:
                        self::assertEquals($eventTwo, $event);
                        self::assertEquals($providerIdTwo, $providerId);
                        self::assertCount(0, $events);
                        self::assertEquals($defaultProvider, $provider);
                        throw new SynchronizerException(__('Test exception'));
                }
            });

        $this->loggerMock->expects(self::once())
            ->method('error');

        $metadataSyncResults = $this->metadataSynchronizer->run();
        $this->assertCount(1, $metadataSyncResults->getSuccessMessages());
        $this->assertCount(2, $metadataSyncResults->getFailedEvents());
        $this->assertContains($eventTwoCode, $metadataSyncResults->getFailedEvents());
        $this->assertContains($eventThreeCode, $metadataSyncResults->getFailedEvents());
    }

    public function testRunNoEvents()
    {
        $this->eventSyncListMock->expects(self::once())
            ->method('getList')
            ->willReturn([]);

        $this->defaultProviderRetrieverMock->expects(self::never())
            ->method('retrieve');
        $this->registeredEventsFetcherMock->expects(self::never())
            ->method('getRegisteredEvents');
        $this->singleEventSynchronizerMock->expects(self::never())
            ->method('synchronize');

        $metadataSyncResults = $this->metadataSynchronizer->run();
        self::assertCount(0, $metadataSyncResults->getSuccessMessages());
        self::assertCount(0, $metadataSyncResults->getFailedEvents());
    }

    public function testRunEventInitializationException()
    {
        $exceptionMessage = 'Exception message';
        $this->expectException(EventInitializationException::class);
        $this->expectExceptionMessage($exceptionMessage);

        $this->eventSyncListMock->expects(self::once())
            ->method('getList')
            ->willThrowException(new EventInitializationException(__($exceptionMessage)));

        $this->defaultProviderRetrieverMock->expects(self::never())
            ->method('retrieve');
        $this->registeredEventsFetcherMock->expects(self::never())
            ->method('getRegisteredEvents');
        $this->singleEventSynchronizerMock->expects(self::never())
            ->method('synchronize');

        $this->metadataSynchronizer->run();
    }

    public function testRunNoDefaultProvider()
    {
        $exceptionMessage = 'Exception message';
        $this->expectException(SynchronizerException::class);
        $this->expectExceptionMessage($exceptionMessage);

        $this->eventSyncListMock->expects(self::once())
            ->method('getList')
            ->willReturn([$this->createMock(Event::class)]);

        $this->defaultProviderRetrieverMock->expects(self::once())
            ->method('retrieve')
            ->willThrowException(new SynchronizerException(__($exceptionMessage)));
        $this->registeredEventsFetcherMock->expects(self::never())
            ->method('getRegisteredEvents');
        $this->singleEventSynchronizerMock->expects(self::never())
            ->method('synchronize');

        $this->metadataSynchronizer->run();
    }
}
