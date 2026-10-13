<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Test\Unit\Event\Filter;

use Magento\AdobeCommerceEventsClient\Event\Converter\EventFieldConverter;
use Magento\AdobeCommerceEventsClient\Event\Context\ContextRetriever;
use Magento\AdobeCommerceEventsClient\Event\Event;
use Magento\AdobeCommerceEventsClient\Event\EventField;
use Magento\AdobeCommerceEventsClient\Event\EventInitializationException;
use Magento\AdobeCommerceEventsClient\Event\EventList;
use Magento\AdobeCommerceEventsClient\Event\Filter\EventFieldsFilter;
use Magento\AdobeCommerceEventsClient\Event\Filter\FieldFilter\FieldConverter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Tests for @see EventFieldsFilter class
 */
class EventFieldsFilterTest extends TestCase
{
    /**
     * @var EventFieldsFilter
     */
    private EventFieldsFilter $filter;

    /**
     * @var EventList|MockObject
     */
    private $eventListMock;

    /**
     * @var Event|MockObject
     */
    private $eventMock;

    /**
     * @var EventFieldConverter|MockObject
     */
    private $fieldProcessConverterMock;

    /**
     * @var ContextRetriever|MockObject
     */
    private ContextRetriever|MockObject $contextRetrieverMock;

    protected function setUp(): void
    {
        $this->eventListMock = $this->createMock(EventList::class);
        $this->eventMock = $this->createMock(Event::class);
        $this->fieldProcessConverterMock = $this->createMock(EventFieldConverter::class);
        $this->contextRetrieverMock = $this->createMock(ContextRetriever::class);
        $this->filter = new EventFieldsFilter(
            $this->eventListMock,
            new FieldConverter(),
            $this->fieldProcessConverterMock,
            $this->contextRetrieverMock
        );
    }

    /**
     * @param array $eventData
     * @param array $fields
     * @param array $expectedData
     * @return void
     * @throws EventInitializationException
     */
    #[DataProvider('dataFilteredDataProvider')]
    public function testDataFiltered(array $eventData, array $fields, array $expectedData): void
    {
        $eventFields = $this->createFieldObjects($fields);
        $this->eventListMock->expects(self::once())
            ->method('get')
            ->with('some.event')
            ->willReturn($this->eventMock);
        $this->eventMock->expects(self::exactly(2))
            ->method('getFields')
            ->willReturn($fields);
        $this->eventMock->expects(self::once())
            ->method('getEventFields')
            ->willReturn($eventFields);
        $this->contextRetrieverMock->expects(self::never())
            ->method('getContextValue');

        self::assertEquals(
            $expectedData,
            $this->filter->filter('some.event', $eventData)
        );
    }

    /**
     * @return array[]
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public static function dataFilteredDataProvider(): array
    {
        return [
            'simple fields' => [
                'eventData' => [
                    'key1' => 'value1',
                    'key2' => 'value2',
                    'key3' => [
                        'key3_1' => 'value3_1',
                        'key3_2' => 'value3_2',
                    ],
                    'key4' => 'value4'
                ],
                'fields' => [
                    'key1',
                    'key3',
                    'key5'
                ],
                'expectedData' => [
                    'key1' => 'value1',
                    'key3' => [
                        'key3_1' => 'value3_1',
                        'key3_2' => 'value3_2',
                    ],
                    'key5' => null
                ]
            ],
            'simple nested fields' => [
                'eventData' => [
                    'key1' => 'value1',
                    'key2' => 'value2',
                    'key3' => [
                        'key3_1' => 'value3_1',
                        'key3_2' => [
                            'key3_2_1' => 'value3_2_1',
                            'key3_2_2' => [
                                'key3_2_2_1' => 'value3_2_2_1',
                                'key3_2_2_2' => 'value3_2_2_2',
                            ]
                        ]
                    ],
                    'key4' => [
                        'key4_1' => 'value4_1'
                    ]
                ],
                'fields' => [
                    'key1',
                    'key3.key3_2.key3_2_2.key3_2_2_2',
                    'key3.key3_2.key3_2_2.key_not_exists',
                    'key3.key3_1',
                    'key_not_exists.key_not_exists.key_not_exists.key_not_exists.key_not_exists.key_not_exists'
                ],
                'expectedData' => [
                    'key1' => 'value1',
                    'key3' => [
                        'key3_1' => 'value3_1',
                        'key3_2' => [
                            'key3_2_2' => [
                                'key3_2_2_2' => 'value3_2_2_2',
                                'key_not_exists' => null,
                            ]
                        ]
                    ],
                    'key_not_exists' => [
                        'key_not_exists' => [
                            'key_not_exists' => [
                                'key_not_exists' => [
                                    'key_not_exists' => [
                                        'key_not_exists' => null
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
            ],
            'array fields' => [
                'eventData' => [
                    'entity_id' => 'value1',
                    'items' => [
                        'items_1' => [
                            'sku' => 'sku1',
                            'qty' => '10',
                            'entity_id' => '1',
                        ],
                        'items_2' => [
                            'sku' => 'sku2',
                            'qty' => '20',
                            'entity_id' => '2',
                        ],
                    ],
                ],
                'fields' => [
                    'entity_id',
                    'items[].sku',
                    'items[].qty',
                    'items[].not_exists',
                    'not_exists[]'
                ],
                'expectedData' => [
                    'entity_id' => 'value1',
                    'items' => [
                        [
                            'sku' => 'sku1',
                            'qty' => '10',
                            'not_exists' => null,
                        ],
                        [
                            'sku' => 'sku2',
                            'qty' => '20',
                            'not_exists' => null,
                        ],
                    ],
                    'not_exists' => null
                ],
            ],
            'array fields items remove indexes' => [
                'eventData' => [
                    'entity_id' => 'value1',
                    'items' => [
                        'items_1' => [
                            'sku' => 'sku1',
                            'qty' => '10',
                            'entity_id' => '1',
                        ],
                        'items_2' => [
                            'sku' => 'sku2',
                            'qty' => '20',
                            'entity_id' => '2',
                        ],
                        35 => [
                            'sku' => 'sku3',
                            'qty' => '1',
                        ]
                    ],
                ],
                'fields' => [
                    'entity_id',
                    'items[]'
                ],
                'expectedData' => [
                    'entity_id' => 'value1',
                    'items' => [
                        [
                            'sku' => 'sku1',
                            'qty' => '10',
                            'entity_id' => '1',
                        ],
                        [
                            'sku' => 'sku2',
                            'qty' => '20',
                            'entity_id' => '2',
                        ],
                        [
                            'sku' => 'sku3',
                            'qty' => '1'
                        ]
                    ],
                ],
            ],
            'array fields items remove indexes in nested array structure with child array element' => [
                'eventData' => [
                    'entity_id' => 'value1',
                    'data' => [
                        'data_nested' => [
                            'items' => [
                                'items_1' => [
                                    'sku' => 'sku1',
                                    'qty' => '10',
                                    'entity_id' => '1',
                                ],
                                'items_2' => [
                                    'sku' => 'sku2',
                                    'qty' => '20',
                                    'entity_id' => '2',
                                ],
                                35 => [
                                    'sku' => 'sku3',
                                    'qty' => '1',
                                ]
                            ],
                        ],
                    ],
                ],
                'fields' => [
                    'entity_id',
                    'data.data_nested.items[]',
                ],
                'expectedData' => [
                    'entity_id' => 'value1',
                    'data' => [
                        'data_nested' => [
                            'items' => [
                                [
                                    'sku' => 'sku1',
                                    'qty' => '10',
                                    'entity_id' => '1',
                                ],
                                [
                                    'sku' => 'sku2',
                                    'qty' => '20',
                                    'entity_id' => '2',
                                ],
                                [
                                    'sku' => 'sku3',
                                    'qty' => '1',
                                ]
                            ],
                        ],
                    ],
                ],
            ],
            'array nested fields' => [
                'eventData' => [
                    'entity_id' => 'value1',
                    'items' => [
                        'items_1' => [
                            'product' => [
                                'name' => 'test',
                                'price' => 100
                            ],
                            'qty' => 11,
                            'entity_id' => '1',
                        ],
                        'items_2' => [
                            'product' => [
                                'name' => 'test2',
                                'price' => 200
                            ],
                            'qty' => 22,
                            'entity_id' => '2',
                        ],
                    ],
                ],
                'fields' => [
                    'entity_id',
                    'items[].product.name',
                    'items[].qty',
                ],
                'expectedData' => [
                    'entity_id' => 'value1',
                    'items' => [
                        [
                            'product' => [
                                'name' => 'test'
                            ],
                            'qty' => 11,
                        ],
                        [
                            'product' => [
                                'name' => 'test2'
                            ],
                            'qty' => 22,
                        ],
                    ],
                ],
            ],
            'array nested fields not exists' => [
                'eventData' => [
                    'entity_id' => 'value1',
                ],
                'fields' => [
                    'entity_id',
                    'items[].product.name',
                    'items[].qty',
                    'items2[].id',
                ],
                'expectedData' => [
                    'entity_id' => 'value1',
                    'items' => [],
                    'items2' => [],
                ],
            ],
            'nested fields with array fields' => [
                'eventData' => [
                    'entity_id' => 'value1',
                    'products' => [
                        'items' => [
                            'items_1' => [
                                'product' => [
                                    'name' => 'test',
                                    'price' => 100
                                ],
                                'qty' => 11,
                                'entity_id' => '1',
                            ],
                            'items_2' => [
                                'product' => [
                                    'name' => 'test2',
                                    'price' => 200
                                ],
                                'qty' => 22,
                                'entity_id' => '2',
                            ],
                        ],
                    ],
                ],
                'fields' => [
                    'entity_id',
                    'products.items[].product.name',
                    'products.items[].qty',
                ],
                'expectedData' => [
                    'entity_id' => 'value1',
                    'products' => [
                        'items' => [
                            [
                                'product' => [
                                    'name' => 'test'
                                ],
                                'qty' => 11,
                            ],
                            [
                                'product' => [
                                    'name' => 'test2'
                                ],
                                'qty' => 22,
                            ],
                        ],
                    ],
                ],
            ],
            'nested fields with array fields not exists' => [
                'eventData' => [
                    'entity_id' => 'value1',
                    'products' => [
                        'items' => [],
                    ],
                ],
                'fields' => [
                    'entity_id',
                    'products.items[].product.name',
                    'products.items[].qty',
                ],
                'expectedData' => [
                    'entity_id' => 'value1',
                    'products' => [
                        'items' => [],
                    ],
                ],
            ],
            'nested array fields with not array event data fields' => [
                'eventData' => [
                    'entity_id' => 'value1',
                    'products' => [
                        'items' => [
                            'items_1' => 'item_1',
                            'items_2' => 'item_2',
                        ],
                    ],
                ],
                'fields' => [
                    'entity_id',
                    'products.items[].product.name',
                    'products.items[].qty',
                ],
                'expectedData' => [
                    'entity_id' => 'value1',
                    'products' => [
                        'items' => [
                            [
                                'product' => [
                                    'name' => null
                                ],
                                'qty' => null,
                            ],
                            [
                                'product' => [
                                    'name' => null
                                ],
                                'qty' => null,
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @return void
     * @throws EventInitializationException
     */
    public function testDataNotFilteredIfEventNotExists(): void
    {
        $eventData = ['key' => 'value'];

        $this->eventListMock->expects(self::once())
            ->method('get')
            ->with('some.event')
            ->willReturn(null);
        $this->contextRetrieverMock->expects(self::never())
            ->method('getContextValue');

        self::assertEquals(
            $eventData,
            $this->filter->filter('some.event', $eventData)
        );
    }

    /**
     * @return void
     * @throws EventInitializationException
     */
    public function testDataNotFilteredWithWildcard(): void
    {
        $eventData = [
            'name' => 'test',
            'price' => 100,
            EventFieldsFilter::FIELD_ORIGINAL_DATA => [
                'name' => 'original_test',
                'price' => 90
            ]
        ];

        $this->eventListMock->expects(self::once())
            ->method('get')
            ->with('some.event')
            ->willReturn($this->eventMock);
        $this->eventMock->expects(self::exactly(3))
            ->method('getFields')
            ->willReturn(['name', EventFieldsFilter::FIELD_WILDCARD]);
        $this->eventMock->expects(self::never())
            ->method('getEventFields');

        self::assertEquals(
            [
                'name' => 'test',
                'price' => 100,
            ],
            $this->filter->filter('some.event', $eventData)
        );
    }

    public function testDataNotFilteredWithWildcardAndOrigData(): void
    {
        $eventData = [
            'name' => 'test',
            'price' => 100,
            EventFieldsFilter::FIELD_ORIGINAL_DATA => [
                'name' => 'original_test',
                'price' => 90
            ]
        ];

        $this->eventListMock->expects(self::once())
            ->method('get')
            ->with('some.event')
            ->willReturn($this->eventMock);
        $this->eventMock->expects(self::exactly(3))
            ->method('getFields')
            ->willReturn([
                EventFieldsFilter::FIELD_ORIGINAL_DATA,
                EventFieldsFilter::FIELD_WILDCARD
            ]);
        $this->eventMock->expects(self::never())
            ->method('getEventFields');
        $this->contextRetrieverMock->expects(self::never())
            ->method('getContextValue');

        self::assertEquals(
            $eventData,
            $this->filter->filter('some.event', $eventData)
        );
    }

    /**
     * @param array $eventData
     * @param array $fields
     * @param array $expectedData
     * @return void
     */
    #[DataProvider('dataConverterDataProvider')]
    public function testConverterField(
        array $eventData,
        array $fields,
        array $converterOutput,
        array $expectedData
    ): void {
        $eventFields = $this->createFieldObjects($fields);
        $this->eventListMock->expects(self::once())
            ->method('get')
            ->with('some.event')
            ->willReturn($this->eventMock);
        $this->eventMock->expects(self::exactly(2))
            ->method('getFields')
            ->willReturn($fields);
        $this->eventMock->expects(self::once())
            ->method('getEventFields')
            ->willReturn($eventFields);
        $this->fieldProcessConverterMock->expects(self::exactly(count($converterOutput)))
            ->method('convertField')
            ->willReturnOnConsecutiveCalls(...$converterOutput);

        self::assertEquals(
            $expectedData,
            $this->filter->filter('some.event', $eventData)
        );
    }

    public static function dataConverterDataProvider(): array
    {
        return [
            'simple fields with converter' => [
                'eventData' => [
                    'key1' => 'value1',
                    'key2' => 'value2',
                    'key3' => [
                        'key3_1' => 'value3_1',
                        'key3_2' => 'value3_2',
                    ],
                    'key4' => 'value4'
                ],
                'fields' => [
                    ['name' => 'key1', 'converter' => 'TestConverterName'],
                    'key3',
                    'key5'
                ],
                'converterOutput' => ['Test Case 1'],
                'expectedData' => [
                    'key1' => 'Test Case 1',
                    'key3' => [
                        'key3_1' => 'value3_1',
                        'key3_2' => 'value3_2',
                    ],
                    'key5' => null
                ]
            ],
            'array fields with converters' => [
                'eventData' => [
                    'entity_id' => 'value1',
                    'items' => [
                        'items_1' => [
                            'sku' => 'sku1',
                            'qty' => '10',
                            'entity_id' => '1',
                        ],
                        'items_2' => [
                            'sku' => 'sku2',
                            'qty' => '20',
                            'entity_id' => '2',
                        ],
                    ],
                ],
                'fields' => [
                    'entity_id',
                    ['name' => 'items[].sku', 'converter' => 'TestConverterSku'],
                    ['name' => 'items[].qty', 'converter' => 'TestConverterQty'],
                    'items[].not_exists',
                    'not_exists[]'
                ],
                'converterOutput' => ['Test Sku1', '10', 'Test Sku2', '20'],
                'expectedData' => [
                    'entity_id' => 'value1',
                    'items' => [
                        [
                            'sku' => 'Test Sku1',
                            'qty' => '10',
                            'not_exists' => null,
                        ],
                        [
                            'sku' => 'Test Sku2',
                            'qty' => '20',
                            'not_exists' => null,
                        ],
                    ],
                    'not_exists' => null
                ],
            ]
        ];
    }

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

    public function testConvertFieldsWithContextValues()
    {
        $fields = [
            ['name' => 'customer.id', 'source' => 'context_customer.id'],
            'key2',
            ['name' => 'customer.email', 'source' => 'context_customer.email'],
            ['name' => 'not_existed_context', 'source' => 'context_not_existed.id'],
            ['name' => 'key3', 'source' => 'context.wrong.format'],
        ];
        $eventData = [
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 'value3',
        ];
        $eventFields = $this->createFieldObjects($fields);
        $this->eventListMock->expects(self::once())
            ->method('get')
            ->with('some.event')
            ->willReturn($this->eventMock);
        $this->eventMock->expects(self::exactly(2))
            ->method('getFields')
            ->willReturn($fields);
        $this->eventMock->expects(self::once())
            ->method('getEventFields')
            ->willReturn($eventFields);
        $this->contextRetrieverMock->expects(self::exactly(3))
            ->method('getContextValue')
            ->willReturnCallback(function (string $source) {
                static $count = 0;
                switch ($count++) {
                    case 0:
                        self::assertEquals('context_customer.id', $source);
                        return '123';
                    case 1:
                        self::assertEquals('context_customer.email', $source);
                        return 'test@example.com';
                    case 2:
                        self::assertEquals('context_not_existed.id', $source);
                        return null;
                };
                return null;
            });
        $this->fieldProcessConverterMock->expects(self::never())
            ->method('convertField');

        self::assertEquals(
            [
                'customer' => [
                    'id' => '123',
                    'email' => 'test@example.com',
                ],
                'key2' => 'value2',
                'not_existed_context' => null,
                'key3' => 'value3',
            ],
            $this->filter->filter('some.event', $eventData)
        );
    }
}
