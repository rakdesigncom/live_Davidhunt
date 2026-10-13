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

namespace Magento\AdobeCommerceEventsClient\Test\Unit\Config;

use Magento\AdobeCommerceEventsClient\Config\SubscriptionLoader;
use Magento\AdobeCommerceEventsClient\Config\SubscriptionSourceInterface;
use Magento\AdobeCommerceEventsClient\Config\SubscriptionSourcePool;
use Magento\AdobeCommerceEventsClient\Event\InvalidConfigurationException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test for @see SubscriptionLoader class
 */
class SubscriptionLoaderTest extends TestCase
{
    /**
     * @var SubscriptionSourcePool|MockObject
     */
    private SubscriptionSourcePool|MockObject $subscriptionSourcePoolMock;

    /**
     * @var SubscriptionLoader $subscriptionLoader
     */
    private SubscriptionLoader $subscriptionLoader;

    protected function setUp(): void
    {
        $this->subscriptionSourcePoolMock = $this->createMock(SubscriptionSourcePool::class);
        $this->subscriptionLoader = new SubscriptionLoader($this->subscriptionSourcePoolMock);
    }

    public function testGetEventSubscriptionsEmpty(): void
    {
        $this->subscriptionSourcePoolMock->expects(self::once())
            ->method('getSources')
            ->willReturn([]);

        self::assertSame([], $this->subscriptionLoader->getEventSubscriptions());
    }

    public function testGetEventSubscriptionsInvalidConfiguration(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $subscriptionSourceMock = $this->createMock(SubscriptionSourceInterface::class);
        $subscriptionSourceMock->expects(self::once())
            ->method('getEventSubscriptions')
            ->willThrowException(new InvalidConfigurationException(__('Invalid configuration')));
        $this->subscriptionSourcePoolMock->expects(self::once())
            ->method('getSources')
            ->willReturn([$subscriptionSourceMock]);

        $this->subscriptionLoader->getEventSubscriptions();
    }

    public function testConfigurationMerge()
    {
        $subscriptionSourceMockOne = $this->createMock(SubscriptionSourceInterface::class);
        $subscriptionSourceMockOne->expects(self::once())
            ->method('getEventSubscriptions')
            ->willReturn([
                'event1' => [
                    'fields' => ['id', 'name']
                ],
                'event2' => [
                    'fields' => ['order_id']
                ]
            ]);

        $subscriptionSourceMockTwo = $this->createMock(SubscriptionSourceInterface::class);
        $subscriptionSourceMockTwo->expects(self::once())
            ->method('isOptional')
            ->willReturn(true);
        $subscriptionSourceMockTwo->expects(self::once())
            ->method('getEventSubscriptions')
            ->willReturn([
                'event1' => [
                    'fields' => ['id', 'name'],
                    'enabled' => false
                ],
                'event3' => [
                    'fields' => ['order_id']
                ]
            ]);

        $subscriptionSourceMockThree = $this->createMock(SubscriptionSourceInterface::class);
        $subscriptionSourceMockThree->expects(self::once())
            ->method('isOptional')
            ->willReturn(false);
        $subscriptionSourceMockThree->expects(self::once())
            ->method('getEventSubscriptions')
            ->willReturn([
                'event2' => [
                    'fields' => ['id', 'name', 'sku', 'total'],
                    'priority' => true
                ],
                'event4' => [
                    'fields' => ['name']
                ]
            ]);

        $this->subscriptionSourcePoolMock->expects(self::once())
            ->method('getSources')
            ->willReturn([$subscriptionSourceMockOne, $subscriptionSourceMockTwo, $subscriptionSourceMockThree]);

        self::assertEquals(
            [
                'event1' => [
                    'fields' => ['id', 'name']
                ],
                'event2' => [
                    'fields' => ['id', 'name', 'sku', 'total'],
                    'priority' => true
                ],
                'event3' => [
                    'fields' => ['order_id']
                ],
                'event4' => [
                    'fields' => ['name']
                ]
            ],
            $this->subscriptionLoader->getEventSubscriptions()
        );
    }
}
