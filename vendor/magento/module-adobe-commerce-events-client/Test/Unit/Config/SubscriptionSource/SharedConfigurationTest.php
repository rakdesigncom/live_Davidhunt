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

use Magento\AdobeCommerceEventsClient\Config\SubscriptionSource\SharedConfiguration;
use Magento\AdobeCommerceEventsClient\Event\Event;
use Magento\AdobeCommerceEventsClient\Event\EventSubscriberInterface;
use Magento\AdobeCommerceEventsClient\Event\InvalidConfigurationException;
use Magento\Framework\App\DeploymentConfig;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Test for @see SharedConfiguration
 */
class SharedConfigurationTest extends TestCase
{
    /**
     * @var DeploymentConfig|MockObject
     */
    private DeploymentConfig|MockObject $deploymentConfigMock;

    /**
     * @var SharedConfiguration
     */
    private SharedConfiguration $sharedConfiguration;

    protected function setUp(): void
    {
        $this->deploymentConfigMock = $this->createMock(DeploymentConfig::class);
        $this->sharedConfiguration = new SharedConfiguration($this->deploymentConfigMock);
    }

    public function testGetEventSubscriptionsEmptyConfig(): void
    {
        $this->deploymentConfigMock->expects(self::once())
            ->method('get')
            ->with(EventSubscriberInterface::IO_EVENTS_CONFIG_NAME)
            ->willReturn([]);

        self::assertEquals([], $this->sharedConfiguration->getEventSubscriptions());
    }

    public function testGetEventSubscriptionsThrowsExceptionOnInvalidConfiguration(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $this->deploymentConfigMock->expects(self::once())
            ->method('get')
            ->with(EventSubscriberInterface::IO_EVENTS_CONFIG_NAME)
            ->willReturn(['eventName' => 'eventData']);

        $this->sharedConfiguration->getEventSubscriptions();
    }

    public function testGetEventSubscriptionsWithProcessedFields(): void
    {
        $this->deploymentConfigMock->expects(self::once())
            ->method('get')
            ->with(EventSubscriberInterface::IO_EVENTS_CONFIG_NAME)
            ->willReturn([
                'config_php_event1' => [
                    'fields' => ['id'],
                    'parent' => 'parent_event',
                    'enabled' => 1,
                    'hipaaAuditRequired' => 1,
                ],
                'config_php_event2' => [
                    'fields' => ['name'],
                    'enabled' => 0,
                    'priority' => 1,
                    'providerId' => 'test-provider'
                ],
            ]);

        self::assertEquals(
            [
                'config_php_event1' => [
                    'fields' => [
                        [
                            'name' => 'id',
                            'converter' => null,
                            'source' => null
                        ],
                    ],
                    'name' => 'config_php_event1',
                    'parent' => 'parent_event',
                    'optional' => true,
                    'rules' => [],
                    'processors' => [],
                    'enabled' => true,
                    'priority' => false,
                    'destination' => Event::DESTINATION_DEFAULT,
                    'hipaaAuditRequired' => true,
                    'providerId' => null
                ],
                'config_php_event2' => [
                    'fields' => [
                        [
                            'name' => 'name',
                            'converter' => null,
                            'source' => null
                        ],
                    ],
                    'name' => 'config_php_event2',
                    'parent' => null,
                    'optional' => true,
                    'rules' => [],
                    'processors' => [],
                    'enabled' => false,
                    'priority' => true,
                    'destination' => Event::DESTINATION_DEFAULT,
                    'hipaaAuditRequired' => false,
                    'providerId' => 'test-provider'
                ],
            ],
            $this->sharedConfiguration->getEventSubscriptions()
        );
    }

    public function testIsOptional(): void
    {
        self::assertTrue($this->sharedConfiguration->isOptional());
    }
}
