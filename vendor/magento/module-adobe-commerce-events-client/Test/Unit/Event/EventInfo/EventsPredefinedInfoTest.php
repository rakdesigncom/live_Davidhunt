<?php
/**
 * ADOBE CONFIDENTIAL
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
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Test\Unit\Event\EventInfo;

use Magento\AdobeCommerceEventsClient\Event\Event;
use Magento\AdobeCommerceEventsClient\Event\EventInfo\EventInfoExtenderInterface;
use Magento\AdobeCommerceEventsClient\Event\EventInfo\EventsPredefinedInfo;
use Magento\AdobeCommerceOutOfProcessExtensibility\Model\Converter\ClassToArrayConverterInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Controller\Account\CreatePost;
use Magento\Quote\Model\Quote;
use Magento\Sales\Model\Order;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Tests for @see EventsPredefinedInfo
 */
class EventsPredefinedInfoTest extends TestCase
{
    /**
     * @var ClassToArrayConverterInterface|MockObject
     */
    private ClassToArrayConverterInterface|MockObject $classToArrayConverterMock;

    /**
     * @var EventInfoExtenderInterface|MockObject
     */
    private EventInfoExtenderInterface|MockObject $eventInfoExtenderMock;

    /**
     * @var Event|MockObject
     */
    private Event|MockObject $eventMock;

    /**
     * @var EventsPredefinedInfo
     */
    private EventsPredefinedInfo $eventsPredefinedInfo;

    protected function setUp(): void
    {
        $this->classToArrayConverterMock = $this->createMock(ClassToArrayConverterInterface::class);
        $this->eventInfoExtenderMock = $this->createMock(EventInfoExtenderInterface::class);
        $this->eventInfoExtenderMock->expects(self::any())
            ->method('extend')
            ->willReturnArgument(1);
        $this->eventMock = $this->createMock(Event::class);

        $this->eventsPredefinedInfo = new EventsPredefinedInfo(
            $this->classToArrayConverterMock,
            $this->eventInfoExtenderMock
        );
    }

    public function testGetReturnsNullForNonExistentEvent(): void
    {
        $this->eventMock->expects(self::once())
            ->method('getName')
            ->willReturn('observer.non_existent_event');

        self::assertNull($this->eventsPredefinedInfo->get($this->eventMock));
    }

    public function testGetReturnsCheckoutSubmitAllAfterEventInfo(): void
    {
        $orderData = ['id' => 1, 'status' => 'pending'];
        $quoteData = ['id' => 2, 'items' => []];

        $this->eventMock->expects(self::once())
            ->method('getName')
            ->willReturn('observer.checkout_submit_all_after');

        $this->classToArrayConverterMock->expects(self::exactly(2))
            ->method('convert')
            ->willReturnMap([
                [Order::class, 2, $orderData],
                [Quote::class, 2, $quoteData],
            ]);

        $result = $this->eventsPredefinedInfo->get($this->eventMock);

        self::assertArrayHasKey('order', $result);
        self::assertArrayHasKey('quote', $result);
        self::assertEquals($orderData, $result['order']);
        self::assertEquals($quoteData, $result['quote']);
    }

    public function testGetReturnsCustomerLoginEventInfo(): void
    {
        $customerData = ['id' => 1, 'email' => 'test@example.com'];

        $this->eventMock->expects(self::once())
            ->method('getName')
            ->willReturn('observer.customer_login');

        $this->classToArrayConverterMock->expects(self::once())
            ->method('convert')
            ->with(CustomerInterface::class, 2)
            ->willReturn($customerData);

        $result = $this->eventsPredefinedInfo->get($this->eventMock);

        self::assertArrayHasKey('customer', $result);
        self::assertEquals($customerData, $result['customer']);
    }

    public function testGetReturnsCustomerRegisterSuccessEventInfo(): void
    {
        $customerData = ['id' => 1, 'email' => 'test@example.com'];
        $accountControllerData = ['controller' => 'account'];

        $this->eventMock->expects(self::once())
            ->method('getName')
            ->willReturn('observer.customer_register_success');

        $this->classToArrayConverterMock->expects(self::exactly(2))
            ->method('convert')
            ->willReturnMap([
                [CustomerInterface::class, 2, $customerData],
                [CreatePost::class, 2, $accountControllerData],
            ]);

        $result = $this->eventsPredefinedInfo->get($this->eventMock);

        self::assertArrayHasKey('customer', $result);
        self::assertArrayHasKey('account_controller', $result);
        self::assertEquals($customerData, $result['customer']);
        self::assertEquals($accountControllerData, $result['account_controller']);
    }
}
