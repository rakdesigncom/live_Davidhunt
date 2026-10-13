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

namespace Magento\AdobeCommerceEventsClient\Test\Unit\Config\SubscriptionSource;

use Magento\AdobeCommerceEventsClient\Config\SubscriptionSource\XmlConfiguration;
use Magento\AdobeCommerceEventsClient\Config\Reader;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Test for @see XmlConfiguration
 */
class XmlConfigurationTest extends TestCase
{
    /**
     * @var Reader|MockObject
     */
    private Reader|MockObject $readerMock;

    /**
     * @var XmlConfiguration
     */
    private XmlConfiguration $xmlConfiguration;

    protected function setUp(): void
    {
        $this->readerMock = $this->createMock(Reader::class);
        $this->xmlConfiguration = new XmlConfiguration($this->readerMock);
    }

    public function testGetEventSubscriptions(): void
    {
        $this->readerMock->expects(self::once())
            ->method('read')
            ->willReturn(['eventName' => 'eventData']);

        self::assertEquals(
            ['eventName' => 'eventData'],
            $this->xmlConfiguration->getEventSubscriptions()
        );
    }

    public function testIsOptional(): void
    {
        self::assertFalse($this->xmlConfiguration->isOptional());
    }
}
