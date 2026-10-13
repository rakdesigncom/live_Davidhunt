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

use Magento\AdobeCommerceEventsClient\Api\Data\EventProviderInterface;
use Magento\AdobeCommerceEventsClient\Event\EventProvider\EventProviderManagement;
use Magento\AdobeCommerceEventsClient\Event\InvalidConfigurationException;
use Magento\AdobeCommerceEventsClient\Event\Synchronizer\RegisteredEventsFetcher;
use Magento\AdobeIoEventsClient\Model\Data\EventMetadata;
use Magento\AdobeIoEventsClient\Model\Data\EventProvider;
use Magento\AdobeIoEventsClient\Model\Data\EventProviderFactory;
use Magento\AdobeIoEventsClient\Model\EventMetadataClient;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests for @see RegisteredEventsFetcher
 */
class RegisteredEventsFetcherTest extends TestCase
{
    /**
     * @var EventMetadataClient|MockObject
     */
    private $eventMetadataClientMock;

    /**
     * @var EventProviderManagement|MockObject
     */
    private $eventProviderManagementMock;

    /**
     * @var EventProviderFactory|MockObject
     */
    private $eventProviderFactoryMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var RegisteredEventsFetcher
     */
    private RegisteredEventsFetcher $registeredEventsFetcher;

    protected function setUp(): void
    {
        $this->eventMetadataClientMock = $this->createMock(EventMetadataClient::class);
        $this->eventProviderManagementMock = $this->createMock(EventProviderManagement::class);
        $this->eventProviderFactoryMock = $this->createMock(EventProviderFactory::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);

        $this->registeredEventsFetcher = new RegisteredEventsFetcher(
            $this->eventMetadataClientMock,
            $this->eventProviderManagementMock,
            $this->eventProviderFactoryMock,
            $this->loggerMock
        );
    }

    public function testGetRegisteredEvents()
    {
        $failedProviderId = '1';
        $successfulProviderId = '2';
        $providerOne = $this->createMock(EventProviderInterface::class);
        $providerOne->expects(self::once())
            ->method('getWorkspaceConfiguration')
            ->willReturn('testWorkspace');
        $providerOne->expects(self::exactly(3))
            ->method('getProviderId')
            ->willReturn($failedProviderId);
        $providerTwo = $this->createMock(EventProviderInterface::class);
        $providerTwo->expects(self::once())
            ->method('getWorkspaceConfiguration')
            ->willReturn('testWorkspace');
        $providerTwo->expects(self::exactly(2))
            ->method('getProviderId')
            ->willReturn($successfulProviderId);
        $providerThree = $this->createMock(EventProviderInterface::class);

        $this->eventProviderManagementMock->expects(self::once())
            ->method('getList')
            ->willReturn([$providerOne, $providerTwo, $providerThree]);

        $failedProvider = $this->createMock(EventProvider::class);
        $successfulProvider = $this->createMock(EventProvider::class);

        $this->eventProviderFactoryMock->expects(self::exactly(2))
            ->method('create')
            ->willReturnCallback(function (array $data) use (
                $failedProvider,
                $successfulProvider,
                $failedProviderId,
                $successfulProviderId
            ) {
                static $count = 0;
                self::assertArrayHasKey('data', $data);
                self::assertArrayHasKey('id', $data['data']);
                switch ($count++) {
                    case 0:
                        self::assertEquals($failedProviderId, $data['data']['id']);
                        return $failedProvider;
                    case 1:
                        self::assertEquals($successfulProviderId, $data['data']['id']);
                        return $successfulProvider;
                }
            });

        $eventCodeOne = 'event_code_one';
        $eventMetadataOne = $this->createMock(EventMetadata::class);
        $eventMetadataOne->method('getEventCode')
            ->willReturn($eventCodeOne);
        $eventCodeTwo = 'event_code_two';
        $eventMetadataTwo = $this->createMock(EventMetadata::class);
        $eventMetadataTwo->method('getEventCode')
            ->willReturn($eventCodeTwo);

        $this->eventMetadataClientMock->expects(self::exactly(2))
            ->method('listRegisteredEventMetadata')
            ->willReturnCallback(function (EventProvider $inputProvider) use (
                $failedProvider,
                $successfulProvider,
                $eventMetadataOne,
                $eventMetadataTwo
            ) {
                static $count = 0;
                switch ($count++) {
                    case 0:
                        self::assertEquals($failedProvider, $inputProvider);
                        throw new InvalidConfigurationException(__('invalid config'));
                    case 1:
                        self::assertEquals($successfulProvider, $inputProvider);
                        return [$eventMetadataOne, $eventMetadataTwo];
                }
            });

        $this->loggerMock->expects(self::once())
            ->method('error');

        $result = $this->registeredEventsFetcher->getRegisteredEvents();
        $this->assertTrue($result->hasProviderFailed($failedProviderId));
        $this->assertFalse($result->hasProviderFailed($successfulProviderId));
        $registeredEvents = $result->getRegisteredEventsForProvider($successfulProviderId);
        $this->assertCount(2, $registeredEvents);
        $this->assertContains($eventCodeOne, $registeredEvents);
        $this->assertContains($eventCodeTwo, $registeredEvents);
    }
}
