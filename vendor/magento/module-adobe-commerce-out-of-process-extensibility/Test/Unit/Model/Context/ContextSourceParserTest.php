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

use Magento\AdobeCommerceOutOfProcessExtensibility\Model\Context\ContextSourceParser;
use PHPUnit\Framework\TestCase;

/**
 * Tests for @see ContextSourceParser
 */
class ContextSourceParserTest extends TestCase
{
    private ContextSourceParser $parser;

    protected function setUp(): void
    {
        $this->parser = new ContextSourceParser();
    }

    public function testGetRetrieverPathWithNoFilter(): void
    {
        self::assertSame(
            'context_checkout_session.get_quote.get_items',
            $this->parser->getRetrieverPath('context_checkout_session.get_quote.get_items')
        );
    }

    public function testGetRetrieverPathWithFilter(): void
    {
        self::assertSame(
            'context_checkout_session.get_quote.get_items',
            $this->parser->getRetrieverPath('context_checkout_session.get_quote.get_items[sku,qty,price]')
        );
    }

    public function testGetRetrieverPathWithNestedFilter(): void
    {
        self::assertSame(
            'context_checkout_session.get_quote.get_items',
            $this->parser->getRetrieverPath(
                'context_checkout_session.get_quote.get_items[sku,product.name,product.media_gallery_entries.file]'
            )
        );
    }

    public function testGetFieldFiltersWithNoFilter(): void
    {
        self::assertSame(
            [],
            $this->parser->getFieldFilters('context_checkout_session.get_quote.get_items')
        );
    }

    public function testGetFieldFiltersWithFlatFields(): void
    {
        self::assertSame(
            ['sku', 'qty', 'price'],
            $this->parser->getFieldFilters('context_checkout_session.get_quote.get_items[sku,qty,price]')
        );
    }

    public function testGetFieldFiltersWithNestedFields(): void
    {
        self::assertSame(
            ['sku', 'product.name', 'product.media_gallery_entries.file'],
            $this->parser->getFieldFilters(
                'context_checkout_session.get_quote.get_items[sku,product.name,product.media_gallery_entries.file]'
            )
        );
    }

    public function testGetFieldFiltersTrimsWhitespace(): void
    {
        self::assertSame(
            ['sku', 'qty', 'price'],
            $this->parser->getFieldFilters('context_checkout_session.get_quote.get_items[ sku , qty , price ]')
        );
    }

    public function testGetFieldFiltersWithEmptyBrackets(): void
    {
        self::assertSame(
            [],
            $this->parser->getFieldFilters('context_checkout_session.get_quote.get_items[]')
        );
    }

    public function testGetFieldFiltersWithMissingClosingBracket(): void
    {
        self::assertSame(
            [],
            $this->parser->getFieldFilters('context_checkout_session.get_quote.get_items[sku,qty')
        );
    }
}

