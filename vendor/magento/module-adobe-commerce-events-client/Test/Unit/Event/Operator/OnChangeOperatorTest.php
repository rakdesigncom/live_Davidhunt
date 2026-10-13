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

namespace Magento\AdobeCommerceEventsClient\Test\Unit\Event\Operator;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\AdobeCommerceEventsClient\Event\Operator\OnChangeOperator;
use Magento\AdobeCommerceEventsClient\Event\Rule\Rule;
use Magento\AdobeCommerceEventsClient\Event\Operator\OperatorException;
use Magento\AdobeCommerceEventsClient\Event\Filter\EventFieldsFilter;

/**
 * Test for @see OnChangeOperator class
 */
class OnChangeOperatorTest extends TestCase
{
    /**
     * @var Rule|MockObject
     */
    private Rule|MockObject $ruleMock;

    /**
     * @var OnChangeOperator
     */
    private OnChangeOperator $operator;

    protected function setUp(): void
    {
        $this->ruleMock = $this->createMock(Rule::class);
        $this->operator = new OnChangeOperator();
    }

    public function testVerifyReturnsTrueWhenValueChanged()
    {
        $this->ruleMock->expects(self::once())
            ->method('getField')
            ->willReturn('quantity_and_stock_status.qty');
        $this->ruleMock->expects(self::once())
            ->method('getValue')
            ->willReturn('');

        $eventData = $this->getProductData();
        $eventData[EventFieldsFilter::FIELD_ORIGINAL_DATA] = $this->getProductData();
        $eventData['quantity_and_stock_status']['qty'] = 5;

        self::assertTrue($this->operator->verify($this->ruleMock, $eventData));
    }

    public function testVerifyReturnsFalseWhenValueNotChanged()
    {
        $this->ruleMock->expects(self::once())
            ->method('getField')
            ->willReturn('sku');
        $this->ruleMock->expects(self::once())
            ->method('getValue')
            ->willReturn('');

        $eventData = $this->getProductData();
        $eventData[EventFieldsFilter::FIELD_ORIGINAL_DATA] = $this->getProductData();

        self::assertFalse($this->operator->verify($this->ruleMock, $eventData));
    }

    public function testVerifyThrowsExceptionWhenOriginalDataMissing()
    {
        $this->expectException(OperatorException::class);
        $this->expectExceptionMessage(
            'Event payload does not contain original data for comparison in onChange operator.'
        );
        $this->ruleMock->expects(self::never())
            ->method('getField');
        $this->ruleMock->expects(self::once())
            ->method('getValue')
            ->willReturn('');

        $this->operator->verify($this->ruleMock, $this->getProductData());
    }

    public function testVerifyReturnsTrueWhenIsNewAndRuleValueIsEmpty()
    {
        $this->ruleMock->expects(self::never())
            ->method('getField');
        $this->ruleMock->expects(self::once())
            ->method('getValue')
            ->willReturn('');

        $eventData = $this->getProductData();
        $eventData[EventFieldsFilter::FIELD_IS_NEW] = true;

        self::assertTrue($this->operator->verify($this->ruleMock, $eventData));
    }

    public function testVerifyThrowsExceptionWhenPathDoesNotExist()
    {
        $this->expectException(OperatorException::class);
        $this->expectExceptionMessage(
            'Path "nonexistent_field" does not exist in event data for comparison in onChange operator.'
        );
        $this->ruleMock->expects(self::once())
            ->method('getField')
            ->willReturn('nonexistent_field');
        $this->ruleMock->expects(self::once())
            ->method('getValue')
            ->willReturn('');

        $eventData = $this->getProductData();
        $eventData[EventFieldsFilter::FIELD_ORIGINAL_DATA] = $this->getProductData();

        $this->operator->verify($this->ruleMock, $eventData);
    }

    public function testVerifyWithMultipleObjectData()
    {
        $this->ruleMock->expects(self::exactly(2))
            ->method('getField')
            ->willReturn('order.total');
        $this->ruleMock->expects(self::exactly(2))
            ->method('getValue')
            ->willReturn('order._origData.total');

        $eventData = $this->getProductOrderData();
        $eventData['order'][EventFieldsFilter::FIELD_ORIGINAL_DATA] = $eventData['order'];

        self::assertFalse($this->operator->verify($this->ruleMock, $eventData));

        $eventData['order']['total'] = '500.00';
        self::assertTrue($this->operator->verify($this->ruleMock, $eventData));
    }

    /**
     * Get sample product data
     *
     * @return array
     */
    private function getProductData(): array
    {
        return [
            'name' => 'Test Product',
            'sku' => 'TP001',
            'price' => 99.99,
            'quantity_and_stock_status' => [
                'qty' => 10
            ]
        ];
    }

    /**
     * Get sample product and order data
     *
     * @return array
     */
    private function getProductOrderData(): array
    {
        return [
            'product' => $this->getProductData(),
            'order' => [
                'increment_id' => '100000001',
                'status' => 'processing',
                'total' => 199.99,
            ]
        ];
    }
}
