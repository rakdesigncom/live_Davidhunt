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

namespace Magento\AdobeCommerceOutOfProcessExtensibility\Model\Util;

use Laminas\Code\Reflection\ClassReflection;
use Laminas\Code\Reflection\DocBlock\Tag\ParamTag;
use Laminas\Code\Reflection\MethodReflection;
use Magento\Framework\DataObject;
use Magento\Framework\Reflection\FieldNamer;
use Magento\Framework\Reflection\TypeProcessor;
use ReflectionException;
use Throwable;

class ReflectionHelper
{
    public const TYPE_VOID = 'void';

    /**
     * @var string[]
     */
    private array $simpleTypes = [
        'bool',
        'boolean',
        'int',
        'integer',
        'string',
        'object',
        'float',
        'array',
        'mixed',
        'null',
        'void',
    ];

    /**
     * @var FieldNamer
     */
    private FieldNamer $fieldNamer;

    /**
     * @var TypeProcessor
     */
    private TypeProcessor $typeProcessor;

    /**
     * Maps model class names to their corresponding data interface.
     *
     * @var string[]
     */
    private array $modelInterfaceMap;

    /**
     * @param FieldNamer $fieldNamer
     * @param TypeProcessor $typeProcessor
     * @param string[] $modelInterfaceMap
     */
    public function __construct(
        FieldNamer $fieldNamer,
        TypeProcessor $typeProcessor,
        array $modelInterfaceMap = []
    ) {
        $this->fieldNamer = $fieldNamer;
        $this->typeProcessor = $typeProcessor;
        $this->modelInterfaceMap = $modelInterfaceMap;
    }

    /**
     * Gets method return type.
     *
     * @param MethodReflection $methodReflection
     * @param ClassReflection $classReflection
     * @return string|null
     */
    public function getReturnType(MethodReflection $methodReflection, ClassReflection $classReflection): ?string
    {
        try {
            $returnType = $this->typeProcessor->getGetterReturnType($methodReflection)['type'];
        } catch (Throwable $e) {
            return 'mixed';
        }

        if ($returnType === null) {
            return 'null';
        }

        if ($this->isSimple($returnType)) {
            return $returnType;
        }

        if (in_array($returnType, ['$this', 'this', 'self'])) {
            $returnType = $classReflection->getName();
        }

        return $this->typeProcessor->resolveFullyQualifiedClassName($classReflection, $returnType);
    }

    /**
     * Converts array type to single type by removing `[]` part
     *
     * @param string $type
     * @return string
     */
    public function arrayTypeToSingle(string $type): string
    {
        return str_replace('[]', '', $type);
    }

    /**
     * Retrieves objects properties by a class name in [type, name] format based on get*, is*, and has* methods.
     *
     * For classes extending Magento\Framework\DataObject, returns object properties based only on the get*, is*, or
     * has* methods that access _data.
     *
     * When a modelInterfaceMap entry exists for $fqcn, the model's own properties are collected first (reflecting the
     * actual toArray() / _data structure). The interface then supplements with additional fields not present on the
     * model. Model-defined fields always take precedence so that their simpler return types
     * (e.g. string for "region") are not overridden by the interface's typed equivalents (e.g. RegionInterface).
     *
     * @param string $fqcn
     * @return array
     * @throws ReflectionException
     */
    public function getObjectProperties(string $fqcn): array
    {
        if (isset($this->modelInterfaceMap[$fqcn])) {
            $modelProperties = $this->collectProperties($fqcn);
            $interfaceProperties = $this->getObjectProperties($this->modelInterfaceMap[$fqcn]);
            $modelFieldNames = array_flip(array_column($modelProperties, 'name'));
            foreach ($interfaceProperties as $prop) {
                if (!isset($modelFieldNames[$prop['name']])) {
                    $modelProperties[] = $prop;
                }
            }
            return $modelProperties;
        }

        return $this->collectProperties($fqcn);
    }

    /**
     * Collects properties for a single class or interface without applying modelInterfaceMap.
     *
     * @param string $fqcn
     * @return array
     * @throws ReflectionException
     */
    private function collectProperties(string $fqcn): array
    {
        $result = [];

        $refClass = new ClassReflection($fqcn);
        $methodList = $refClass->getMethods(\ReflectionMethod::IS_PUBLIC);
        foreach ($methodList as $method) {
            if (!$refClass->isInterface() && $method->class != $refClass->getName()) {
                continue;
            }

            if ($method->getNumberOfParameters() != 0) {
                continue;
            }

            $propName = $this->fieldNamer->getFieldNameForMethodName($method->getName());
            if ($propName === null) {
                continue;
            }

            if (is_a($fqcn, DataObject::class, true) && !$this->hasDataAccess($method)) {
                continue;
            }

            $result[] = [
                'type' => str_replace('|null', '', $this->getReturnType($method, $refClass)),
                'name' => $propName
            ];
        }

        return $result;
    }

    /**
     * Checks if input method's body accesses the _data variable directly or through the getData or _getData methods
     *
     * @param MethodReflection $method
     * @return bool
     */
    private function hasDataAccess(MethodReflection $method): bool
    {
        $body = $method->getBody();
        return strpos($body, '$this->_data') !== false ||
            strpos($body, '$this->getData(') !== false ||
            strpos($body, '$this->_getData(') !== false;
    }

    /**
     * Checks if a type is an array.
     *
     * @param string|null $input
     * @return bool
     */
    public function isArray(?string $input): bool
    {
        if ($input == null) {
            return false;
        }

        return $input === 'array' || strpos($input, '[]') !== false;
    }

    /**
     * Checks if input string is a simple type.
     *
     * @param string $input
     * @return bool
     */
    public function isSimple(string $input): bool
    {
        return in_array($this->arrayTypeToSingle($input), $this->simpleTypes);
    }

    /**
     * Returns list of method parameters.
     *
     * @param MethodReflection $methodReflection
     * @return array
     */
    public function getMethodParameters(MethodReflection $methodReflection): array
    {
        $params = [];

        foreach ($methodReflection->getDocBlock()->getTags('param') as $paramTag) {
            if ($paramTag instanceof ParamTag) {
                $types = $paramTag->getTypes();
                $variableName = $paramTag->getVariableName();
                if (!empty($types) && !empty($variableName)) {
                    $params[] = [
                        'name' => ltrim($variableName, '$'),
                        'type' => $this->getParameterType($types),
                    ];
                }
            }
        }

        return $params;
    }

    /**
     * Returns a single type name given the input list of types.
     *
     * @param array $types
     * @return string
     */
    private function getParameterType(array $types): string
    {
        $nonNullTypes = array_values(array_diff($types, ['null']));
        return (count($nonNullTypes) > 1) ? 'mixed' : $nonNullTypes[0] ?? 'null';
    }
}
