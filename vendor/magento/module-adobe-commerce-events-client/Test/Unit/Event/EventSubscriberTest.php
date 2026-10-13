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

namespace Magento\AdobeCommerceEventsClient\Test\Unit\Event;

use Magento\AdobeCommerceEventsClient\Event\AdobeIoEventMetadata\SubscriberInterface;
use Magento\AdobeCommerceEventsClient\Event\Event;
use Magento\AdobeCommerceEventsClient\Event\EventField;
use Magento\AdobeCommerceEventsClient\Event\EventList;
use Magento\AdobeCommerceEventsClient\Event\EventSubscriber;
use Magento\AdobeCommerceEventsClient\Event\EventSubscriberInterface;
use Magento\AdobeCommerceEventsClient\Event\Validator\EventValidatorInterface;
use Magento\AdobeCommerceEventsClient\Event\Validator\ValidatorException;
use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\App\DeploymentConfig\Writer;
use Magento\Framework\Config\File\ConfigFilePool;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests for @see EventSubscriber class
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects))
 */
class EventSubscriberTest extends TestCase
{
    /**
     * @var EventSubscriber
     */
    private EventSubscriber $eventSubscriber;

    /**
     * @var Writer|MockObject
     */
    private $configWriterMock;

    /**
     * @var DeploymentConfig|MockObject
     */
    private $deploymentConfigMock;

    /**
     * @var EventValidatorInterface|MockObject
     */
    private $subscribeValidatorMock;

    /**
     * @var EventValidatorInterface|MockObject
     */
    private $unsubscribeValidatorMock;

    /**
     * @var SubscriberInterface|MockObject
     */
    private $ioMetadataSubscriberMock;

    /**
     * @var EventList|MockObject
     */
    private $eventListMock;

    /**
     * @var MockObject|LoggerInterface
     */
    private $loggerMock;

    /**
     * @var EventValidatorInterface|MockObject
     */
    private $updateValidatorMock;

    /**
     * @var Event|MockObject
     */
    private $eventMock;

    protected function setUp(): void
    {
        $this->eventMock = $this->createMock(Event::class);
        $this->configWriterMock = $this->createMock(Writer::class);
        $this->deploymentConfigMock = $this->createMock(DeploymentConfig::class);
        $this->subscribeValidatorMock = $this->createMock(EventValidatorInterface::class);
        $this->unsubscribeValidatorMock = $this->createMock(EventValidatorInterface::class);
        $this->ioMetadataSubscriberMock = $this->createMock(SubscriberInterface::class);
        $this->eventListMock = $this->createMock(EventList::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->updateValidatorMock = $this->createMock(EventValidatorInterface::class);

        $this->eventSubscriber = new EventSubscriber(
            $this->configWriterMock,
            $this->deploymentConfigMock,
            $this->subscribeValidatorMock,
            $this->unsubscribeValidatorMock,
            $this->ioMetadataSubscriberMock,
            $this->eventListMock,
            $this->loggerMock,
            $this->updateValidatorMock
        );
    }

    public function testSubscribeValidationFailed(): void
    {
        $this->expectException(ValidatorException::class);
        $this->expectExceptionMessage('validation error');

        $this->subscribeValidatorMock->expects(self::once())
            ->method('validate')
            ->with($this->eventMock)
            ->willThrowException(new ValidatorException(__('validation error')));
        $this->configWriterMock->expects(self::never())
            ->method('saveConfig');

        $this->eventSubscriber->subscribe($this->eventMock);
    }

    public function testSubscribeExceptionOnCreatingIoMetadata(): void
    {
        $this->expectException(ValidatorException::class);
        $this->expectExceptionMessage('validation error');

        $eventName = 'observer.event.test';

        $this->eventMock->expects(self::any())
            ->method('getName')
            ->willReturn($eventName);
        $this->eventMock->expects(self::any())
            ->method('getEventFields')
            ->willReturn([
                new EventField([EventField::NAME => 'id']),
                new EventField([EventField::NAME => 'name'])
            ]);
        $this->ioMetadataSubscriberMock->expects(self::once())
            ->method('create')
            ->with($this->eventMock)
            ->willThrowException(new \Exception('validation error'));
        $this->configWriterMock->expects(self::once())
            ->method('saveConfig')
            ->with(
                [
                    ConfigFilePool::APP_ENV => [
                        EventSubscriberInterface::IO_EVENTS_CONFIG_NAME => [
                            $eventName => [
                                'enabled' => 1,
                                'fields' => [
                                    'id',
                                    'name'
                                ]
                            ],
                        ]
                    ],
                ]
            );
        $this->eventListMock->expects(self::once())
            ->method('reset');

        $this->eventSubscriber->subscribe($this->eventMock);
    }

    /**
     * @return void
     * @throws ValidatorException
     */
    public function testSubscribe(): void
    {
        $this->setEventConfigSaveExpectations();
        $this->eventListMock->expects(self::once())
            ->method('reset');
        $this->loggerMock->expects(self::once())
            ->method('info');
        $this->subscribeValidatorMock->expects(self::once())
            ->method('validate')
            ->with($this->eventMock);
        $this->ioMetadataSubscriberMock->expects(self::once())
            ->method('create')
            ->with($this->eventMock)
            ->willReturn(true);

        $this->eventSubscriber->subscribe($this->eventMock);
    }

    /**
     * Sets expectations for saving an event subscription to the deployment configuration
     *
     * @return void
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    private function setEventConfigSaveExpectations(): void
    {
        $eventName = 'observer.event.test';
        $fieldsData = [
            ['name' => 'field_one', 'converter' => 'converter_one'],
            'field_two'
        ];

        foreach ($fieldsData as $fieldData) {
            if (is_string($fieldData)) {
                $fieldData = [EventField::NAME => $fieldData];
            }
            $eventFields[] = new EventField($fieldData);
        }

        $this->eventMock->expects(self::any())
            ->method('getName')
            ->willReturn($eventName);
        $this->eventMock->expects(self::once())
            ->method('isHipaaAuditRequired')
            ->willReturn(true);
        $this->eventMock->expects(self::once())
            ->method('getEventFields')
            ->willReturn($eventFields);
        $this->eventMock->expects(self::exactly(2))
            ->method('getParent')
            ->willReturn('parent.event');
        $this->eventMock->expects(self::exactly(3))
            ->method('getDestination')
            ->willReturn('custom-destination');
        $this->deploymentConfigMock->expects(self::once())
            ->method('get')
            ->with(EventSubscriberInterface::IO_EVENTS_CONFIG_NAME, [])
            ->willReturn([
                'observer.event.test' => [
                    'fields' => [
                        'field_1',
                        'field_2',
                        'field_3',
                    ],
                    'enabled' => 0
                ],
                'observer.event.test_two' => [
                    'fields' => [
                        'field_1',
                    ],
                    'enabled' => 1
                ]
            ]);
        $this->configWriterMock->expects(self::once())
            ->method('saveConfig')
            ->with(
                [
                    ConfigFilePool::APP_ENV => [
                        EventSubscriberInterface::IO_EVENTS_CONFIG_NAME => [
                            'observer.event.test' => [
                                'fields' => [
                                    [
                                        'name' => 'field_one',
                                        'converter' => 'converter_one',
                                    ],
                                    'field_two'
                                ],
                                'enabled' => 1,
                                'hipaaAuditRequired' => 1,
                                'parent' => 'parent.event',
                                'destination' => 'custom-destination'
                            ],
                            'observer.event.test_two' => [
                                'fields' => [
                                    'field_1',
                                ],
                                'enabled' => 1
                            ]
                        ]
                    ],
                ],
                true
            );
    }

    /**
     * @param string $eventName
     * @param string $eventNameInConfig
     * @return void
     * @throws ValidatorException
     *
     */
    #[DataProvider('unsubscribeDataProvider')]
    public function testUnsubscribe(string $eventName, string $eventNameInConfig): void
    {
        $this->eventMock->expects(self::any())
            ->method('getName')
            ->willReturn($eventName);
        $this->unsubscribeValidatorMock->expects(self::once())
            ->method('validate')
            ->with($this->eventMock);
        $this->ioMetadataSubscriberMock->expects(self::once())
            ->method('delete')
            ->with($this->eventMock)
            ->willReturn(true);
        $this->deploymentConfigMock->expects(self::once())
            ->method('get')
            ->with(EventSubscriberInterface::IO_EVENTS_CONFIG_NAME, [])
            ->willReturn([
                $eventNameInConfig => [
                    'fields' => [
                        'field_1',
                        'field_2',
                        'field_3',
                    ],
                    'enabled' => 1
                ],
            ]);
        $this->configWriterMock->expects(self::once())
            ->method('saveConfig')
            ->with(
                [
                    ConfigFilePool::APP_ENV => [
                        EventSubscriberInterface::IO_EVENTS_CONFIG_NAME => [
                            $eventNameInConfig => [
                                'fields' => [
                                    'field_1',
                                    'field_2',
                                    'field_3',
                                ],
                                'enabled' => 0
                            ],
                        ]
                    ],
                ]
            );
        $this->eventListMock->expects(self::once())
            ->method('reset');
        $this->loggerMock->expects(self::once())
            ->method('info');

        $this->eventSubscriber->unsubscribe($this->eventMock);
    }

    /**
     * @return array
     */
    public static function unsubscribeDataProvider(): array
    {
        return [
            [
                'observer.event.test',
                'observer.event.test'
            ],
            [
                'casetest.observer.event.test',
                'CaseTest.observer.event.test'
            ]
        ];
    }

    public function testUnsubscribeExceptionOnProviderRetrieve(): void
    {
        $this->expectException(ValidatorException::class);
        $this->expectExceptionMessage('validation error');

        $eventName = 'observer.event.test';

        $this->eventMock->expects(self::any())
            ->method('getName')
            ->willReturn($eventName);
        $this->ioMetadataSubscriberMock->expects(self::once())
            ->method('delete')
            ->with($this->eventMock)
            ->willThrowException(new \Exception('validation error'));
        $this->deploymentConfigMock->expects(self::once())
            ->method('get')
            ->willReturn(
                [
                    $eventName => [
                        'enabled' => 1
                    ],
                ]
            );
        $this->configWriterMock->expects(self::once())
            ->method('saveConfig')
            ->with(
                [
                    ConfigFilePool::APP_ENV => [
                        EventSubscriberInterface::IO_EVENTS_CONFIG_NAME => [
                            $eventName => [
                                'enabled' => 0
                            ],
                        ]
                    ],
                ]
            );
        $this->eventListMock->expects(self::once())
            ->method('reset');

        $this->eventSubscriber->unsubscribe($this->eventMock);
    }

    public function testUpdate(): void
    {
        $this->setEventConfigSaveExpectations();
        $this->updateValidatorMock->expects(self::once())
            ->method('validate');
        $this->eventListMock->expects(self::once())
            ->method('reset');

        $this->eventSubscriber->update($this->eventMock);
    }

    public function testUpdateValidationFailed(): void
    {
        $updateValidationError = 'update validation error';
        $this->expectException(ValidatorException::class);
        $this->expectExceptionMessage($updateValidationError);

        $this->updateValidatorMock->expects(self::once())
            ->method('validate')
            ->with($this->eventMock)
            ->willThrowException(new ValidatorException(__($updateValidationError)));
        $this->configWriterMock->expects(self::never())
            ->method('saveConfig');

        $this->eventSubscriber->update($this->eventMock);
    }
}
