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

namespace Magento\AdobeCommerceOutOfProcessExtensibility\Test\Unit\Model\Logger;

use Magento\AdobeCommerceOutOfProcessExtensibility\Model\Logger\RequestIdProcessor;
use Magento\AdobeCommerceOutOfProcessExtensibility\Model\Request\RequestIdInterface;
use Monolog\LogRecord;
use Monolog\Level;
use PHPUnit\Framework\TestCase;

/**
 * Tests for @see RequestIdProcessor
 */
class RequestIdProcessorTest extends TestCase
{
    /**
     * @var RequestIdProcessor
     */
    private RequestIdProcessor $processor;

    public function setUp(): void
    {
        $requestIdMock = $this->createMock(RequestIdInterface::class);
        $requestIdMock->expects(self::once())
            ->method('get')
            ->willReturn('test-request-id');

        $this->processor = new RequestIdProcessor($requestIdMock);
    }

    public function testInvokeWithArrayRecordAddsRequestId()
    {
        $record = [
            'context' => ['foo' => 'bar'],
            'message' => 'test'
        ];

        $processor = $this->processor;
        $result = $processor($record);

        self::assertArrayHasKey('context', $result);
        self::assertSame('test-request-id', $result['context'][RequestIdInterface::REQUEST_ID_HEADER]);
        self::assertSame('bar', $result['context']['foo']);
    }

    public function testInvokeWithLogRecordAddsRequestId()
    {
        if (!class_exists(LogRecord::class)) {
            $this->markTestSkipped('Monolog 3 is required for this test.');
        }

        $record = new LogRecord(
            datetime: new \DateTimeImmutable(),
            channel: 'test',
            level: Level::Debug,
            message: 'test',
            context: ['foo' => 'bar'],
            extra: []
        );

        $processor = $this->processor;
        $result = $processor($record);

        self::assertInstanceOf(LogRecord::class, $result);
        self::assertSame('test-request-id', $result->context[RequestIdInterface::REQUEST_ID_HEADER]);
        self::assertSame('bar', $result->context['foo']);
    }
}
