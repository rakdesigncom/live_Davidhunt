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

use Magento\AdobeCommerceEventsClient\Config\AdobeIoConfigurationChecker;
use Magento\AdobeIoEventsClient\Api\EventProviderInterface;
use Magento\AdobeIoEventsClient\Exception\InvalidConfigurationException;
use Magento\AdobeIoEventsClient\Model\AdobeIOConfigurationProvider;
use Magento\AdobeIoEventsClient\Model\Config\Source\AuthorizationType;
use Magento\AdobeIoEventsClient\Model\Data\AdobeConsoleConfiguration\AdobeConsoleConfiguration;
use Magento\Framework\Exception\NotFoundException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Tests for @see AdobeIoConfigurationChecker
 */
class AdobeIoConfigurationCheckerTest extends TestCase
{
    /**
     * @var AdobeIOConfigurationProvider|MockObject
     */
    private AdobeIOConfigurationProvider|MockObject $configurationProviderMock;

    /**
     * @var AdobeIoConfigurationChecker
     */
    private AdobeIoConfigurationChecker $configurationChecker;

    protected function setUp(): void
    {
        $this->configurationProviderMock = $this->createMock(AdobeIOConfigurationProvider::class);
        $this->configurationChecker = new AdobeIoConfigurationChecker($this->configurationProviderMock);
    }

    public function testIsComplete()
    {
        $this->configurationProviderMock->expects(self::once())
            ->method('getScopeConfig')
            ->with(AdobeIOConfigurationProvider::XML_PATH_ADOBE_IO_AUTHORIZATION_TYPE)
            ->willReturn(AuthorizationType::OAUTH);
        $this->configurationProviderMock->expects(self::never())
            ->method('getPrivateKey');
        $this->configurationProviderMock->expects(self::once())
            ->method('getConfiguration')
            ->willReturn($this->createMock(AdobeConsoleConfiguration::class));

        $this->assertTrue($this->configurationChecker->isComplete());
    }

    public function testIsCompleteNoPrivateKey()
    {
        $this->configurationProviderMock->expects(self::once())
            ->method('getScopeConfig')
            ->with(AdobeIOConfigurationProvider::XML_PATH_ADOBE_IO_AUTHORIZATION_TYPE)
            ->willReturn(AuthorizationType::JWT);
        $this->configurationProviderMock->expects(self::once())
            ->method('getPrivateKey')
            ->willThrowException(new NotFoundException(__('Private key not found')));
        $this->configurationProviderMock->expects(self::never())
            ->method('getConfiguration');

        $this->assertFalse($this->configurationChecker->isComplete());
    }

    public function testIsCompleteInvalidConfiguration()
    {
        $this->configurationProviderMock->expects(self::once())
            ->method('getScopeConfig')
            ->with(AdobeIOConfigurationProvider::XML_PATH_ADOBE_IO_AUTHORIZATION_TYPE)
            ->willReturn(AuthorizationType::OAUTH);
        $this->configurationProviderMock->expects(self::never())
            ->method('getPrivateKey');
        $this->configurationProviderMock->expects(self::once())
            ->method('getConfiguration')
            ->willThrowException(new InvalidConfigurationException(__('Invalid configuration')));

        $this->assertTrue($this->configurationChecker->isComplete());
    }
}
