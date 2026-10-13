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

namespace Magento\AdobeCommerceEventsClient\Test\Unit\Event\Merger;

use Magento\AdobeCommerceEventsClient\Event\Event;
use Magento\AdobeCommerceEventsClient\Event\EventFactory;
use Magento\AdobeCommerceEventsClient\Event\EventField;
use Magento\AdobeCommerceEventsClient\Event\Merger\EventMerger;
use Magento\AdobeCommerceEventsClient\Event\Rule\RuleInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Tests for @see EventMerger
 */
class EventMergerTest extends TestCase
{
    /**
     * @var EventFactory|MockObject
     */
    private EventFactory|MockObject $eventFactoryMock;

    /**
     * @var EventMerger
     */
    private EventMerger $eventMerger;

    protected function setUp(): void
    {
        $this->eventFactoryMock = $this->createMock(EventFactory::class);
        $this->eventMerger = new EventMerger($this->eventFactoryMock);
    }

    public function testMergeFields()
    {
        $baseEventMock = $this->createMock(Event::class);
        $eventToMergeMock = $this->createMock(Event::class);

        $existingFields = [
            new EventField([
                EventField::NAME => 'field1',
                EventField::CONVERTER => 'converter1',
            ]),
            new EventField([
                EventField::NAME => 'field2'
            ]),
            new EventField([
                EventField::NAME => 'field3'
            ])
        ];

        $updateFields = [
            new EventField([
                EventField::NAME => 'field1',
            ]),
            new EventField([
                EventField::NAME => 'field2',
                EventField::CONVERTER => 'converter2'
            ]),
            new EventField([
                EventField::NAME => 'field4',
                EventField::SOURCE => 'source4',
            ]),
        ];

        $baseEventMock->expects(self::once())
            ->method('getEventFields')
            ->willReturn($existingFields);
        $eventToMergeMock->expects(self::once())
            ->method('getEventFields')
            ->willReturn($updateFields);

        $this->eventFactoryMock->expects(self::once())
            ->method('create')
            ->with($this->callback(function ($params) {
                return $params[Event::EVENT_FIELDS] == [
                    [
                        EventField::NAME => 'field1'
                    ],
                    [
                        EventField::NAME => 'field2',
                        EventField::CONVERTER => 'converter2'
                    ],
                    [
                        EventField::NAME => 'field3'
                    ],
                    [
                        EventField::NAME => 'field4',
                        EventField::SOURCE => 'source4',
                    ]
                ];
            }));

        $this->eventMerger->merge($baseEventMock, $eventToMergeMock);
    }

    public function testMergeRules()
    {
        $baseEventMock = $this->createMock(Event::class);
        $eventToMergeMock = $this->createMock(Event::class);

        $existingRules = [
            [
                RuleInterface::RULE_FIELD => 'field1',
                RuleInterface::RULE_OPERATOR => 'operator1',
                RuleInterface::RULE_VALUE => 'value1'
            ],
            [
                RuleInterface::RULE_FIELD => 'field2',
                RuleInterface::RULE_OPERATOR => 'operator2',
                RuleInterface::RULE_VALUE => 'value2'
            ]
        ];

        $updateRules = [
            [
                RuleInterface::RULE_FIELD => 'field1',
                RuleInterface::RULE_OPERATOR => 'operator1',
                RuleInterface::RULE_VALUE => 'value3'
            ],
            [
                RuleInterface::RULE_FIELD => 'field2',
                RuleInterface::RULE_OPERATOR => 'operator3',
                RuleInterface::RULE_VALUE => 'value4'
            ]
        ];

        $baseEventMock->expects(self::once())
            ->method('getRules')
            ->willReturn($existingRules);
        $eventToMergeMock->expects(self::once())
            ->method('getRules')
            ->willReturn($updateRules);

        $this->eventFactoryMock->expects(self::once())
            ->method('create')
            ->with($this->callback(function ($params) {
                return $params[Event::EVENT_RULES] == [
                    [
                        RuleInterface::RULE_FIELD => 'field1',
                        RuleInterface::RULE_OPERATOR => 'operator1',
                        RuleInterface::RULE_VALUE => 'value3'
                    ],
                    [
                        RuleInterface::RULE_FIELD => 'field2',
                        RuleInterface::RULE_OPERATOR => 'operator2',
                        RuleInterface::RULE_VALUE => 'value2'
                    ],
                    [
                        RuleInterface::RULE_FIELD => 'field2',
                        RuleInterface::RULE_OPERATOR => 'operator3',
                        RuleInterface::RULE_VALUE => 'value4'
                    ]
                ];
            }));

        $this->eventMerger->merge($baseEventMock, $eventToMergeMock);
    }
}
