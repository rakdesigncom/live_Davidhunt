<?php
/**
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
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceOutOfProcessExtensibility\Test\Unit\Model\Context;

use Magento\AdobeCommerceOutOfProcessExtensibility\Model\Context\ContextArrayFieldFilter;
use PHPUnit\Framework\TestCase;

/**
 * Tests for @see ContextArrayFieldFilter
 */
class ContextArrayFieldFilterTest extends TestCase
{
    private ContextArrayFieldFilter $filter;

    protected function setUp(): void
    {
        $this->filter = new ContextArrayFieldFilter();
    }

    public function testFilterFlatFields(): void
    {
        $items = [
            ['sku' => 'ABC-001', 'qty' => 2, 'price' => 29.99, 'weight' => 1.5],
            ['sku' => 'XYZ-002', 'qty' => 1, 'price' => 49.99, 'weight' => 0.8],
        ];

        $result = $this->filter->filter($items, ['sku', 'qty', 'price']);

        self::assertSame(
            [
                ['sku' => 'ABC-001', 'qty' => 2, 'price' => 29.99],
                ['sku' => 'XYZ-002', 'qty' => 1, 'price' => 49.99],
            ],
            $result
        );
    }

    public function testFilterNestedField(): void
    {
        $items = [
            [
                'sku' => 'ABC-001',
                'product' => ['name' => 'Hoodie', 'color' => 'Black', 'weight' => 1.5],
            ],
        ];

        $result = $this->filter->filter($items, ['sku', 'product.name']);

        self::assertSame(
            [
                ['sku' => 'ABC-001', 'product' => ['name' => 'Hoodie']],
            ],
            $result
        );
    }

    public function testFilterDeeplyNestedField(): void
    {
        $items = [
            [
                'sku' => 'ABC-001',
                'product' => [
                    'name' => 'Hoodie',
                    'media_gallery_entries' => ['file' => '/m/h/mh01.jpg', 'type' => 'image'],
                ],
            ],
        ];

        $result = $this->filter->filter($items, ['sku', 'product.media_gallery_entries.file']);

        self::assertSame(
            [
                [
                    'sku' => 'ABC-001',
                    'product' => ['media_gallery_entries' => ['file' => '/m/h/mh01.jpg']],
                ],
            ],
            $result
        );
    }

    public function testFilterMultipleNestedFieldsUnderSameParent(): void
    {
        $items = [
            [
                'sku' => 'ABC-001',
                'product' => ['name' => 'Hoodie', 'sku' => 'PROD-SKU', 'color' => 'Black'],
            ],
        ];

        $result = $this->filter->filter($items, ['sku', 'product.name', 'product.sku']);

        self::assertSame(
            [
                ['sku' => 'ABC-001', 'product' => ['name' => 'Hoodie', 'sku' => 'PROD-SKU']],
            ],
            $result
        );
    }

    public function testFilterNestedFieldWithListChild(): void
    {
        $items = [
            [
                'sku' => 'ABC-001',
                'product' => [
                    'media_gallery_entries' => [
                        ['file' => '/m/h/mh01.jpg', 'type' => 'image', 'position' => 1],
                        ['file' => '/m/h/mh02.jpg', 'type' => 'image', 'position' => 2],
                    ],
                ],
            ],
        ];

        $result = $this->filter->filter($items, ['sku', 'product.media_gallery_entries.file']);

        self::assertSame(
            [
                [
                    'sku' => 'ABC-001',
                    'product' => [
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

    public function testFilterMissingKeyIsOmitted(): void
    {
        $items = [
            ['sku' => 'ABC-001', 'qty' => 2],
        ];

        $result = $this->filter->filter($items, ['sku', 'price']);

        self::assertSame(
            [['sku' => 'ABC-001']],
            $result
        );
    }

    public function testFilterNonArrayItemIsReturnedAsIs(): void
    {
        $items = ['scalar_value', 42];

        $result = $this->filter->filter($items, ['sku']);

        self::assertSame(['scalar_value', 42], $result);
    }

    public function testFilterEmptyItems(): void
    {
        self::assertSame([], $this->filter->filter([], ['sku', 'qty']));
    }

    public function testFilterMultipleSiblingFieldsInNestedList(): void
    {
        $items = [
            [
                'sku' => 'ABC-001',
                'product' => [
                    'media_gallery_entries' => [
                        ['file' => '/m/h/mh01.jpg', 'type' => 'image', 'position' => 1],
                        ['file' => '/m/h/mh02.jpg', 'type' => 'image', 'position' => 2],
                    ],
                ],
            ],
        ];

        $result = $this->filter->filter(
            $items,
            ['sku', 'product.media_gallery_entries.file', 'product.media_gallery_entries.type']
        );

        self::assertSame(
            [
                [
                    'sku' => 'ABC-001',
                    'product' => [
                        'media_gallery_entries' => [
                            ['file' => '/m/h/mh01.jpg', 'type' => 'image'],
                            ['file' => '/m/h/mh02.jpg', 'type' => 'image'],
                        ],
                    ],
                ],
            ],
            $result
        );
    }

    public function testFilterItemOnSingleAssociativeArray(): void
    {
        $quote = [
            'store_id'    => 1,
            'items_qty'   => 3,
            'grand_total' => 99.99,
            'coupon_code' => 'SALE10',
        ];

        $result = $this->filter->filterItem($quote, ['store_id', 'items_qty']);

        self::assertSame(
            ['store_id' => 1, 'items_qty' => 3],
            $result
        );
    }
}

