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

namespace Magento\AdobeCommerceOutOfProcessExtensibility\Model\Generator\Collector\Reflection;

use Laminas\Code\Reflection\MethodReflection;
use Magento\AdobeCommerceOutOfProcessExtensibility\Model\Util\ReflectionHelper;
use ReflectionMethod;
use ReflectionException;

/**
 * Collects a list of parameters for the given method
 */
class MethodParametersCollector
{
    /**
     * @param ReflectionHelper $reflectionHelper
     */
    public function __construct(private ReflectionHelper $reflectionHelper)
    {
    }

    /**
     * Collects a list of parameters with their type and defaults for the given method
     *
     * @param ReflectionMethod $reflectionMethod
     * @return array
     */
    public function collect(ReflectionMethod $reflectionMethod): array
    {
        $params = [];
        $methodParameterTypes = $this->retrieveParamsType($reflectionMethod);

        foreach ($reflectionMethod->getParameters() as $param) {
            $parameterName = $param->getName();
            $methodParams = [
                'type' => $param->getType()?->getName(),
                'name' => $parameterName,
                'isDefaultValueAvailable' => false,
            ];

            $methodParams['type'] = $methodParams['type'] ?? ($methodParameterTypes[$parameterName] ?? null);

            if ($param->isDefaultValueAvailable()) {
                $methodParams['isDefaultValueAvailable'] = true;
                $methodParams['defaultValue'] = $this->formatDefaultValue($param->getDefaultValue());
            }

            $params[] = $methodParams;
        }

        return $params;
    }

    /**
     * Convert default value to appropriate string format
     *
     * @param mixed $defaultValue
     * @return string
     */
    private function formatDefaultValue($defaultValue): string
    {
        if (is_string($defaultValue)) {
            return '\'' . $defaultValue . '\'';
        }

        if (is_array($defaultValue)) {
            return '[' . implode(', ', $defaultValue) . ']';
        }

        if ($defaultValue === null) {
            return 'null';
        }

        if (is_bool($defaultValue)) {
            return $defaultValue ? 'true' : 'false';
        }

        return (string)$defaultValue;
    }

    /**
     * Retrieves the parameter types of a given method.
     *
     * @param ReflectionMethod $reflectionMethod
     * @return array
     */
    private function retrieveParamsType(ReflectionMethod $reflectionMethod): array
    {
        try {
            $methodReflection = new MethodReflection(
                $reflectionMethod->getDeclaringClass()->getName(),
                $reflectionMethod->getName()
            );
        } catch (ReflectionException $e) {
            return [];
        }

        $methodParameters = $this->reflectionHelper->getMethodParameters($methodReflection);
        return array_combine(
            array_column($methodParameters, 'name'),
            array_column($methodParameters, 'type')
        );
    }
}
