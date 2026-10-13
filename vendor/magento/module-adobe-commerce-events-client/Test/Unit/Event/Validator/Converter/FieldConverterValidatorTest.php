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

namespace Magento\AdobeCommerceEventsClient\Test\Unit\Event\Validator\Converter;

use Magento\AdobeCommerceEventsClient\Event\Event;
use Magento\AdobeCommerceEventsClient\Event\EventField;
use Magento\AdobeCommerceEventsClient\Event\Filter\FieldConverterInterface;
use Magento\AdobeCommerceEventsClient\Event\Filter\FieldFilter\FieldConverter;
use Magento\AdobeCommerceEventsClient\Event\Validator\Converter\FieldConverterValidator;
use Magento\AdobeCommerceEventsClient\Event\Validator\ValidatorException;
use Magento\Framework\ObjectManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionException;

class FieldConverterValidatorTest extends TestCase
{
    /**
     * @var ObjectManagerInterface|MockObject
     */
    private $objectManagerMock;

    /**
     * @var FieldConverterInterface|MockObject
     */
    private $sampleConverterClassOneMock;

    /**
     * @var FieldConverter|MockObject
     */
    private $sampleConverterClassTwoMock;

    /**
     * @var FieldConverterValidator
     */
    private FieldConverterValidator $validator;

    /**
     * @var Event|MockObject
     */
    private $eventMock;

    protected function setUp(): void
    {
        $this->eventMock = $this->createMock(Event::class);
        $this->objectManagerMock = $this->createMock(ObjectManagerInterface::class);
        $this->sampleConverterClassOneMock = $this->createMock(FieldConverterInterface::class);
        $this->sampleConverterClassTwoMock = $this->createMock(FieldConverter::class);
        $this->validator = new FieldConverterValidator($this->objectManagerMock);
    }

    public function testValidFieldConverter()
    {
        $fieldsData = [
            ['name' => 'key_one', 'converter' => 'testConverter1']
        ];

        $eventFields = $this->createFieldObjects($fieldsData);
        $this->eventMock->expects(self::once())
            ->method('getEventFields')
            ->willReturn($eventFields);
        $this->objectManagerMock->expects(self::once())
            ->method('get')
            ->with('testConverter1')
            ->willReturn($this->sampleConverterClassOneMock);

        $this->validator->validate($this->eventMock);
    }

    public function testValidateEmptyFields()
    {
        $this->eventMock->expects(self::once())
            ->method('getEventFields')
            ->willReturn([]);

        $this->validator->validate($this->eventMock);
    }

    public function testDuplicateFieldName()
    {
        $this->expectException(ValidatorException::class);
        $this->expectExceptionMessage(
            'Field "key_one" can not be provided twice in the event subscription'
        );

        $fieldsData = ["key_one", "key_two", "key_one"];

        $eventFields = $this->createFieldObjects($fieldsData);
        $this->eventMock->expects(self::once())
            ->method('getEventFields')
            ->willReturn($eventFields);

        $this->validator->validate($this->eventMock);
    }

    public function testConverterClassNotFoundException()
    {
        $this->expectException(ValidatorException::class);
        $this->expectExceptionMessage(
            'Can\'t create a converter class "testConverter2" for field "key_two". Error: Class \'testConverter2\''.
             ' does not exist'
        );

        $fieldsData = [
            ['name' => 'key_one', 'converter' => 'testConverter1'],
            ['name' => 'key_two', 'converter' => 'testConverter2']
        ];

        $eventFields = $this->createFieldObjects($fieldsData);
        $this->eventMock->expects(self::once())
            ->method('getEventFields')
            ->willReturn($eventFields);
        $this->objectManagerMock->expects(self::exactly(2))
            ->method('get')
            ->willReturnCallback(function () {
                static $count = 0;
                if (++$count === 1) {
                    return $this->sampleConverterClassOneMock;
                }
                throw new ReflectionException('Class \'testConverter2\' does not exist');
            });

        $this->validator->validate($this->eventMock);
    }

    public function testConverterInterfaceNotFoundException()
    {
        $this->expectException(ValidatorException::class);
        $this->expectExceptionMessage(
            'Converter class "testConverter2" for field "key_two" does not implement FieldConverterInterface'
        );

        $fieldsData = [
            ['name'=>'key_one','converter'=>'testConverter1'],
            ['name'=>'key_two','converter'=>'testConverter2']
        ];

        $eventFields = $this->createFieldObjects($fieldsData);
        $this->eventMock->expects(self::once())
            ->method('getEventFields')
            ->willReturn($eventFields);
        $this->objectManagerMock->expects(self::exactly(2))
            ->method('get')
            ->willReturnOnConsecutiveCalls($this->sampleConverterClassOneMock, $this->sampleConverterClassTwoMock);

        $this->validator->validate($this->eventMock);
    }

    /**
     * Creates eventField object for each subscribed field
     *
     * @param array $fieldsData
     * @return array
     */
    private function createFieldObjects(array $fieldsData) : array
    {
        $eventFields = [];
        foreach ($fieldsData as $fieldData) {
            if (is_string($fieldData)) {
                $fieldData = [EventField::NAME => $fieldData];
            }
            $eventFields[] = new EventField($fieldData);
        }
        return $eventFields;
    }
}
