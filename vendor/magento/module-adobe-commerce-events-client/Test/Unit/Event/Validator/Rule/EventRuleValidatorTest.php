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

namespace Magento\AdobeCommerceEventsClient\Test\Unit\Event\Validator\Rule;

use Magento\AdobeCommerceEventsClient\Event\Event;
use Magento\AdobeCommerceEventsClient\Event\Operator\OperatorFactory;
use Magento\AdobeCommerceEventsClient\Event\Validator\Rule\EventRuleValidator;
use Magento\AdobeCommerceEventsClient\Event\Validator\ValidatorException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test for @see EventRuleValidator class
 */
class EventRuleValidatorTest extends TestCase
{
    /**
     * @var EventRuleValidator
     */
    private EventRuleValidator $eventRuleValidator;

    /**
     * @var OperatorFactory|MockObject
     */
    private $operatorFactoryMock;

    /**
     * @var Event|MockObject
     */
    private $eventMock;

    protected function setUp(): void
    {
        $this->operatorFactoryMock = $this->createMock(OperatorFactory::class);
        $this->eventMock = $this->createMock(Event::class);

        $this->eventRuleValidator = new EventRuleValidator($this->operatorFactoryMock);
    }

    public function testInvalidEventRuleOperator()
    {
        $rule = [
            'field' => 'order_id',
            'operator' => 'greater',
            'value' => '2'
        ];
        
        $this->eventMock->expects(self::exactly(2))
            ->method('getRules')
            ->willReturn([$rule]);
        $this->eventMock->expects(self::once())
            ->method('getParent')
            ->willReturn('observer.parent');

        $operatorNames = ["lessThan", "greaterThan", "regex", "in", "equal"];

        $this->expectException(ValidatorException::class);
        $this->expectExceptionMessage('"greater" is an invalid event rule operator name');

        $this->operatorFactoryMock->expects(self::once())
            ->method('getOperatorNames')
            ->willReturn($operatorNames);

        $this->eventRuleValidator->validate($this->eventMock);
    }

    public function testValidOperatorNames()
    {
        $rule_one = [
            'field' => 'order_id',
            'operator' => 'lessThan',
            'value' => '2'
        ];

        $rule_two = [
            'field' => 'order_id',
            'operator' => 'regex',
            'value' => '2'
        ];

        $this->eventMock->expects(self::exactly(2))
            ->method('getRules')
            ->willReturn([$rule_one, $rule_two]);
        $this->eventMock->expects(self::once())
            ->method('getParent')
            ->willReturn('observer.parent');

        $operatorNames = ["lessThan", "greaterThan", "regex", "in", "equal"];

        $this->operatorFactoryMock->expects(self::once())
            ->method('getOperatorNames')
            ->willReturn($operatorNames);

        $this->eventRuleValidator->validate($this->eventMock);
    }

    public function testMissingParentEventName()
    {
        $rule = [
            'field' => 'order_id',
            'operator' => 'greater',
            'value' => '2'
        ];

        $this->expectException(ValidatorException::class);
        $this->expectExceptionMessage('A parent event name must be set for rule-based event subscriptions');

        $this->operatorFactoryMock->expects(self::never())
            ->method('getOperatorNames');
        $this->eventMock->expects(self::once())
            ->method('getRules')
            ->willReturn([$rule]);
        $this->eventMock->expects(self::once())
            ->method('getParent');
        $this->operatorFactoryMock->expects(self::never())
            ->method('getOperatorNames');

        $this->eventRuleValidator->validate($this->eventMock);
    }
}
