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

namespace Magento\AdobeCommerceEventsClient\Test\Unit\Event\AdobeIoEventMetadata;

use Magento\AdobeCommerceEventsClient\Event\AdobeIoEventMetadata\ProviderNotConfiguredSubscriberException;
use Magento\AdobeCommerceEventsClient\Event\AdobeIoEventMetadata\Subscriber;
use Magento\AdobeCommerceEventsClient\Event\AdobeIoEventMetadata\SubscriberException;
use Magento\AdobeCommerceEventsClient\Event\AdobeIoEventMetadataFactory;
use Magento\AdobeCommerceEventsClient\Event\Event;
use Magento\AdobeCommerceEventsClient\Event\ProviderConfigurationBuilder;
use Magento\AdobeIoEventsClient\Api\EventMetadataInterface;
use Magento\AdobeIoEventsClient\Api\EventProviderInterface;
use Magento\AdobeIoEventsClient\Model\AdobeIOConfigurationProvider;
use Magento\AdobeIoEventsClient\Model\Data\AdobeConsoleConfiguration\AdobeConsoleConfiguration;
use Magento\AdobeIoEventsClient\Model\Data\EventProviderFactory;
use Magento\AdobeIoEventsClient\Model\EventMetadataClient;
use Magento\Framework\Exception\AuthorizationException;
use Magento\Framework\Exception\NoSuchEntityException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Tests for @see Subscriber
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SubscriberTest extends TestCase
{
    /**
     * @var AdobeIOConfigurationProvider|MockObject
     */
    private AdobeIOConfigurationProvider|MockObject $configurationProviderMock;

    /**
     * @var AdobeIoEventMetadataFactory|MockObject
     */
    private AdobeIoEventMetadataFactory|MockObject $eventMetadataFactoryMock;

    /**
     * @var EventMetadataClient|MockObject
     */
    private EventMetadataClient|MockObject $metadataClientMock;

    /**
     * @var EventProviderInterface|MockObject
     */
    private EventProviderInterface|MockObject $eventProviderMock;

    /**
     * @var EventProviderFactory|MockObject
     */
    private EventProviderFactory|MockObject $eventProviderFactoryMock;

    /**
     * @var ProviderConfigurationBuilder|MockObject
     */
    private ProviderConfigurationBuilder|MockObject $providerConfigurationBuilderMock;

    /**
     * @var Subscriber
     */
    private Subscriber $subscriber;

    protected function setUp(): void
    {
        $this->configurationProviderMock = $this->createMock(AdobeIOConfigurationProvider::class);
        $this->eventMetadataFactoryMock = $this->createMock(AdobeIoEventMetadataFactory::class);
        $this->metadataClientMock = $this->createMock(EventMetadataClient::class);
        $this->eventProviderFactoryMock = $this->createMock(EventProviderFactory::class);
        $this->providerConfigurationBuilderMock = $this->createMock(ProviderConfigurationBuilder::class);
        $this->eventProviderMock = $this->createMock(EventProviderInterface::class);

        $this->subscriber = new Subscriber(
            $this->configurationProviderMock,
            $this->eventMetadataFactoryMock,
            $this->metadataClientMock,
            $this->eventProviderFactoryMock,
            $this->providerConfigurationBuilderMock
        );
    }

    public function testCreate(): void
    {
        $eventMock = $this->createMock(Event::class);
        $eventMetadataMock = $this->createMock(EventMetadataInterface::class);

        $eventMock->expects(self::once())
            ->method('getName')
            ->willReturn('observer.test_event');
        $this->configurationProviderMock->expects(self::once())
            ->method('getProvider')
            ->willReturn($this->eventProviderMock);
        $this->eventMetadataFactoryMock->expects(self::once())
            ->method('generate')
            ->with('com.adobe.commerce.observer.test_event')
            ->willReturn($eventMetadataMock);
        $this->metadataClientMock->expects(self::once())
            ->method('createEventMetadata')
            ->with($this->eventProviderMock, $eventMetadataMock)
            ->willReturn(true);

        self::assertTrue($this->subscriber->create($eventMock));
    }

    public function testCreateThrowsException(): void
    {
        $this->expectException(SubscriberException::class);

        $eventMock = $this->createMock(Event::class);
        $this->configurationProviderMock->expects(self::once())
            ->method('getProvider')
            ->willReturn($this->eventProviderMock);
        $this->metadataClientMock->expects(self::once())
            ->method('createEventMetadata')
            ->willThrowException(new AuthorizationException(__('some error')));

        $this->subscriber->create($eventMock);
    }

    public function testCreateNoProviderConfigured(): void
    {
        $eventMock = $this->createMock(Event::class);
        $this->expectException(ProviderNotConfiguredSubscriberException::class);
        $this->subscriber->create($eventMock);
    }

    public function testCreateNonDefaultProvider(): void
    {
        $providerId = 'testId';
        $eventMock = $this->createMock(Event::class);
        $eventMock->expects(self::once())
            ->method('getProviderId')
            ->willReturn($providerId);
        $eventMock->expects(self::once())
            ->method('getName')
            ->willReturn('observer.test_event');

        $consoleConfigMock = $this->createMock(AdobeConsoleConfiguration::class);
        $this->providerConfigurationBuilderMock->expects(self::once())
            ->method('build')
            ->willReturn($consoleConfigMock);

        $this->eventProviderFactoryMock->expects(self::once())
            ->method('create')
            ->with(['data' => ['id' => $providerId]])
            ->willReturn($this->eventProviderMock);

        $this->configurationProviderMock->expects(self::never())
            ->method('getProvider');

        $eventMetadataMock = $this->createMock(EventMetadataInterface::class);
        $this->eventMetadataFactoryMock->expects(self::once())
            ->method('generate')
            ->with('com.adobe.commerce.observer.test_event')
            ->willReturn($eventMetadataMock);
        $this->metadataClientMock->expects(self::once())
            ->method('createEventMetadata')
            ->with($this->eventProviderMock, $eventMetadataMock, $consoleConfigMock)
            ->willReturn(true);

        self::assertTrue($this->subscriber->create($eventMock));
    }

    public function testCreateNonDefaultProviderWithoutWorkspace(): void
    {
        $providerId = 'testId';
        $eventMock = $this->createMock(Event::class);
        $eventMock->expects(self::once())
            ->method('getProviderId')
            ->willReturn($providerId);

        $this->providerConfigurationBuilderMock->expects(self::once())
            ->method('build');

        $this->eventProviderFactoryMock->expects(self::never())
            ->method('create');
        $this->configurationProviderMock->expects(self::never())
            ->method('getProvider');
        $this->metadataClientMock->expects(self::never())
            ->method('createEventMetadata');

        self::assertFalse($this->subscriber->create($eventMock));
    }

    public function testCreateNonDefaultProviderException(): void
    {
        $this->expectException(SubscriberException::class);

        $providerId = 'testId';
        $eventMock = $this->createMock(Event::class);
        $eventMock->expects(self::once())
            ->method('getProviderId')
            ->willReturn($providerId);

        $this->providerConfigurationBuilderMock->expects(self::once())
            ->method('build')
            ->with($providerId)
            ->willThrowException(new NoSuchEntityException(__('error')));

        $this->eventProviderFactoryMock->expects(self::never())
            ->method('create');
        $this->configurationProviderMock->expects(self::never())
            ->method('getProvider');
        $this->metadataClientMock->expects(self::never())
            ->method('createEventMetadata');

        $this->subscriber->create($eventMock);
    }

    public function testDelete(): void
    {
        $eventMock = $this->createMock(Event::class);
        $eventMetadataMock = $this->createMock(EventMetadataInterface::class);

        $eventMock->expects(self::once())
            ->method('getName')
            ->willReturn('observer.test_event');
        $this->configurationProviderMock->expects(self::once())
            ->method('getProvider')
            ->willReturn($this->eventProviderMock);
        $this->eventMetadataFactoryMock->expects(self::once())
            ->method('generate')
            ->with('com.adobe.commerce.observer.test_event')
            ->willReturn($eventMetadataMock);
        $this->metadataClientMock->expects($this->once())
            ->method('deleteEventMetadata')
            ->with($this->eventProviderMock, $eventMetadataMock)
            ->willReturn(true);

        $this->assertTrue($this->subscriber->delete($eventMock));
    }

    public function testDeleteEventWithNonDefaultProvider(): void
    {
        $providerId = 'testId';
        $eventMock = $this->createMock(Event::class);
        $eventMock->expects(self::once())
            ->method('getProviderId')
            ->willReturn($providerId);
        $eventMock->expects(self::once())
            ->method('getName')
            ->willReturn('observer.test_event');

        $consoleConfigMock = $this->createMock(AdobeConsoleConfiguration::class);
        $this->providerConfigurationBuilderMock->expects(self::once())
            ->method('build')
            ->with($providerId)
            ->willReturn($consoleConfigMock);
        $this->eventProviderFactoryMock->expects(self::once())
            ->method('create')
            ->with(['data' => ['id' => $providerId]])
            ->willReturn($this->eventProviderMock);
        $this->configurationProviderMock->expects(self::never())
            ->method('getProvider');

        $eventMetadataMock = $this->createMock(EventMetadataInterface::class);
        $this->eventMetadataFactoryMock->expects(self::once())
            ->method('generate')
            ->with('com.adobe.commerce.observer.test_event')
            ->willReturn($eventMetadataMock);
        $this->metadataClientMock->expects(self::once())
            ->method('deleteEventMetadata')
            ->with($this->eventProviderMock, $eventMetadataMock, $consoleConfigMock)
            ->willReturn(true);

        self::assertTrue($this->subscriber->delete($eventMock));
    }

    public function testDeleteEventWithNonDefaultProviderWithoutWorkspace(): void
    {
        $providerId = 'testId';
        $eventMock = $this->createMock(Event::class);
        $eventMock->expects(self::once())
            ->method('getProviderId')
            ->willReturn($providerId);
        $this->providerConfigurationBuilderMock->expects(self::once())
            ->method('build')
            ->with($providerId)
            ->willReturn(null);

        $this->eventProviderFactoryMock->expects(self::never())
            ->method('create');
        $this->configurationProviderMock->expects(self::never())
            ->method('getProvider');
        $this->metadataClientMock->expects(self::never())
            ->method('deleteEventMetadata');

        self::assertFalse($this->subscriber->delete($eventMock));
    }

    public function testDeleteThrowsException(): void
    {
        $eventMock = $this->createMock(Event::class);
        $this->configurationProviderMock->expects(self::once())
            ->method('getProvider')
            ->willReturn($this->eventProviderMock);
        $this->metadataClientMock->expects(self::once())
            ->method('deleteEventMetadata')
            ->willThrowException(new AuthorizationException(__('some error')));

        $this->expectException(SubscriberException::class);

        $this->subscriber->delete($eventMock);
    }

    public function testDeleteNoProviderConfigured(): void
    {
        $eventMock = $this->createMock(Event::class);
        $this->expectException(ProviderNotConfiguredSubscriberException::class);
        $this->subscriber->delete($eventMock);
    }
}
