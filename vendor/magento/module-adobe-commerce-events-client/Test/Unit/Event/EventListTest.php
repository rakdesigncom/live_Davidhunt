<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Test\Unit\Event;

use Magento\AdobeCommerceEventsClient\Config\Reader;
use Magento\AdobeCommerceEventsClient\Config\SubscriptionLoader;
use Magento\AdobeCommerceEventsClient\Event\EventFactory;
use Magento\AdobeCommerceEventsClient\Event\EventList;
use Magento\AdobeCommerceEventsClient\Event\EventSubscriberInterface;
use Magento\AdobeCommerceEventsClient\Event\Event;
use Magento\AdobeCommerceEventsClient\Event\InvalidConfigurationException;
use Magento\Framework\App\DeploymentConfig;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Tests for EventList class
 */
class EventListTest extends TestCase
{
    /**
     * @var EventList
     */
    private EventList $eventList;

    /**
     * @var Reader|MockObject
     */
    private $readerMock;

    /**
     * @var DeploymentConfig|MockObject
     */
    private $deploymentConfigMock;

    /**
     * @var EventFactory|MockObject
     */
    private $eventFactoryMock;

    /**
     * @var SubscriptionLoader|MockObject
     */
    private SubscriptionLoader|MockObject $subscriptionLoaderMock;

    protected function setUp(): void
    {
        $this->readerMock = $this->createMock(Reader::class);
        $this->deploymentConfigMock = $this->createMock(DeploymentConfig::class);
        $this->eventFactoryMock = $this->createMock(EventFactory::class);
        $this->subscriptionLoaderMock = $this->createMock(SubscriptionLoader::class);

        $this->eventList = new EventList(
            $this->readerMock,
            $this->deploymentConfigMock,
            $this->eventFactoryMock,
            $this->subscriptionLoaderMock
        );
    }

    public function testGetAll()
    {
        $this->mockLoadEvents();

        $events = $this->eventList->getAll();
        self::assertEquals(2, count($events));
        self::assertArrayHasKey('event_code_one', $events);
        self::assertArrayHasKey('event_code_two', $events);
    }

    public function testGet()
    {
        $this->mockLoadEvents();

        self::assertInstanceOf(Event::class, $this->eventList->get('event_code_one'));
    }

    public function testGetNotExists()
    {
        $this->mockLoadEvents();

        self::assertNull($this->eventList->get('event_not_exists'));
    }

    public function testEventIsEnabled()
    {
        $this->mockLoadEvents();

        self::assertTrue($this->eventList->isEventEnabled('event_code_one'));
        self::assertFalse($this->eventList->isEventEnabled('event_code_two'));
    }

    public function testValidateEventData()
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage(
            'Wrong configuration in "io_events" section of app/etc/env.php or ' .
            'app/etc/config.php files for the event "event1_no_eventData". ' .
            'The configuration must be in array format with at least one field configured.'
        );

        $this->eventList->validateEventData('event1_no_eventData', []);
    }

    private function mockLoadEvents()
    {
        $this->readerMock->expects(self::never())
            ->method('read');
        $this->deploymentConfigMock->expects(self::never())
            ->method('get');
        $this->subscriptionLoaderMock->expects(self::once())
            ->method('getEventSubscriptions')
            ->willReturn([
                'event_code_one' => ['fields' => ['id']],
                'event_code_two' => ['fields' => ['name']],
            ]);
        $this->eventFactoryMock->expects(self::exactly(2))
            ->method('create')
            ->willReturnCallback(function (array $data) {
                static $count = 0;
                switch ($count++) {
                    case 0:
                        self::assertEquals(['fields' => ['id']], $data);
                        return new Event('event_code_one', fields: ['id']);
                    case 1:
                        self::assertEquals(['fields' => ['name']], $data);
                        return new Event('event_code_two', enabled: false, fields: ['name']);
                }
            });
    }
}
