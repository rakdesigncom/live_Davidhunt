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

use Magento\AdobeCommerceEventsClient\Event\AdobeIoEventMetadataFactory;
use Magento\AdobeCommerceEventsClient\Event\Event;
use Magento\AdobeCommerceEventsClient\Event\EventSubscriberInterface;
use Magento\AdobeCommerceEventsClient\Event\ProviderConfigurationBuilder;
use Magento\AdobeCommerceEventsClient\Event\Synchronizer\SingleEventSynchronizer;
use Magento\AdobeCommerceEventsClient\Event\Synchronizer\SynchronizerException;
use Magento\AdobeIoEventsClient\Api\EventMetadataInterface;
use Magento\AdobeIoEventsClient\Api\EventProviderInterface;
use Magento\AdobeIoEventsClient\Model\Data\AdobeConsoleConfiguration\AdobeConsoleConfiguration;
use Magento\AdobeIoEventsClient\Model\Data\EventProvider;
use Magento\AdobeIoEventsClient\Model\Data\EventProviderFactory;
use Magento\AdobeIoEventsClient\Model\EventMetadataClient;
use Magento\Framework\Exception\NoSuchEntityException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Tests for @see SingleEventSynchronizer
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SingleEventSynchronizerTest extends TestCase
{
    /**
     * @var EventMetadataClient|MockObject
     */
    private $eventMetadataClientMock;

    /**
     * @var AdobeIoEventMetadataFactory|MockObject
     */
    private $ioMetadataFactoryMock;

    /**
     * @var EventProviderFactory|MockObject
     */
    private $eventProviderFactoryMock;

    /**
     * @var ProviderConfigurationBuilder|MockObject
     */
    private $providerConfigurationBuilderMock;

    /**
     * @var SingleEventSynchronizer
     */
    private SingleEventSynchronizer $singleEventSynchronizer;

    protected function setUp(): void
    {
        $this->eventMetadataClientMock = $this->createMock(EventMetadataClient::class);
        $this->ioMetadataFactoryMock = $this->createMock(AdobeIoEventMetadataFactory::class);
        $this->eventProviderFactoryMock = $this->createMock(EventProviderFactory::class);
        $this->providerConfigurationBuilderMock = $this->createMock(ProviderConfigurationBuilder::class);

        $this->singleEventSynchronizer = new SingleEventSynchronizer(
            $this->eventMetadataClientMock,
            $this->ioMetadataFactoryMock,
            $this->eventProviderFactoryMock,
            $this->providerConfigurationBuilderMock
        );
    }

    public function testSynchronize()
    {
        $eventCode = 'test_event';
        $event = $this->createMock(Event::class);
        $event->expects(self::once())
            ->method('getName')
            ->willReturn($eventCode);

        $providerId = 'test_provider';
        $providerEvents = ['test_event_two'];
        $defaultProvider = $this->createMock(EventProviderInterface::class);
        $defaultProvider->expects(self::once())
            ->method('getId')
            ->willReturn('default_id');

        $configuration = $this->createMock(AdobeConsoleConfiguration::class);
        $this->providerConfigurationBuilderMock->expects(self::once())
            ->method('build')
            ->with($providerId)
            ->willReturn($configuration);

        $provider = $this->createMock(EventProvider::class);
        $this->eventProviderFactoryMock->expects(self::once())
            ->method('create')
            ->with(['data' => ['id' => $providerId]])
            ->willReturn($provider);

        $eventMetadata = $this->createMock(EventMetadataInterface::class);
        $this->ioMetadataFactoryMock->expects(self::once())
            ->method('generate')
            ->with(EventSubscriberInterface::EVENT_PREFIX_COMMERCE . $eventCode)
            ->willReturn($eventMetadata);

        $this->eventMetadataClientMock->expects(self::once())
            ->method('createEventMetadata')
            ->with($provider, $eventMetadata, $configuration);

        $this->assertTrue(
            $this->singleEventSynchronizer->synchronize(
                $event,
                $providerId,
                $providerEvents,
                $defaultProvider
            )
        );
    }

    public function testSynchronizeWithDefaultProvider()
    {
        $eventCode = 'test_event';
        $event = $this->createMock(Event::class);
        $event->expects(self::once())
            ->method('getName')
            ->willReturn($eventCode);

        $providerId = 'test_provider';
        $providerEvents = ['test_event_two'];
        $defaultProvider = $this->createMock(EventProviderInterface::class);
        $defaultProvider->expects(self::once())
            ->method('getId')
            ->willReturn($providerId);

        $this->providerConfigurationBuilderMock->expects(self::never())
            ->method('build');

        $eventMetadata = $this->createMock(EventMetadataInterface::class);
        $this->ioMetadataFactoryMock->expects(self::once())
            ->method('generate')
            ->with(EventSubscriberInterface::EVENT_PREFIX_COMMERCE . $eventCode)
            ->willReturn($eventMetadata);

        $this->eventMetadataClientMock->expects(self::once())
            ->method('createEventMetadata')
            ->with($defaultProvider, $eventMetadata, null);

        $this->assertTrue(
            $this->singleEventSynchronizer->synchronize(
                $event,
                $providerId,
                $providerEvents,
                $defaultProvider
            )
        );
    }

    public function testSynchronizeWithAlreadyRegisteredEvent()
    {
        $eventCode = 'test_event';
        $event = $this->createMock(Event::class);
        $event->expects(self::once())
            ->method('getName')
            ->willReturn($eventCode);

        $defaultProvider = $this->createMock(EventProviderInterface::class);
        $defaultProvider->expects(self::never())
            ->method('getId');
        $this->providerConfigurationBuilderMock->expects(self::never())
            ->method('build');
        $this->eventProviderFactoryMock->expects(self::never())
            ->method('create');
        $this->ioMetadataFactoryMock->expects(self::never())
            ->method('generate');
        $this->eventMetadataClientMock->expects(self::never())
            ->method('createEventMetadata');

        $this->assertFalse(
            $this->singleEventSynchronizer->synchronize(
                $event,
                'test_provider',
                [EventSubscriberInterface::EVENT_PREFIX_COMMERCE . $eventCode],
                $defaultProvider
            )
        );
    }

    public function testSynchronizeWithoutProviderWorkspace()
    {
        $eventCode = 'test_event';
        $event = $this->createMock(Event::class);
        $event->expects(self::once())
            ->method('getName')
            ->willReturn($eventCode);

        $providerId = 'test_provider';
        $providerEvents = ['test_event_two'];
        $defaultProvider = $this->createMock(EventProviderInterface::class);
        $defaultProvider->expects(self::once())
            ->method('getId')
            ->willReturn('default_id');

        $this->providerConfigurationBuilderMock->expects(self::once())
            ->method('build')
            ->with($providerId);

        $this->eventProviderFactoryMock->expects(self::never())
            ->method('create');
        $this->ioMetadataFactoryMock->expects(self::never())
            ->method('generate');
        $this->eventMetadataClientMock->expects(self::never())
            ->method('createEventMetadata');

        $this->assertFalse(
            $this->singleEventSynchronizer->synchronize(
                $event,
                $providerId,
                $providerEvents,
                $defaultProvider
            )
        );
    }

    public function testSynchronizeException()
    {
        $exceptionMessage = 'Provider does not exist';
        $this->expectException(SynchronizerException::class);
        $this->expectExceptionMessage($exceptionMessage);

        $eventCode = 'test_event';
        $event = $this->createMock(Event::class);
        $event->expects(self::once())
            ->method('getName')
            ->willReturn($eventCode);

        $providerId = 'test_provider';
        $defaultProvider = $this->createMock(EventProviderInterface::class);
        $defaultProvider->expects(self::once())
            ->method('getId')
            ->willReturn('default_id');
        $exception = new NoSuchEntityException(__($exceptionMessage));
        $this->providerConfigurationBuilderMock->expects(self::once())
            ->method('build')
            ->with($providerId)
            ->willThrowException($exception);

        $this->eventProviderFactoryMock->expects(self::never())
            ->method('create');
        $this->ioMetadataFactoryMock->expects(self::never())
            ->method('generate');
        $this->eventMetadataClientMock->expects(self::never())
            ->method('createEventMetadata');

        $this->singleEventSynchronizer->synchronize(
            $event,
            $providerId,
            [],
            $defaultProvider
        );
    }
}
