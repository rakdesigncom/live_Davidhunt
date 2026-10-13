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

namespace Magento\AdobeCommerceOutOfProcessExtensibility\Test\Unit\Model\Generator\Collector\Reflection;

use Magento\AdobeCommerceOutOfProcessExtensibility\Model\Generator\Collector\Reflection\MethodParametersCollector;
use Magento\AdobeCommerceOutOfProcessExtensibility\Model\Util\ReflectionHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use ReflectionParameter;
use ReflectionNamedType;

/**
 * Tests for MethodParametersCollector class.
 */
class MethodParametersCollectorTest extends TestCase
{
    /**
     * @var MethodParametersCollector
     */
    private MethodParametersCollector $parametersCollector;

    /**
     * @var ReflectionHelper|MockObject
     */
    private ReflectionHelper $reflectionHelperMock;

    public function setUp(): void
    {
        $this->reflectionHelperMock = $this->createMock(ReflectionHelper::class);
        $this->parametersCollector = new MethodParametersCollector($this->reflectionHelperMock);
    }

    /**
     * Tests collection of a method parameter without a default value.
     *
     * @return void
     */
    public function testCollectParamNoDefault(): void
    {
        $this->testParameterCollection(null, false);
    }

    /**
     * Tests collection of a method parameter with a default null value.
     *
     * @return void
     */
    public function testCollectDefaultNullParam(): void
    {
        $this->testParameterCollection('nullParam', true, null, 'null');
    }

    /**
     * Tests collection of a method parameter with a default string value.
     *
     * @return void
     */
    public function testCollectDefaultStringParam(): void
    {
        $this->testParameterCollection('stringParam', true, 'default', '\'default\'');
    }

    /**
     * Tests collection of a method parameter with a default array value.
     *
     * @return void
     */
    public function testCollectDefaultArrayParam(): void
    {
        $this->testParameterCollection('arrayParam', true, [1, 2, 3], '[1, 2, 3]');
    }

    /**
     * Tests collection of a method parameter with a default boolean value.
     *
     * @return void
     */
    public function testCollectDefaultBoolParam(): void
    {
        $this->testParameterCollection('boolParam', true, true, 'true');
    }

    /**
     * Tests collection of a method parameter with a default int value.
     *
     * @return void
     */
    public function testCollectDefaultIntParam(): void
    {
        $this->testParameterCollection('intParam', true, 1, '1');
    }

    /**
     * @param string|null $typeName
     * @param bool $defaultAvailable
     * @param $defaultParamValue
     * @param $collectedDefaultValue
     * @return void
     */
    private function testParameterCollection(
        ?string $typeName,
        bool $defaultAvailable,
        $defaultParamValue = null,
        $collectedDefaultValue = null
    ): void {
        $parameterName = 'paramName';

        $parameter = $this->createMock(ReflectionParameter::class);

        if ($typeName != null) {
            $parameterType = $this->createMock(ReflectionNamedType::class);
            $parameterType->expects(self::once())
                ->method('getName')
                ->willReturn($typeName);
        } else {
            $parameterType = $typeName;
        }
        $parameter->expects(self::exactly(1))
            ->method('getType')
            ->willReturn($parameterType);

        $parameter->expects(self::once())
            ->method('getName')
            ->willReturn($parameterName);
        $parameter->expects(self::once())
            ->method('isDefaultValueAvailable')
            ->willReturn($defaultAvailable);

        if ($defaultAvailable) {
            $parameter->expects(self::once())
                ->method('getDefaultValue')
                ->willReturn($defaultParamValue);
        } else {
            $parameter->expects(self::never())
                ->method('getDefaultValue');
        }

        $reflectionMethodMock = $this->createMock(ReflectionMethod::class);
        $reflectionClassMock = $this->createMock(ReflectionClass::class);
        $reflectionMethodMock->expects(self::once())
            ->method('getParameters')
            ->willReturn([$parameter]);

        $expectedResult = [
            [
                'type' => $typeName ?? 'string',
                'name' => $parameterName,
                'isDefaultValueAvailable' => $defaultAvailable,
            ]
        ];

        $reflectionMethodMock->expects(self::once())
            ->method('getDeclaringClass')
            ->willReturn($reflectionClassMock);
        $reflectionClassMock->expects(self::once())
            ->method('getName')
            ->willReturn('Laminas\Code\Reflection\ClassReflection');
        $reflectionMethodMock->expects(self::once())
            ->method('getName')->willReturn('getDocBlock');
        $this->reflectionHelperMock->expects(self::once())
            ->method('getMethodParameters')
            ->willReturn([[
                    'name' => $parameterName,
                    'type' => $typeName ?? 'string'
            ]]);

        if ($defaultAvailable) {
            $expectedResult[0]['defaultValue'] = $collectedDefaultValue;
        }

        $this->assertEquals(
            $expectedResult,
            $this->parametersCollector->collect($reflectionMethodMock)
        );
    }
}
