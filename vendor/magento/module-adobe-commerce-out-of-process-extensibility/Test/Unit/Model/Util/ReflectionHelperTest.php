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

namespace Magento\AdobeCommerceOutOfProcessExtensibility\Test\Unit\Model\Util;

use Laminas\Code\Reflection\DocBlock\Tag\ParamTag;
use Laminas\Code\Reflection\DocBlockReflection;
use Laminas\Code\Reflection\MethodReflection;
use Magento\AdobeCommerceOutOfProcessExtensibility\Model\Util\ReflectionHelper;
use Magento\Framework\Reflection\FieldNamer;
use Magento\Framework\Reflection\TypeProcessor;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the @see ReflectionHelper Class
 */
class ReflectionHelperTest extends TestCase
{
    /**
     * @var TypeProcessor|MockObject
     */
    private $typeProcessorMock;

    /**
     * @var MethodReflection |MockObject
     */
    private $methodReflectionMock;

    /**
     * @var ReflectionHelper
     */
    private ReflectionHelper $reflectionHelper;

    public function setUp(): void
    {
        $this->typeProcessorMock = $this->createMock(TypeProcessor::class);
        $this->methodReflectionMock = $this->createMock(MethodReflection::class);
        $this->reflectionHelper = new ReflectionHelper(new FieldNamer(), $this->typeProcessorMock);
    }

    /**
     * Tests collection of properties from
     * 'Magento\AdobeCommerceOutOfProcessExtensibility\Test\Unit\Model\Util\_files\SampleClass'.
     *
     * @return void
     * @throws \ReflectionException
     */
    public function testGetDataRelatedObjectPropertiesFromClass(): void
    {
        $this->typeProcessorMock->expects(self::exactly(3))
            ->method('getGetterReturnType')
            ->willReturnCallback(function (MethodReflection $method) {
                static $count = 0;
                switch ($count++) {
                    case 0:
                        $this->assertEquals('isAvailable', $method->getName());
                        return ['type' => 'bool'];
                    case 1:
                        $this->assertEquals('getItemName', $method->getName());
                        return ['type' => 'string'];
                    case 2:
                        $this->assertEquals('getPrice', $method->getName());
                        return ['type' => 'float'];
                }
            });

        $objectProperties = $this->reflectionHelper->getObjectProperties($this->fixture('SampleClass'));

        $this->assertEquals(
            [
                [
                    'type' => 'bool',
                    'name' => 'available'
                ],
                [
                    'type' => 'string',
                    'name' => 'item_name'
                ],
                [
                    'type' => 'float',
                    'name' => 'price'
                ]
            ],
            $objectProperties
        );
    }

    /**
     * Tests collection of properties from
     * 'Magento\AdobeCommerceOutOfProcessExtensibility\Test\Unit\Model\Util\_files\SampleInterface'.
     *
     * @return void
     * @throws \ReflectionException
     */
    public function testGetDataRelatedObjectPropertiesFromInterface(): void
    {
        $this->typeProcessorMock->expects(self::exactly(4))
            ->method('getGetterReturnType')
            ->willReturnCallback(function (MethodReflection $method) {
                static $count = 0;
                switch ($count++) {
                    case 0:
                        $this->assertEquals('getId', $method->getName());
                        return ['type' => 'int'];
                    case 1:
                        $this->assertEquals('getAttributeSetId', $method->getName());
                        return ['type' => 'int'];
                    case 2:
                        $this->assertEquals('isAvailable', $method->getName());
                        return ['type' => 'bool'];
                    case 3:
                        $this->assertEquals('getQty', $method->getName());
                        return ['type' => 'float'];
                }
            });

        $objectProperties = $this->reflectionHelper->getObjectProperties($this->fixture('SampleInterface'));

        $this->assertEquals(
            [
                [
                    'type' => 'int',
                    'name' => 'id'
                ],
                [
                    'type' => 'int',
                    'name' => 'attribute_set_id'
                ],
                [
                    'type' => 'bool',
                    'name' => 'available'
                ],
                [
                    'type' => 'float',
                    'name' => 'qty'
                ]
            ],
            $objectProperties
        );
    }

    /**
     * Tests that when modelInterfaceMap maps a model class to an interface, model-defined properties take precedence
     * over the interface's typed equivalents, and interface-only fields (EAV attributes) are appended.
     *
     * SampleModelClass defines getRegion(): string and getRegionId(): int directly on the model.
     * SampleModelInterface defines getRegion(): SampleNestedObject (conflicting) plus getFirstname() and
     * getLastname().
     *
     * Expected result: region=string (model wins), region_id=int (model only), firstname=string, lastname=string
     * (interface adds). SampleNestedObject is never surfaced.
     *
     * @return void
     * @throws \ReflectionException
     */
    public function testModelInterfaceMapMergesModelTypesWithInterfaceEavFields(): void
    {
        $this->typeProcessorMock->expects(self::exactly(5))
            ->method('getGetterReturnType')
            ->willReturnCallback(function (MethodReflection $method) {
                static $count = 0;
                switch ($count++) {
                    case 0:
                        $this->assertEquals('getRegion', $method->getName());
                        return ['type' => 'string'];
                    case 1:
                        $this->assertEquals('getRegionId', $method->getName());
                        return ['type' => 'int'];
                    case 2:
                        $this->assertEquals('getRegion', $method->getName());
                        return ['type' => $this->fixture('SampleNestedObject')];
                    case 3:
                        $this->assertEquals('getFirstname', $method->getName());
                        return ['type' => 'string'];
                    case 4:
                        $this->assertEquals('getLastname', $method->getName());
                        return ['type' => 'string'];
                }
            });

        $reflectionHelper = new ReflectionHelper(new FieldNamer(), $this->typeProcessorMock, [
            $this->fixture('SampleModelClass') => $this->fixture('SampleModelInterface'),
        ]);

        $objectProperties = $reflectionHelper->getObjectProperties($this->fixture('SampleModelClass'));

        $this->assertEquals(
            [
                ['type' => 'string', 'name' => 'region'],
                ['type' => 'int', 'name' => 'region_id'],
                ['type' => 'string', 'name' => 'firstname'],
                ['type' => 'string', 'name' => 'lastname'],
            ],
            $objectProperties
        );
    }

    /**
     * Test return type of method parameters.
     *
     * @param string|null $variableName
     * @param array $types
     * @param string $expectedType
     * @return void
     */
    #[DataProvider('parameterDataProvider')]
    public function testGetMethodParameters(?string $variableName, array $types, string $expectedType): void
    {
        $paramTagMock = $this->createMock(ParamTag::class);
        $docBlockMock = $this->createMock(DocBlockReflection::class);

        $paramTagMock->method('getTypes')->willReturn($types);
        $paramTagMock->method('getVariableName')->willReturn($variableName);
        $this->methodReflectionMock->method('getDocBlock')->willReturn($docBlockMock);
        $docBlockMock->method('getTags')->willReturn([$paramTagMock]);
        $parameters = $this->reflectionHelper->getMethodParameters($this->methodReflectionMock);

        $expectedArray = !empty($variableName) ? [
            'name' => ltrim($variableName, '$'),
            'type' => $expectedType,
        ] : [];

        $this->assertEquals(
            $expectedArray ? [$expectedArray] : [],
            $parameters
        );
    }

    /**
     * Data provider for testGetMethodParameters
     *
     * @return array[]
     */
    public static function parameterDataProvider(): array
    {
        return [
            ['$param1', ['string', 'int'], 'mixed'],
            ['$param2', ['string', 'null'], 'string'],
            ['$param3', ['string', 'int', 'null'], 'mixed'],
            ['$param4', ['null'], 'null'],
            [ null, ['string'], 'string'],
            [ '', ['string'], 'string']
        ];
    }

    /**
     * Returns path to the fixture.
     *
     * @param string $name
     * @return string
     */
    private function fixture(string $name): string
    {
        return 'Magento\AdobeCommerceOutOfProcessExtensibility\Test\Unit\Model\Util\_files\\' . $name;
    }
}
