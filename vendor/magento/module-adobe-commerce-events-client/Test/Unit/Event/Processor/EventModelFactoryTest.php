<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Test\Unit\Event\Processor;

use Magento\AdobeCommerceEventsClient\Event\DataFilterInterface;
use Magento\AdobeCommerceEventsClient\Event\Event;
use Magento\AdobeCommerceEventsClient\Event\EventMetadataCollector;
use Magento\AdobeCommerceEventsClient\Event\Hipaa\HipaaCustomerInterface;
use Magento\AdobeCommerceEventsClient\Event\Metadata\EventMetadataException;
use Magento\AdobeCommerceEventsClient\Event\Processor\EventModelFactory;
use Magento\AdobeCommerceEventsClient\Model\EventFactory;
use Magento\AdobeCommerceEventsClient\Model\Event as EventModel;
use Magento\Framework\DataObject\IdentityGeneratorInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Tests for @see EventModelFactory
 */
class EventModelFactoryTest extends TestCase
{
    /**
     * @var EventFactory|MockObject
     */
    private $eventFactoryMock;

    /**
     * @var DataFilterInterface|MockObject
     */
    private $dataFilterMock;

    /**
     * @var EventMetadataCollector|MockObject
     */
    private $metadataCollectorMock;

    /**
     * @var IdentityGeneratorInterface|MockObject
     */
    private $identityGeneratorMock;

    /**
     * @var HipaaCustomerInterface|MockObject
     */
    private $hipaaCustomerMock;

    /**
     * @var EventModelFactory
     */
    private EventModelFactory $eventModelFactory;

    protected function setUp(): void
    {
        $this->eventFactoryMock = $this->createMock(EventFactory::class);
        $this->dataFilterMock = $this->createMock(DataFilterInterface::class);
        $this->metadataCollectorMock = $this->createMock(EventMetadataCollector::class);
        $this->identityGeneratorMock = $this->createMock(IdentityGeneratorInterface::class);
        $this->hipaaCustomerMock = $this->createMock(HipaaCustomerInterface::class);

        $this->eventModelFactory = new EventModelFactory(
            $this->eventFactoryMock,
            $this->dataFilterMock,
            $this->metadataCollectorMock,
            $this->identityGeneratorMock,
            $this->hipaaCustomerMock
        );
    }

    public function testCreate(): void
    {
        $eventMock = $this->createEventMock('observer.test_event', false, true);
        $eventData = [
            'id' => 3,
            'name' => 'test'
        ];
        $eventModel = $this->createMock(EventModel::class);
        $eventModel->expects(self::once())
            ->method('setEventCode')
            ->with('com.adobe.commerce.observer.test_event');
        $eventModel->expects(self::once())
            ->method('setEventData')
            ->with(['id' => 3]);
        $eventModel->expects(self::once())
            ->method('setMetadata')
            ->with(['metadata' => 'value']);
        $eventModel->expects(self::once())
            ->method('setPriority')
            ->with(0);
        $eventModel->expects(self::once())
            ->method('setHipaaAuditRequired')
            ->with(1);
        $eventModel->expects(self::once())
            ->method('setTrackId')
            ->with('track-id');
        $this->eventFactoryMock->expects(self::once())
            ->method('create')
            ->willReturn($eventModel);
        $this->hipaaCustomerMock->expects(self::never())
            ->method('isHipaaCustomer');
        $this->identityGeneratorMock->expects(self::once())
            ->method('generateId')
            ->willReturn('track-id');
        $this->dataFilterMock->expects(self::once())
            ->method('filter')
            ->with('com.adobe.commerce.observer.test_event', $eventData)
            ->willReturn(['id' => 3]);
        $this->metadataCollectorMock->expects(self::once())
            ->method('getMetadata')
            ->willReturn(['metadata' => 'value']);

        $this->eventModelFactory->create($eventMock, $eventData);
    }

    public function testCreateWithHipaaCustomer(): void
    {
        $eventMock = $this->createEventMock('observer.test_event_hipaa', false, false);
        $eventData = [
            'id' => 3,
            'name' => 'test'
        ];
        $eventModel = $this->createMock(EventModel::class);
        $eventModel->expects(self::once())
            ->method('setEventCode')
            ->with('com.adobe.commerce.observer.test_event_hipaa');
        $eventModel->expects(self::once())
            ->method('setEventData')
            ->with(['id' => 13]);
        $eventModel->expects(self::once())
            ->method('setMetadata')
            ->with(['metadata' => 'hipaa_value']);
        $eventModel->expects(self::once())
            ->method('setPriority')
            ->with(0);
        $eventModel->expects(self::once())
            ->method('setHipaaAuditRequired')
            ->with(1);
        $eventModel->expects(self::once())
            ->method('setTrackId')
            ->with('track-id');
        $this->eventFactoryMock->expects(self::once())
            ->method('create')
            ->willReturn($eventModel);
        $this->hipaaCustomerMock->expects(self::once())
            ->method('isHipaaCustomer')
            ->willReturn(true);
        $this->identityGeneratorMock->expects(self::once())
            ->method('generateId')
            ->willReturn('track-id');
        $this->dataFilterMock->expects(self::once())
            ->method('filter')
            ->with('com.adobe.commerce.observer.test_event_hipaa', $eventData)
            ->willReturn(['id' => 13]);
        $this->metadataCollectorMock->expects(self::once())
            ->method('getMetadata')
            ->willReturn(['metadata' => 'hipaa_value']);

        $this->eventModelFactory->create($eventMock, $eventData);
    }

    public function testCreateWithMetadataException(): void
    {
        $this->expectException(EventMetadataException::class);
        $eventModel = $this->createMock(EventModel::class);
        $eventModel->expects(self::once())
            ->method('setTrackId')
            ->with('track-id');
        $this->eventFactoryMock->expects(self::once())
            ->method('create')
            ->willReturn($eventModel);
        $this->identityGeneratorMock->expects(self::once())
            ->method('generateId')
            ->willReturn('track-id');
        $this->metadataCollectorMock->expects(self::once())
            ->method('getMetadata')
            ->willThrowException(new EventMetadataException(__('Some error')));

        $eventMock = $this->createEventMock('observer.test_event', true, false);
        $this->eventModelFactory->create($eventMock, []);
    }

    /**
     * @param string $eventName
     * @param bool $isPriority
     * @param bool $isHipaaAuditRequired
     * @return MockObject|Event
     */
    private function createEventMock(string $eventName, bool $isPriority, bool $isHipaaAuditRequired): MockObject|Event
    {
        $eventMock = $this->createMock(Event::class);
        $eventMock->expects(self::once())
            ->method('getName')
            ->willReturn($eventName);
        $eventMock->expects(self::any())
            ->method('isPriority')
            ->willReturn($isPriority);
        $eventMock->expects(self::any())
            ->method('isHipaaAuditRequired')
            ->willReturn($isHipaaAuditRequired);

        return $eventMock;
    }
}
