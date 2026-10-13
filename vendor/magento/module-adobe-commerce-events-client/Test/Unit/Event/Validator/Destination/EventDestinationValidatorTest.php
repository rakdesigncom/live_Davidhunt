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

namespace Magento\AdobeCommerceEventsClient\Test\Unit\Event\Validator\Destination;

use Magento\AdobeCommerceEventsClient\Event\Event;
use Magento\AdobeCommerceEventsClient\Event\EventStorageWriter\EventDestinationResolver;
use Magento\AdobeCommerceEventsClient\Event\Validator\Destination\EventDestinationValidator;
use Magento\AdobeCommerceEventsClient\Event\Validator\ValidatorException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Tests for @see EventDestinationValidator
 */
class EventDestinationValidatorTest extends TestCase
{
    /**
     * @var EventDestinationResolver|MockObject
     */
    private EventDestinationResolver|MockObject $eventDestinationResolverMock;

    /**
     * @var Event|MockObject
     */
    private Event|MockObject $eventMock;

    /**
     * @var EventDestinationValidator
     */
    private EventDestinationValidator $validator;

    protected function setUp(): void
    {
        $this->eventDestinationResolverMock = $this->createMock(EventDestinationResolver::class);
        $this->eventMock = $this->createMock(Event::class);

        $this->validator = new EventDestinationValidator($this->eventDestinationResolverMock);
    }

    public function testValidationWithForceOption()
    {
        $this->eventMock->expects(self::never())
            ->method('getDestination');
        $this->eventDestinationResolverMock->expects(self::never())
            ->method('getDestinations');

        $this->validator->validate($this->eventMock, true);
    }

    public function testDestinationDefault()
    {
        $this->eventMock->expects(self::once())
            ->method('getDestination')
            ->willReturn(Event::DESTINATION_DEFAULT);
        $this->eventDestinationResolverMock->expects(self::never())
            ->method('getDestinations');

        $this->validator->validate($this->eventMock);
    }

    public function testDestinationRegistered()
    {
        $this->eventMock->expects(self::once())
            ->method('getDestination')
            ->willReturn('custom-destination-one');
        $this->eventDestinationResolverMock->expects(self::once())
            ->method('getDestinations')
            ->willReturn([
                'custom-destination-one',
                'custom-destination-two',
            ]);

        $this->validator->validate($this->eventMock);
    }

    public function testDestinationNotRegistered()
    {
        $this->expectException(ValidatorException::class);
        $this->expectExceptionMessage('The destination "custom-destination-one" is not registered');

        $this->eventMock->expects(self::once())
            ->method('getDestination')
            ->willReturn('custom-destination-one');
        $this->eventDestinationResolverMock->expects(self::once())
            ->method('getDestinations')
            ->willReturn([
                'custom-destination-two',
            ]);

        $this->validator->validate($this->eventMock);
    }
}
