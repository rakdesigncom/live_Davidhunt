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

namespace Magento\AdobeCommerceEventsClient\Test\Unit\Event\EventProvider\Webapi;

use Magento\AdobeCommerceEventsClient\Api\Data\EventDataInterface;
use Magento\AdobeCommerceEventsClient\Api\Data\EventProviderInterface as EventDataProviderInterface;
use Magento\AdobeIoEventsClient\Api\EventProviderInterface;
use Magento\AdobeCommerceEventsClient\Event\Event;
use Magento\AdobeCommerceEventsClient\Event\EventProvider\Webapi\EventDataProviderConverter;
use Magento\AdobeIoEventsClient\Model\AdobeIOConfigurationProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test for @see EventDataProviderConverter
 */
class EventDataProviderConverterTest extends TestCase
{
    /**
     * @var AdobeIOConfigurationProvider|MockObject
     */
    private $configurationProviderMock;

    /**
     * @var EventDataProviderConverter
     */
    private $converter;

    protected function setUp(): void
    {
        $this->configurationProviderMock = $this->createMock(AdobeIOConfigurationProvider::class);
        $this->converter = new EventDataProviderConverter($this->configurationProviderMock);
    }

    public function testExecuteWithUnconfiguredProvider(): void
    {
        $outputData = [EventDataProviderInterface::PROVIDER_ID => 'some_id'];

        $this->configurationProviderMock->expects(self::once())
            ->method('getProvider')
            ->willReturn(null);

        $result = $this->converter->execute($this->createMock(EventDataInterface::class), $outputData);

        self::assertSame($outputData, $result);
    }

    public function testExecuteWithDefaultProviderId(): void
    {
        $outputData = [EventDataProviderInterface::PROVIDER_ID => Event::EVENT_PROVIDER_DEFAULT];

        $providerMock = $this->createMock(EventProviderInterface::class);
        $providerMock->expects(self::once())
            ->method('getId')
            ->willReturn('configured_provider_id');
        $this->configurationProviderMock->expects(self::once())
            ->method('getProvider')
            ->willReturn($providerMock);

        $result = $this->converter->execute($this->createMock(EventDataInterface::class), $outputData);

        self::assertSame(
            'configured_provider_id',
            $result[EventDataProviderInterface::PROVIDER_ID]
        );
    }

    public function testExecuteWithEmptyProviderId(): void
    {
        $outputData = [];

        $providerMock = $this->createMock(EventProviderInterface::class);
        $providerMock->expects(self::once())
            ->method('getId')
            ->willReturn('configured_provider_id');
        $this->configurationProviderMock->expects(self::once())
            ->method('getProvider')
            ->willReturn($providerMock);

        $result = $this->converter->execute($this->createMock(EventDataInterface::class), $outputData);

        self::assertSame(
            'configured_provider_id',
            $result[EventDataProviderInterface::PROVIDER_ID]
        );
    }
}
