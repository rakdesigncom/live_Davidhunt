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

namespace Magento\AdobeCommerceEventsClient\Test\Unit\Event\Validator;

use Magento\AdobeCommerceEventsClient\Event\Event;
use Magento\AdobeCommerceEventsClient\Event\EventField;
use Magento\AdobeCommerceEventsClient\Event\Validator\FieldsConfiguredValidator;
use Magento\AdobeCommerceEventsClient\Event\Validator\ValidatorException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Tests for @see FieldsConfiguredValidator
 */
class FieldsConfiguredValidatorTest extends TestCase
{
    /**
     * @var Event|MockObject
     */
    private Event|MockObject $eventMock;

    protected function setUp(): void
    {
        $this->eventMock = $this->createMock(Event::class);
    }

    public function testFieldsNotConfigured()
    {
        $this->expectException(ValidatorException::class);
        $this->expectExceptionMessage('At least one field must be configured for the event.');

        $this->eventMock->expects(self::once())
            ->method('getEventFields')
            ->willReturn([]);
        (new FieldsConfiguredValidator())->validate($this->eventMock);
    }

    public function testFieldsConfiguredWithoutNameAttribute()
    {
        $this->expectException(ValidatorException::class);
        $this->expectExceptionMessage('Each field must have a name attribute.');

        $this->eventMock->expects(self::once())
            ->method('getEventFields')
            ->willReturn([
                new EventField(['test'])
            ]);
        (new FieldsConfiguredValidator())->validate($this->eventMock);
    }

    public function testFieldsConfigured()
    {
        $this->eventMock->expects(self::once())
            ->method('getEventFields')
            ->willReturn([
                new EventField(['name' => 'test'])
            ]);
        (new FieldsConfiguredValidator())->validate($this->eventMock);
    }
}
