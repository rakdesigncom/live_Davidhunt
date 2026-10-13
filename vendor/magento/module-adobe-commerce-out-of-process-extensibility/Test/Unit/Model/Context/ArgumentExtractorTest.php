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

use Magento\AdobeCommerceOutOfProcessExtensibility\Model\Context\ArgumentExtractor;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests for @see ArgumentExtractor
 */
class ArgumentExtractorTest extends TestCase
{
    /**
     * @var ArgumentExtractor
     */
    private ArgumentExtractor $argumentExtractor;

    protected function setUp(): void
    {
        $this->argumentExtractor = new ArgumentExtractor();
    }

    /**
     * @param string $sourcePart
     * @param array $arguments
     * @return void
     */
    #[DataProvider('processProvider')]
    public function testProcess(string $sourcePart, array $arguments): void
    {
        $this->assertEquals($arguments, $this->argumentExtractor->extract($sourcePart));
    }

    /**
     * @return array
     */
    public static function processProvider(): array
    {
        return [
            'no arguments' => [
                'sourcePart' => 'get_method',
                'arguments' => []
            ],
            'improperly formatted arguments' => [
                'sourcePart' => 'get_class_property{one:two',
                'arguments' => []
            ],
            'one argument' => [
                'sourcePart' => 'access_data{argument}',
                'arguments' => ['argument']
            ],
            'multiple arguments' => [
                'sourcePart' => 'get{test_arg1:test_arg2:test_arg3}',
                'arguments' => ['test_arg1', 'test_arg2', 'test_arg3']
            ]
        ];
    }
}
