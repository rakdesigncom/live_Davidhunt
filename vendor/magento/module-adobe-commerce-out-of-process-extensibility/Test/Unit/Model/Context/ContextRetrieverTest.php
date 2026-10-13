<?php
/************************************************************************
 *
 * ADOBE CONFIDENTIAL
 * ___________________
 *
 * Copyright 2026 Adobe
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

namespace Magento\AdobeCommerceOutOfProcessExtensibility\Test\Unit\Model\Context;

use Exception;
use Magento\AdobeCommerceOutOfProcessExtensibility\Model\Context\ArgumentExtractor;
use Magento\AdobeCommerceOutOfProcessExtensibility\Model\Context\ContextPool;
use Magento\AdobeCommerceOutOfProcessExtensibility\Model\Context\ContextRetriever;
use Magento\AdobeCommerceOutOfProcessExtensibility\Model\Context\ContextRetrieverException;
use Magento\AdobeCommerceOutOfProcessExtensibility\Model\Util\CaseConverter;
use Magento\AdobeCommerceOutOfProcessExtensibility\Test\Unit\Model\Context\_files\ContextClass;
use Magento\AdobeCommerceOutOfProcessExtensibility\Test\Unit\Model\Context\_files\SampleClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Tests for @see ContextRetriever
 */
class ContextRetrieverTest extends TestCase
{
    /**
     * @var ContextRetriever
     */
    private ContextRetriever $contextRetriever;

    /**
     * @var ContextPool|MockObject
     */
    private ContextPool|MockObject $contextPoolMock;

    protected function setUp(): void
    {
        $this->contextPoolMock = $this->createMock(ContextPool::class);
        $this->contextRetriever = new ContextRetriever(
            new ArgumentExtractor(),
            new CaseConverter(),
            $this->contextPoolMock,
        );
    }

    public function testGetContextValueNoArguments()
    {
        $source = 'context_test.get_sample_class.get_value_one';

        $this->contextPoolMock->expects(self::once())
            ->method('has')
            ->with('context_test')
            ->willReturn(true);
        $this->contextPoolMock->expects(self::once())
            ->method('get')
            ->with('context_test')
            ->willReturn(new ContextClass());

        $this->assertEquals(
            'test_value_one',
            $this->contextRetriever->getContextValue($source)
        );
    }

    public function testGetContextValueUsingArguments()
    {
        $source = 'context_test.concatenate{one:two}';

        $this->contextPoolMock->expects(self::once())
            ->method('has')
            ->with('context_test')
            ->willReturn(true);
        $this->contextPoolMock->expects(self::once())
            ->method('get')
            ->with('context_test')
            ->willReturn(new ContextClass());

        $this->assertEquals(
            'one_two_three',
            $this->contextRetriever->getContextValue($source)
        );
    }

    public function testUnsupportedContextReference()
    {
        $this->expectException(ContextRetrieverException::class);
        $this->expectExceptionMessage('Context \'unsupported_context\' is unsupported');

        $source = 'unsupported_context.get_value';

        $this->contextPoolMock->expects(self::once())
            ->method('has')
            ->with('unsupported_context')
            ->willReturn(false);
        $this->contextPoolMock->expects(self::never())
            ->method('get');

        $this->contextRetriever->getContextValue($source);
    }

    public function testFailedContextAccess()
    {
        $this->expectException(ContextRetrieverException::class);
        $this->expectExceptionMessage('Unable to access context for source \'context_failed.get_value\'');

        $this->contextPoolMock->expects(self::once())
            ->method('has')
            ->with('context_failed')
            ->willReturn(true);
        $this->contextPoolMock->expects(self::once())
            ->method('get')
            ->willThrowException(new Exception('Method does not exist'));

        $this->contextRetriever->getContextValue('context_failed.get_value');
    }

    /**
     * @return void
     */
    #[DataProvider('invalidMethodOrArgumentsProvider')]
    public function testInvalidMethodsOrArguments(string $source, string $contextReference)
    {
        $this->expectException(ContextRetrieverException::class);
        $this->expectExceptionMessage(sprintf('The context field source \'%s\' cannot be accessed.', $source));

        $this->contextPoolMock->expects(self::once())
            ->method('has')
            ->with($contextReference)
            ->willReturn(true);
        $this->contextPoolMock->expects(self::once())
            ->method('get')
            ->with($contextReference)
            ->willReturn(new ContextClass());

        $this->contextRetriever->getContextValue($source);
    }

    public function testObjectRetrievedFromContext()
    {
        $source = 'context_test.get_sample_class';

        $this->contextPoolMock->expects(self::once())
            ->method('has')
            ->with('context_test')
            ->willReturn(true);
        $this->contextPoolMock->expects(self::once())
            ->method('get')
            ->with('context_test')
            ->willReturn(new ContextClass());

        $this->assertInstanceOf(SampleClass::class, $this->contextRetriever->getContextValue($source));
    }

    /**
     * @return array
     */
    public static function invalidMethodOrArgumentsProvider(): array
    {
        return [
            'nonexistent method specified by source' => [
                'source' => 'context_valid.get_nonexistent_value',
                'contextReference' => 'context_valid'
            ],
            'invalid argument format' => [
                'source' => 'context_test.concatenate{one:two',
                'contextReference' => 'context_test'
            ],
            'invalid argument number' => [
                'source' => 'context_test.concatenate{one}',
                'contextReference' => 'context_test'
            ],
            'method being called on non-object context value' => [
                'source' => 'context_valid.get_sample_class.get_value.get_data',
                'contextReference' => 'context_valid'
            ]
        ];
    }
}
