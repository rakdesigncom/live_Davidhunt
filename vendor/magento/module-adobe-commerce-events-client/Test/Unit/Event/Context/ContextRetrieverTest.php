<?php
/**
 * ADOBE CONFIDENTIAL
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
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Test\Unit\Event\Context;

use Magento\AdobeCommerceEventsClient\Event\Context\ContextRetriever;
use Magento\AdobeCommerceEventsClient\Event\Converter\EventDataConverter;
use Magento\AdobeCommerceEventsClient\Event\Event;
use Magento\AdobeCommerceOutOfProcessExtensibility\Model\Context\ContextArrayFieldFilter;
use Magento\AdobeCommerceOutOfProcessExtensibility\Model\Context\ContextRetriever as Retriever;
use Magento\AdobeCommerceOutOfProcessExtensibility\Model\Context\ContextRetrieverException;
use Magento\AdobeCommerceOutOfProcessExtensibility\Model\Context\ContextSourceParser;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

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
     * @var Retriever|MockObject
     */
    private Retriever|MockObject $retrieverMock;

    /**
     * @var EventDataConverter|MockObject
     */
    private EventDataConverter|MockObject $eventDataConverterMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private LoggerInterface|MockObject $loggerMock;

    protected function setUp(): void
    {
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->eventDataConverterMock = $this->createMock(EventDataConverter::class);
        $this->retrieverMock = $this->createMock(Retriever::class);
        $this->contextRetriever = new ContextRetriever(
            $this->retrieverMock,
            $this->eventDataConverterMock,
            $this->loggerMock,
            new ContextSourceParser(),
            new ContextArrayFieldFilter(),
        );
    }

    public function testGetContextValue()
    {
        $source = 'context_test.get_value';

        $eventMock = $this->createMock(Event::class);
        $eventMock->expects(self::never())
            ->method('getName');
        $this->retrieverMock->expects(self::once())
            ->method('getContextValue')
            ->with($source)
            ->willReturn('some_value');
        $this->loggerMock->expects(self::never())
            ->method('warning');
        $this->eventDataConverterMock->expects(self::never())
            ->method('convert');

        self::assertEquals(
            'some_value',
            $this->contextRetriever->getContextValue($source, $eventMock)
        );
    }

    public function testGetContextValueWithContextRetrieverException()
    {
        $source = 'context_test.get_value';
        $eventName = 'test.event';
        $exceptionMessage = 'Context retrieval failed';

        $eventMock = $this->createMock(Event::class);
        $eventMock->expects(self::once())
            ->method('getName')
            ->willReturn($eventName);
        $this->retrieverMock->expects(self::once())
            ->method('getContextValue')
            ->with($source)
            ->willThrowException(new ContextRetrieverException(__($exceptionMessage)));
        $this->eventDataConverterMock->expects(self::never())
            ->method('convert');
        $this->loggerMock->expects(self::once())
            ->method('warning')
            ->with(
                'Failed to retrieve context value for event test.event: ' . $exceptionMessage,
                ['destination' => ['internal', 'external']]
            );

        self::assertNull($this->contextRetriever->getContextValue($source, $eventMock));
    }

    public function testGetContextValueWithObjectConversion()
    {
        $source = 'context_test.get_value';

        $eventMock = $this->createMock(Event::class);
        $eventMock->expects(self::never())
            ->method('getName');

        $testClass = new class {
            public function getData(): string
            {
                return 'value';
            }
        };
        $this->retrieverMock->expects(self::once())
            ->method('getContextValue')
            ->with($source)
            ->willReturn($testClass);
        $this->loggerMock->expects(self::never())
            ->method('warning');
        $this->eventDataConverterMock->expects(self::once())
            ->method('convert')
            ->with($testClass)
            ->willReturn(['data' => 'value']);

        self::assertEquals(
            ['data' => 'value'],
            $this->contextRetriever->getContextValue($source, $eventMock)
        );
    }

    public function testGetContextValueWithArrayOfObjects()
    {
        $source = 'context_test.get_value';
        $eventMock = $this->createMock(Event::class);
        $eventMock->expects(self::never())
            ->method('getName');

        $object1 = new class {
            public function getId(): int
            {
                return 1;
            }
        };
        $object2 = new class {
            public function getId(): int
            {
                return 2;
            }
        };

        $this->retrieverMock->expects(self::once())
            ->method('getContextValue')
            ->with($source)
            ->willReturn([$object1, $object2]);
        $this->loggerMock->expects(self::never())
            ->method('warning');
        $this->eventDataConverterMock->expects(self::exactly(2))
            ->method('convert')
            ->willReturnMap([
                [$object1, ['id' => 1]],
                [$object2, ['id' => 2]],
            ]);

        self::assertEquals(
            [['id' => 1], ['id' => 2]],
            $this->contextRetriever->getContextValue($source, $eventMock)
        );
    }

    public function testGetContextValueWithArrayOfMixedTypes()
    {
        $source = 'context_test.get_value';
        $eventMock = $this->createMock(Event::class);
        $eventMock->expects(self::never())
            ->method('getName');

        $object = new class {
            public function getName(): string
            {
                return 'foo';
            }
        };

        $this->retrieverMock->expects(self::once())
            ->method('getContextValue')
            ->with($source)
            ->willReturn([$object, 'scalar_value', 42]);
        $this->loggerMock->expects(self::never())
            ->method('warning');
        $this->eventDataConverterMock->expects(self::once())
            ->method('convert')
            ->with($object)
            ->willReturn(['name' => 'foo']);

        self::assertEquals(
            [['name' => 'foo'], 'scalar_value', 42],
            $this->contextRetriever->getContextValue($source, $eventMock)
        );
    }

    public function testGetContextValueWithObjectConversionFailure()
    {
        $source = 'context_test.get_value';
        $eventName = 'test.event';

        $eventMock = $this->createMock(Event::class);
        $eventMock->expects(self::once())
            ->method('getName')
            ->willReturn($eventName);

        $testClass = new class {
            public function getData(): string
            {
                return 'value';
            }
        };
        $this->retrieverMock->expects(self::once())
            ->method('getContextValue')
            ->with($source)
            ->willReturn($testClass);
        $this->eventDataConverterMock->expects(self::once())
            ->method('convert')
            ->with($testClass)
            ->willThrowException(new \RuntimeException('Conversion failed'));
        $this->loggerMock->expects(self::once())
            ->method('warning')
            ->with(
                'Failed to retrieve context value for event test.event: ' .
                'Can not convert context value retrieved with source \'context_test.get_value\'.',
                ['destination' => ['internal', 'external']]
            );

        self::assertNull($this->contextRetriever->getContextValue($source, $eventMock));
    }

    public function testGetContextValueWithArrayOfObjectsConversionFailure()
    {
        $source = 'context_test.get_value';
        $eventName = 'test.event';

        $eventMock = $this->createMock(Event::class);
        $eventMock->expects(self::once())
            ->method('getName')
            ->willReturn($eventName);

        $object = new class {
        };

        $this->retrieverMock->expects(self::once())
            ->method('getContextValue')
            ->with($source)
            ->willReturn([$object]);
        $this->eventDataConverterMock->expects(self::once())
            ->method('convert')
            ->willThrowException(new \RuntimeException('Conversion error'));
        $this->loggerMock->expects(self::once())
            ->method('warning')
            ->with(
                sprintf(
                    'Failed to retrieve context value for event test.event: ' .
                    'Can not convert context value retrieved with source \'%s\'.',
                    $source
                ),
                ['destination' => ['internal', 'external']]
            );

        self::assertNull($this->contextRetriever->getContextValue($source, $eventMock));
    }

    public function testGetContextValueStripsFilterFromRetrieverPath(): void
    {
        $path = 'context_test.get_items';
        $source = $path . '[sku,qty]';
        $eventMock = $this->createMock(Event::class);
        $eventMock->expects(self::never())
            ->method('getName');

        // Retriever must be called with the path only, NOT with the full source including [...]
        $this->retrieverMock->expects(self::once())
            ->method('getContextValue')
            ->with($path)
            ->willReturn([['sku' => 'ABC', 'qty' => 2, 'price' => 9.99]]);

        $this->eventDataConverterMock->expects(self::never())
            ->method('convert');

        $result = $this->contextRetriever->getContextValue($source, $eventMock);

        self::assertSame([['sku' => 'ABC', 'qty' => 2]], $result);
    }

    public function testGetContextValueAppliesNestedFieldFilter(): void
    {
        $path = 'context_checkout_session.get_quote.get_items';
        $source = $path . '[sku,product.name,product.media_gallery_entries.file]';
        $eventMock = $this->createMock(Event::class);
        $eventMock->expects(self::never())
            ->method('getName');

        $rawItems = [
            [
                'sku' => 'ABC-001',
                'qty' => 2,
                'product' => [
                    'name' => 'Hoodie',
                    'color' => 'Black',
                    'media_gallery_entries' => [
                        ['file' => '/m/h/mh01.jpg', 'type' => 'image'],
                        ['file' => '/m/h/mh02.jpg', 'type' => 'image'],
                    ],
                ],
            ],
        ];

        $this->retrieverMock->expects(self::once())
            ->method('getContextValue')
            ->with($path)
            ->willReturn($rawItems);

        $this->eventDataConverterMock->expects(self::never())
            ->method('convert');

        $result = $this->contextRetriever->getContextValue($source, $eventMock);

        self::assertSame(
            [
                [
                    'sku' => 'ABC-001',
                    'product' => [
                        'name' => 'Hoodie',
                        'media_gallery_entries' => [
                            ['file' => '/m/h/mh01.jpg'],
                            ['file' => '/m/h/mh02.jpg'],
                        ],
                    ],
                ],
            ],
            $result
        );
    }

    public function testGetContextValueWithNoFilterReturnsFullArray(): void
    {
        $source = 'context_test.get_items';
        $eventMock = $this->createMock(Event::class);
        $eventMock->expects(self::never())
            ->method('getName');

        $rawItems = [['sku' => 'ABC', 'qty' => 2, 'price' => 9.99]];

        $this->retrieverMock->expects(self::once())
            ->method('getContextValue')
            ->with($source)
            ->willReturn($rawItems);

        $result = $this->contextRetriever->getContextValue($source, $eventMock);

        self::assertSame($rawItems, $result);
    }

    public function testGetContextValueAppliesFieldFilterToSingleAssociativeArray(): void
    {
        $path = 'context_checkout_session.get_quote';
        $source = $path . '[store_id,items_qty]';
        $eventMock = $this->createMock(Event::class);
        $eventMock->expects(self::never())
            ->method('getName');

        $rawQuote = [
            'store_id'   => 1,
            'items_qty'  => 3,
            'grand_total' => 99.99,
            'coupon_code' => 'SALE10',
        ];

        $this->retrieverMock->expects(self::once())
            ->method('getContextValue')
            ->with($path)
            ->willReturn($rawQuote);

        $this->eventDataConverterMock->expects(self::never())
            ->method('convert');

        $result = $this->contextRetriever->getContextValue($source, $eventMock);

        self::assertSame(
            ['store_id' => 1, 'items_qty' => 3],
            $result
        );
    }
}
