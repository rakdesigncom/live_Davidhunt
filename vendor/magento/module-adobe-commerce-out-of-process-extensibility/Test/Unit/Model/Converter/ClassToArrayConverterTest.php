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

namespace Magento\AdobeCommerceOutOfProcessExtensibility\Test\Unit\Model\Converter;

use Magento\AdobeCommerceOutOfProcessExtensibility\Model\Converter\ClassToArrayConverter;
use Magento\AdobeCommerceOutOfProcessExtensibility\Model\Converter\ClassToArrayConverterInterface;
use Magento\AdobeCommerceOutOfProcessExtensibility\Model\Util\ReflectionHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionException;

/**
 * Tests for @see ClassToArrayConverter
 */
class ClassToArrayConverterTest extends TestCase
{
    /**
     * @var ReflectionHelper|MockObject
     */
    private ReflectionHelper|MockObject $reflectionHelperMock;

    /**
     * @var ClassToArrayConverter
     */
    private ClassToArrayConverter $converter;

    protected function setUp(): void
    {
        $this->reflectionHelperMock = $this->createMock(ReflectionHelper::class);
        $this->converter = new ClassToArrayConverter($this->reflectionHelperMock);
    }

    /**
     * Tests that simple-typed properties are returned as flat name => type pairs.
     */
    public function testConvertSimpleProperties(): void
    {
        $className = 'Foo\Bar';

        $this->reflectionHelperMock->method('getObjectProperties')
            ->with($className)
            ->willReturn([
                ['name' => 'id', 'type' => 'int'],
                ['name' => 'name', 'type' => 'string'],
                ['name' => 'active', 'type' => 'bool'],
            ]);
        $this->reflectionHelperMock->method('isSimple')->willReturn(true);

        $result = $this->converter->convert($className, ClassToArrayConverterInterface::NESTED_LEVEL);

        self::assertEquals(
            ['id' => 'int', 'name' => 'string', 'active' => 'bool'],
            $result
        );
    }

    /**
     * Tests that a property with a complex return type is recursively converted.
     */
    public function testConvertNestedObject(): void
    {
        $className = 'Foo\Order';
        $nestedClassName = 'Foo\Address';

        $this->reflectionHelperMock->method('getObjectProperties')
            ->willReturnMap([
                [$className, [
                    ['name' => 'id', 'type' => 'int'],
                    ['name' => 'billing_address', 'type' => $nestedClassName],
                ]],
                [$nestedClassName, [
                    ['name' => 'street', 'type' => 'string'],
                    ['name' => 'postcode', 'type' => 'string'],
                ]],
            ]);
        $this->reflectionHelperMock->method('isSimple')
            ->willReturnCallback(fn(string $type) => in_array($type, ['int', 'string']));
        $this->reflectionHelperMock->method('isArray')->willReturn(false);

        $result = $this->converter->convert($className, ClassToArrayConverterInterface::NESTED_LEVEL);

        self::assertEquals(
            [
                'id' => 'int',
                'billing_address' => [
                    'street' => 'string',
                    'postcode' => 'string',
                ],
            ],
            $result
        );
    }

    /**
     * Tests that an array-typed property is converted to a single-element list
     * containing the converted item structure.
     */
    public function testConvertArrayProperty(): void
    {
        $className = 'Foo\Invoice';
        $itemClassName = 'Foo\InvoiceItem';
        $itemArrayType = $itemClassName . '[]';

        $this->reflectionHelperMock->method('getObjectProperties')
            ->willReturnMap([
                [$className, [['name' => 'items', 'type' => $itemArrayType]]],
                [$itemClassName, [
                    ['name' => 'sku', 'type' => 'string'],
                    ['name' => 'qty', 'type' => 'float'],
                ]],
            ]);
        $this->reflectionHelperMock->method('isSimple')
            ->willReturnCallback(fn(string $type) => in_array($type, ['string', 'float']));
        $this->reflectionHelperMock->method('isArray')
            ->willReturnCallback(fn(?string $type) => $type === $itemArrayType);
        $this->reflectionHelperMock->method('arrayTypeToSingle')
            ->with($itemArrayType)
            ->willReturn($itemClassName);

        $result = $this->converter->convert($className, ClassToArrayConverterInterface::NESTED_LEVEL);

        self::assertEquals(
            [
                'items' => [
                    ['sku' => 'string', 'qty' => 'float'],
                ],
            ],
            $result
        );
    }

    /**
     * Tests that recursion stops when the nesting level limit is reached.
     * At the limit, complex types are stored as their type string instead of being expanded.
     */
    public function testConvertNestingLevelLimit(): void
    {
        $className = 'Foo\Bar';
        $nestedClassName = 'Foo\Baz';

        $this->reflectionHelperMock->method('getObjectProperties')
            ->with($className)
            ->willReturn([['name' => 'nested', 'type' => $nestedClassName]]);
        $this->reflectionHelperMock->method('isSimple')->willReturn(false);

        // nestedLevel=1, level starts at 1: condition level >= nestedLevel is immediately true
        $result = $this->converter->convert($className, nestedLevel: 1);

        self::assertEquals(['nested' => $nestedClassName], $result);
        // getObjectProperties must NOT be called recursively for the nested type
        $this->reflectionHelperMock->expects(self::never())
            ->method('getObjectProperties')
            ->with($nestedClassName);
    }

    /**
     * Tests that a ReflectionException from getObjectProperties returns an array containing the class name.
     */
    public function testConvertUnknownClassReturnsClassName(): void
    {
        $className = 'NonExistent\ClassName';

        $this->reflectionHelperMock->method('getObjectProperties')
            ->willThrowException(new ReflectionException('Class not found'));

        $result = $this->converter->convert($className, ClassToArrayConverterInterface::NESTED_LEVEL);

        self::assertEquals([$className], $result);
    }
}
