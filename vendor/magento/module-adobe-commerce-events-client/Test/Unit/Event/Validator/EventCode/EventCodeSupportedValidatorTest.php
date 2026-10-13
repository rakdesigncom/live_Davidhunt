<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Test\Unit\Event\Validator\EventCode;

use Magento\AdobeCommerceEventsClient\Event\Event;
use Magento\AdobeCommerceEventsClient\Event\EventSubscriberInterface;
use Magento\AdobeCommerceEventsClient\Event\Validator\EventCode\EventCodeSupportedValidator;
use Magento\AdobeCommerceEventsClient\Event\Validator\ValidatorException;
use Magento\AdobeCommerceOutOfProcessExtensibility\Model\Collector\AggregatedEventListInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Tests for EventCodeSupportedValidator class
 */
class EventCodeSupportedValidatorTest extends TestCase
{
    /**
     * @var EventCodeSupportedValidator
     */
    private EventCodeSupportedValidator $validator;

    /**
     * @var AggregatedEventListInterface|MockObject
     */
    private $aggregatedEventListMock;

    /**
     * @var Event|MockObject
     */
    private $eventMock;

    protected function setUp(): void
    {
        $this->eventMock = $this->createMock(Event::class);
        $this->aggregatedEventListMock = $this->createMock(AggregatedEventListInterface::class);

        $this->validator = new EventCodeSupportedValidator($this->aggregatedEventListMock);
    }

    public function testGetListIsNotCalledForObserverEvents()
    {
        $this->eventMock->expects(self::once())
            ->method('getParent')
            ->willReturn(null);
        $this->eventMock->expects(self::once())
            ->method('getName')
            ->willReturn('observer.some_event_code');
        $this->aggregatedEventListMock->expects(self::never())
            ->method('getList');

        $this->validator->validate($this->eventMock);
    }

    #[DataProvider('parentValueDataProvider')]
    public function testValidateException(?string $parentValue)
    {
        $this->expectException(ValidatorException::class);
        $this->expectExceptionMessage(
            'Event "plugin.some_event_code" is not defined in the list of supported events'
        );
        $this->eventMock->expects(self::once())
            ->method('getParent')
            ->willReturn($parentValue);
        $this->eventMock->expects(self::once())
            ->method('getName')
            ->willReturn('plugin.some_event_code');

        $this->aggregatedEventListMock->expects(self::once())
            ->method('getList')
            ->willReturn([]);

        $this->validator->validate($this->eventMock);
    }

    public static function parentValueDataProvider(): array
    {
        return [[null], ['']];
    }

    public function testValidateParentException()
    {
        $this->expectException(ValidatorException::class);
        $this->expectExceptionMessage(
            'Event "plugin.some_from_event_code" is not defined in the list of supported events'
        );
        $this->eventMock->expects(self::once())
            ->method('getParent')
            ->willReturn('plugin.some_from_event_code');
        $this->eventMock->expects(self::never())
            ->method('getName');

        $this->aggregatedEventListMock->expects(self::once())
            ->method('getList')
            ->willReturn([]);

        $this->validator->validate($this->eventMock);
    }

    public function testValidate()
    {
        $this->eventMock->expects(self::once())
            ->method('getParent')
            ->willReturn(null);
        $this->eventMock->expects(self::once())
            ->method('getName')
            ->willReturn('plugin.some_event_code');
        $this->aggregatedEventListMock->expects(self::once())
            ->method('getList')
            ->willReturn([
                'plugin.some_event_code' => []
            ]);

        $this->validator->validate($this->eventMock);
    }

    public function testValidateWithPrefix()
    {
        $this->eventMock->expects(self::once())
            ->method('getParent')
            ->willReturn(null);
        $this->eventMock->expects(self::once())
            ->method('getName')
            ->willReturn(EventSubscriberInterface::EVENT_PREFIX_COMMERCE. 'plugin.some_event_code');
        $this->aggregatedEventListMock->expects(self::once())
            ->method('getList')
            ->willReturn([
                'plugin.some_event_code' => []
            ]);

        $this->validator->validate($this->eventMock);
    }
}
