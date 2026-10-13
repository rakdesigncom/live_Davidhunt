<?php
/************************************************************************
 *
 * ADOBE CONFIDENTIAL
 * ___________________
 *
 * Copyright 2023 Adobe
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

namespace Magento\AdobeCommerceEventsClient\Test\Unit\Event\Rule;

use Magento\AdobeCommerceEventsClient\Event\Context\ContextRetriever;
use Magento\AdobeCommerceEventsClient\Event\Event;
use Magento\AdobeCommerceEventsClient\Event\Operator\CustomOperatorInterface;
use Magento\AdobeCommerceEventsClient\Event\Operator\OperatorFactory;
use Magento\AdobeCommerceEventsClient\Event\Operator\OperatorInterface;
use Magento\AdobeCommerceEventsClient\Event\Rule\Rule;
use Magento\AdobeCommerceEventsClient\Event\Rule\RuleChecker;
use Magento\AdobeCommerceEventsClient\Event\Rule\RuleFactory;
use Magento\AdobeCommerceOutOfProcessExtensibility\Model\Context\ContextPool;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Tests for @see RuleChecker class
 */
class RuleCheckerTest extends TestCase
{
    /**
     * @var RuleFactory|MockObject
     */
    private RuleFactory|MockObject $ruleFactoryMock;

    /**
     * @var OperatorFactory|MockObject
     */
    private OperatorFactory|MockObject $operatorFactoryMock;

    /**
     * @var Event|MockObject
     */
    private Event|MockObject $eventMock;

    /**
     * @var ContextRetriever|MockObject
     */
    private ContextRetriever|MockObject $contextRetrieverMock;

    /**
     * @var ContextPool|MockObject
     */
    private ContextPool|MockObject $contextPoolMock;

    /**
     * @var RuleChecker
     */
    private RuleChecker $ruleChecker;

    protected function setUp(): void
    {
        $this->ruleFactoryMock = $this->createMock(RuleFactory::class);
        $this->operatorFactoryMock = $this->createMock(OperatorFactory::class);
        $this->contextRetrieverMock = $this->createMock(ContextRetriever::class);
        $this->contextPoolMock = $this->createMock(ContextPool::class);
        $this->eventMock = $this->createMock(Event::class);

        $this->ruleChecker = new RuleChecker(
            $this->ruleFactoryMock,
            $this->operatorFactoryMock,
            $this->contextRetrieverMock,
            $this->contextPoolMock
        );
    }

    public function testRulesAreEmpty()
    {
        $this->eventMock->expects(self::once())
            ->method('getRules')
            ->willReturn([]);
        $this->ruleFactoryMock->expects(self::never())
            ->method('create');
        $this->operatorFactoryMock->expects(self::never())
            ->method('create');

        self::assertTrue($this->ruleChecker->verify($this->eventMock, ['order_id' => 3]));
    }

    public function testFieldValueExist()
    {
        $rule = [
            'field' => 'order_id',
            'operator' => 'greaterThan',
            'value' => '2'
        ];
        $this->eventMock->expects(self::once())
            ->method('getRules')
            ->willReturn([$rule]);
        $this->ruleFactoryMock->expects(self::once())
            ->method('create')
            ->with($rule)
            ->willReturn(new Rule(...$rule));
        $operatorMock = $this->createMock(OperatorInterface::class);
        $operatorMock->expects(self::once())
            ->method('verify')
            ->with('2', '3')
            ->willReturn(true);
        $this->operatorFactoryMock->expects(self::once())
            ->method('create')
            ->with($rule['operator'])
            ->willReturn($operatorMock);

        self::assertTrue($this->ruleChecker->verify($this->eventMock, [
            'order_id' => 3,
            'status' => 'pending'
        ]));
    }

    public function testCustomOperatorNotValid()
    {
        $eventData = [
            'order_id' => 3,
            'status' => 'pending'
        ];
        $rule = [
            'field' => 'order_id',
            'operator' => 'onChange',
            'value' => ''
        ];
        $ruleObject = new Rule(...$rule);
        $this->eventMock->expects(self::once())
            ->method('getRules')
            ->willReturn([$rule]);
        $this->ruleFactoryMock->expects(self::once())
            ->method('create')
            ->with($rule)
            ->willReturn($ruleObject);
        $customOperatorMock = $this->createMock(CustomOperatorInterface::class);
        $customOperatorMock->expects(self::once())
            ->method('verify')
            ->with($ruleObject, $eventData)
            ->willReturn(true);
        $this->operatorFactoryMock->expects(self::once())
            ->method('create')
            ->with($rule['operator'])
            ->willReturn($customOperatorMock);

        self::assertTrue($this->ruleChecker->verify($this->eventMock, $eventData));
    }

    public function testNestedFieldValueExist()
    {
        $rule = [
            'field' => 'level_one.level_two.level_three.status',
            'operator' => 'equal',
            'value' => 'pending'
        ];
        $this->eventMock->expects(self::once())
            ->method('getRules')
            ->willReturn([$rule]);
        $this->ruleFactoryMock->expects(self::once())
            ->method('create')
            ->with($rule)
            ->willReturn(new Rule(...$rule));
        $operatorMock = $this->createMock(OperatorInterface::class);
        $operatorMock->expects(self::once())
            ->method('verify')
            ->with('pending', 'pending_3')
            ->willReturn(true);
        $this->operatorFactoryMock->expects(self::once())
            ->method('create')
            ->with($rule['operator'])
            ->willReturn($operatorMock);

        self::assertTrue($this->ruleChecker->verify($this->eventMock, [
            'order_id' => 3,
            'status' => 'pending',
            'level_one' => [
                'level_two' => [
                    'level_three' => [
                        'payment_id' => 3,
                        'status' => 'pending_3'
                    ]
                ]
            ]
        ]));
    }

    public function testNestedFieldValueExistMultipleRules()
    {
        $ruleOne = [
            'field' => 'level_one.level_two.level_three.status',
            'operator' => 'equal',
            'value' => 'pending'
        ];
        $ruleTwo = [
            'field' => 'level_one.level_two.level_three.level_four.status',
            'operator' => 'not-equal',
            'value' => 'pending'
        ];
        $this->eventMock->expects(self::once())
            ->method('getRules')
            ->willReturn([$ruleOne, $ruleTwo]);
        $this->ruleFactoryMock->expects(self::exactly(2))
            ->method('create')
            ->willReturnCallback(function (array $data) use ($ruleOne, $ruleTwo) {
                static $count = 0;
                switch ($count++) {
                    case 0:
                        self::assertEquals($ruleOne, $data);
                        return new Rule(...$ruleOne);
                    case 1:
                        self::assertEquals($ruleTwo, $data);
                        return new Rule(...$ruleTwo);
                };
            });
        $operatorMock = $this->createMock(OperatorInterface::class);
        $operatorMock->expects(self::exactly(2))
            ->method('verify')
            ->willReturnCallback(function (string $ruleValue, string $fieldValue) {
                static $count = 0;
                self::assertEquals('pending', $ruleValue);
                match ($count++) {
                    0 => self::assertEquals('pending_3', $fieldValue),
                    1 => self::assertEquals('pending_4', $fieldValue)
                };
                return true;
            });
        $this->operatorFactoryMock->expects(self::exactly(2))
            ->method('create')
            ->willReturnCallback(function (string $operator) use ($operatorMock, $ruleOne, $ruleTwo) {
                static $count = 0;
                match ($count++) {
                    0 => self::assertEquals($ruleOne['operator'], $operator),
                    1 => self::assertEquals($ruleTwo['operator'], $operator)
                };
                return $operatorMock;
            });

        self::assertTrue($this->ruleChecker->verify($this->eventMock, [
            'order_id' => 3,
            'status' => 'pending',
            'level_one' => [
                'level_two' => [
                    'level_three' => [
                        'payment_id' => 3,
                        'status' => 'pending_3',
                        'level_four' => [
                            'status' => 'pending_4'
                        ]
                    ]
                ]
            ]
        ]));
    }

    public function testNestedFieldValueDoesNotExist()
    {
        $rule = [
            'field' => 'payment.status',
            'operator' => 'equal',
            'value' => 'pending'
        ];
        $this->eventMock->expects(self::once())
            ->method('getRules')
            ->willReturn([$rule]);
        $this->ruleFactoryMock->expects(self::once())
            ->method('create')
            ->with($rule)
            ->willReturn(new Rule(...$rule));
        $operatorMock = $this->createMock(OperatorInterface::class);
        $operatorMock->expects(self::never())
            ->method('verify');
        $this->operatorFactoryMock->expects(self::once())
            ->method('create')
            ->with($rule['operator'])
            ->willReturn($operatorMock);

        self::assertFalse($this->ruleChecker->verify($this->eventMock, [
            'order_id' => 3,
            'status' => 'pending'
        ]));
    }

    public function testFieldStartsWithContextButNotInContextPool()
    {
        $rule = [
            'field' => 'context_field.status',
            'operator' => 'equal',
            'value' => 'active'
        ];
        $this->eventMock->expects(self::once())
            ->method('getRules')
            ->willReturn([$rule]);
        $this->ruleFactoryMock->expects(self::once())
            ->method('create')
            ->with($rule)
            ->willReturn(new Rule(...$rule));
        $this->contextPoolMock->expects(self::once())
            ->method('has')
            ->with('context_field')
            ->willReturn(false);
        $this->contextRetrieverMock->expects(self::never())
            ->method('getContextValue');
        $operatorMock = $this->createMock(OperatorInterface::class);
        $operatorMock->expects(self::never())
            ->method('verify');
        $this->operatorFactoryMock->expects(self::once())
            ->method('create')
            ->with($rule['operator'])
            ->willReturn($operatorMock);

        self::assertFalse($this->ruleChecker->verify($this->eventMock, []));
    }

    public function testMixedContextAndEventDataFieldRules()
    {
        $contextRule = [
            'field' => 'context_store.store_id',
            'operator' => 'equal',
            'value' => '1'
        ];
        $eventDataRule = [
            'field' => 'order_id',
            'operator' => 'greaterThan',
            'value' => '0'
        ];

        $this->eventMock->expects(self::once())
            ->method('getRules')
            ->willReturn([$contextRule, $eventDataRule]);

        $this->ruleFactoryMock->expects(self::exactly(2))
            ->method('create')
            ->willReturnCallback(function (array $data) use ($contextRule, $eventDataRule) {
                static $count = 0;
                switch ($count++) {
                    case 0:
                        self::assertEquals($contextRule, $data);
                        return new Rule(...$contextRule);
                    case 1:
                        self::assertEquals($eventDataRule, $data);
                        return new Rule(...$eventDataRule);
                };
            });
        $this->contextPoolMock->expects(self::once())
            ->method('has')
            ->with('context_store')
            ->willReturn(true);
        $this->contextRetrieverMock->expects(self::once())
            ->method('getContextValue')
            ->with('context_store.store_id', $this->eventMock)
            ->willReturn('1');

        $operatorMock = $this->createMock(OperatorInterface::class);
        $operatorMock->expects(self::exactly(2))
            ->method('verify')
            ->willReturnCallback(function (string $ruleValue, mixed $fieldValue) {
                static $count = 0;
                match ($count++) {
                    0 => self::assertEquals(['1', '1'], [$ruleValue, $fieldValue]),
                    1 => self::assertEquals(['0', '5'], [$ruleValue, (string)$fieldValue])
                };
                return true;
            });
        $this->operatorFactoryMock->expects(self::exactly(2))
            ->method('create')
            ->willReturnCallback(function (string $operator) use ($operatorMock, $contextRule, $eventDataRule) {
                static $count = 0;
                match ($count++) {
                    0 => self::assertEquals($contextRule['operator'], $operator),
                    1 => self::assertEquals($eventDataRule['operator'], $operator)
                };
                return $operatorMock;
            });

        self::assertTrue($this->ruleChecker->verify($this->eventMock, ['order_id' => 5]));
    }

    public function testMixedRulesContextRuleFails()
    {
        $contextRule = [
            'field' => 'context_store.store_id',
            'operator' => 'equal',
            'value' => '1'
        ];
        $eventDataRule = [
            'field' => 'order_id',
            'operator' => 'greaterThan',
            'value' => '0'
        ];

        $this->eventMock->expects(self::once())
            ->method('getRules')
            ->willReturn([$contextRule, $eventDataRule]);
        $this->ruleFactoryMock->expects(self::once())
            ->method('create')
            ->with($contextRule)
            ->willReturn(new Rule(...$contextRule));
        $this->contextPoolMock->expects(self::once())
            ->method('has')
            ->with('context_store')
            ->willReturn(true);
        $this->contextRetrieverMock->expects(self::once())
            ->method('getContextValue')
            ->with('context_store.store_id', $this->eventMock)
            ->willReturn('2');
        $operatorMock = $this->createMock(OperatorInterface::class);
        $operatorMock->expects(self::once())
            ->method('verify')
            ->with('1', '2')
            ->willReturn(false);
        $this->operatorFactoryMock->expects(self::once())
            ->method('create')
            ->with($contextRule['operator'])
            ->willReturn($operatorMock);

        self::assertFalse($this->ruleChecker->verify($this->eventMock, ['order_id' => 5]));
    }

    public function testContextFieldWithMultipleLevels()
    {
        $rule = [
            'field' => 'context_customer.address.region.code',
            'operator' => 'equal',
            'value' => 'CA'
        ];
        $this->eventMock->expects(self::once())
            ->method('getRules')
            ->willReturn([$rule]);
        $this->ruleFactoryMock->expects(self::once())
            ->method('create')
            ->with($rule)
            ->willReturn(new Rule(...$rule));
        $this->contextPoolMock->expects(self::once())
            ->method('has')
            ->with('context_customer')
            ->willReturn(true);
        $this->contextRetrieverMock->expects(self::once())
            ->method('getContextValue')
            ->with('context_customer.address.region.code', $this->eventMock)
            ->willReturn('CA');
        $operatorMock = $this->createMock(OperatorInterface::class);
        $operatorMock->expects(self::once())
            ->method('verify')
            ->with('CA', 'CA')
            ->willReturn(true);

        $this->operatorFactoryMock->expects(self::once())
            ->method('create')
            ->with($rule['operator'])
            ->willReturn($operatorMock);

        self::assertTrue($this->ruleChecker->verify($this->eventMock, []));
    }
}
