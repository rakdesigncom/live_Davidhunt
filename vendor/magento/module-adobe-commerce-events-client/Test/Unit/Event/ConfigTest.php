<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Test\Unit\Event;

use Magento\AdobeCommerceEventsClient\Event\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Tests for @see Config class
 */
class ConfigTest extends TestCase
{
    /**
     * @var Config
     */
    private Config $config;

    /**
     * @var ScopeConfigInterface|MockObject
     */
    private $scopeConfigMock;

    protected function setUp(): void
    {
        $this->scopeConfigMock = $this->createMock(ScopeConfigInterface::class);

        $this->config = new Config($this->scopeConfigMock);
    }

    public function testGetMerchantId()
    {
        $merchantId = 'demo';
        $this->scopeConfigMock->expects(self::once())
            ->method('getValue')
            ->with('adobe_io_events/eventing/merchant_id')
            ->willReturn($merchantId);

        self::assertEquals(
            $merchantId,
            $this->config->getMerchantId()
        );
    }

    public function testGetEnvironmentId()
    {
        $environmentId = 'demo';
        $this->scopeConfigMock->expects(self::once())
            ->method('getValue')
            ->with('adobe_io_events/eventing/env_id')
            ->willReturn($environmentId);

        self::assertEquals(
            $environmentId,
            $this->config->getEnvironmentId()
        );
    }

    public function testIsEnabled()
    {
        $isEnabled = true;
        $this->scopeConfigMock->expects(self::once())
            ->method('getValue')
            ->with('adobe_io_events/eventing/enabled')
            ->willReturn($isEnabled);

        self::assertEquals(
            $isEnabled,
            $this->config->isEnabled()
        );
    }

    public function testGetInstanceId()
    {
        $instanceId = 'demo';
        $this->scopeConfigMock->expects(self::once())
            ->method('getValue')
            ->with('adobe_io_events/integration/instance_id')
            ->willReturn($instanceId);

        self::assertEquals(
            $instanceId,
            $this->config->getInstanceId()
        );
    }

    public function testGetStageEndPointUrl(): void
    {
        $stageEndPoint = 'https://commerce-eventing-stage.adobe.io';

        $this->scopeConfigMock->expects(self::exactly(2))
            ->method('getValue')
            ->willReturnCallback(function (string $path) use ($stageEndPoint) {
                static $count = 0;
                switch ($count++) {
                    case 0:
                        self::assertEquals('adobe_io_events/integration/adobe_io_environment', $path);
                        return 'staging';
                    case 1:
                        self::assertEquals('adobe_io_events/integration/endpoint_stage', $path);
                        return $stageEndPoint;
                };
                return null;
            });

        self::assertEquals(
            $stageEndPoint,
            $this->config->getEndpointUrl()
        );
    }

    public function testGetProdEndPointUrl(): void
    {
        $prodEndPoint = 'https://commerce-eventing.adobe.io';

        $this->scopeConfigMock->expects(self::exactly(3))
            ->method('getValue')
            ->willReturnCallback(function (string $path) use ($prodEndPoint) {
                static $count = 0;
                switch ($count++) {
                    case 0:
                    case 1:
                        self::assertEquals('adobe_io_events/integration/adobe_io_environment', $path);
                        return 'production';
                    case 2:
                        self::assertEquals('adobe_io_events/integration/endpoint_production', $path);
                        return $prodEndPoint;
                };
                return null;
            });

        self::assertEquals(
            $prodEndPoint,
            $this->config->getEndpointUrl()
        );
    }

    public function testGetDevEndPointUrl()
    {
        $devEndPoint = 'https://commerce-eventing-dev.adobe.io';

        $this->scopeConfigMock->expects(self::exactly(3))
            ->method('getValue')
            ->willReturnCallback(function (string $path) use ($devEndPoint) {
                static $count = 0;
                switch ($count++) {
                    case 0:
                    case 1:
                        self::assertEquals('adobe_io_events/integration/adobe_io_environment', $path);
                        return 'development';
                    case 2:
                        self::assertEquals('adobe_io_events/integration/endpoint_dev', $path);
                        return $devEndPoint;
                };
                return null;
            });

        self::assertEquals(
            $devEndPoint,
            $this->config->getEndpointUrl()
        );
    }
}
