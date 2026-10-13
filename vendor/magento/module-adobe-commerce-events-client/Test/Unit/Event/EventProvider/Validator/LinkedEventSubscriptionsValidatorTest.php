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

namespace Magento\AdobeCommerceEventsClient\Test\Unit\Event\EventProvider\Validator;

use Magento\AdobeCommerceEventsClient\Api\Data\EventProviderInterface;
use Magento\AdobeCommerceEventsClient\Event\Event;
use Magento\AdobeCommerceEventsClient\Event\EventInitializationException;
use Magento\AdobeCommerceEventsClient\Event\EventList;
use Magento\AdobeCommerceEventsClient\Event\EventProvider\Validator\LinkedEventSubscriptionsValidator;
use Magento\Framework\Exception\ValidatorException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test for @see LinkedEventSubscriptionsValidator
 */
class LinkedEventSubscriptionsValidatorTest extends TestCase
{
    /**
     * @var EventList|MockObject
     */
    private $eventListMock;

    /**
     * @var EventProviderInterface|MockObject
     */
    private $eventProviderMock;

    /**
     * @var LinkedEventSubscriptionsValidator
     */
    private $validator;

    protected function setUp(): void
    {
        $this->eventListMock = $this->createMock(EventList::class);
        $this->eventProviderMock = $this->createMock(EventProviderInterface::class);
        $this->eventProviderMock->expects(self::any())
            ->method('getProviderId')
            ->willReturn('test_provider_id');
        $this->validator = new LinkedEventSubscriptionsValidator($this->eventListMock);
    }

    public function testValidateWithNoLinkedEvents(): void
    {
        $eventMock = $this->createMock(Event::class);
        $eventMock->expects(self::once())
            ->method('getProviderId')
            ->willReturn('test_provider_new');
        $eventMock->expects(self::never())
            ->method('getName');
        $eventMock->expects(self::never())
            ->method('isEnabled');
        $this->eventListMock->expects(self::once())
            ->method('getAll')
            ->willReturn([$eventMock]);

        $this->validator->validate($this->eventProviderMock);

        self::assertTrue(true);
    }

    public function testValidateWithLinkedNotEnabledEvents(): void
    {
        $eventMock = $this->createMock(Event::class);
        $eventMock->expects(self::once())
            ->method('getProviderId')
            ->willReturn('test_provider_id');
        $eventMock->expects(self::never())
            ->method('getName');
        $eventMock->expects(self::once())
            ->method('isEnabled')
            ->willReturn(false);
        $this->eventListMock->expects(self::once())
            ->method('getAll')
            ->willReturn([$eventMock]);

        $this->validator->validate($this->eventProviderMock);

        self::assertTrue(true);
    }

    public function testValidateWithLinkedEvents(): void
    {
        self::expectException(ValidatorException::class);
        self::expectExceptionMessage(
            'The event provider has linked event subscriptions: [Test Event].'
        );

        $eventMockOne = $this->createMock(Event::class);
        $eventMockOne->expects(self::once())
            ->method('getProviderId')
            ->willReturn('test_provider_id');
        $eventMockOne->expects(self::once())
            ->method('getName')
            ->willReturn('Test Event');
        $eventMockOne->expects(self::once())
            ->method('isEnabled')
            ->willReturn(true);
        $eventMockTwo = $this->createMock(Event::class);
        $eventMockTwo->expects(self::once())
            ->method('getProviderId')
            ->willReturn('test_provider_id');
        $eventMockTwo->expects(self::never())
            ->method('getName');
        $eventMockTwo->expects(self::once())
            ->method('isEnabled')
            ->willReturn(false);
        $this->eventListMock->expects(self::once())
            ->method('getAll')
            ->willReturn([$eventMockOne, $eventMockTwo]);

        $this->validator->validate($this->eventProviderMock);
    }

    public function testValidateWithEventInitializationException(): void
    {
        $this->expectException(ValidatorException::class);
        $this->expectExceptionMessage('An error occurred while fetching event subscriptions: Initialization error');

        $this->eventListMock->expects(self::once())
            ->method('getAll')
            ->willThrowException(new EventInitializationException(__('Initialization error')));

        $this->validator->validate($this->eventProviderMock);
    }
}
