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

namespace Magento\AdobeCommerceEventsClient\Test\Unit\Event\Validator\EventCode;

use Magento\AdobeCommerceEventsClient\Event\Event;
use Magento\AdobeCommerceEventsClient\Event\Validator\EventCode\PrefixValidator;
use Magento\AdobeCommerceEventsClient\Event\Validator\ValidatorException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Tests for @see PrefixValidator class
 */
class PrefixValidatorTest extends TestCase
{
    /**
     * @var PrefixValidator
     */
    private PrefixValidator $validator;

    /**
     * @var Event|MockObject
     */
    private $eventMock;

    protected function setUp(): void
    {
        $this->eventMock = $this->createMock(Event::class);

        $this->validator = new PrefixValidator();
    }

    public function testValidPrefix()
    {
        $this->eventMock->expects(self::once())
            ->method('getName')
            ->willReturn('observer.some_event_code');

        $this->validator->validate($this->eventMock);
    }

    public function testInvalidPrefix()
    {
        $this->expectException(ValidatorException::class);
        $this->expectExceptionMessage(
            'Invalid event type "invalid" in the provided event code "invalid.some_event_code"'
        );

        $this->eventMock->expects(self::once())
            ->method('getName')
            ->willReturn('invalid.some_event_code');

        $this->validator->validate($this->eventMock);
    }

    public function testInvalidEventCodeStructure()
    {
        $this->expectException(ValidatorException::class);
        $this->expectExceptionMessage(
            'Event code "some_event_code" must consist of a type label and an event code separated by a dot'
        );

        $this->eventMock->expects(self::once())
            ->method('getName')
            ->willReturn('some_event_code');

        $this->validator->validate($this->eventMock);
    }

    public function testValidPrefixWithParent()
    {
        $this->eventMock->expects(self::never())
            ->method('getName');
        $this->eventMock->expects(self::once())
            ->method('getParent')
            ->willReturn('observer.some_event_code');

        $this->validator->validate($this->eventMock);
    }

    public function testInvalidParentPrefix()
    {
        $this->expectException(ValidatorException::class);
        $this->expectExceptionMessage(
            'Invalid event type "invalid" in the provided event code "invalid.some_event_code"'
        );

        $this->eventMock->expects(self::never())
            ->method('getName');
        $this->eventMock->expects(self::once())
            ->method('getParent')
            ->willReturn('invalid.some_event_code');

        $this->validator->validate($this->eventMock);
    }

    public function testInvalidParentEventCodeStructure()
    {
        $this->expectException(ValidatorException::class);
        $this->expectExceptionMessage(
            'Event code "some_event_code" must consist of a type label and an event code separated by a dot'
        );

        $this->eventMock->expects(self::never())
            ->method('getName');
        $this->eventMock->expects(self::once())
            ->method('getParent')
            ->willReturn('some_event_code');

        $this->validator->validate($this->eventMock);
    }

    public function testValidPrefixWithEmptyParent()
    {
        $this->eventMock->expects(self::once())
            ->method('getParent')
            ->willReturn('');
        $this->eventMock->expects(self::once())
            ->method('getName')
            ->willReturn('observer.some_event_code');

        $this->validator->validate($this->eventMock);
    }
}
