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

namespace Magento\AdobeCommerceOutOfProcessExtensibility\Model\Context;

/**
 * Parses a context source string into a retriever path and an optional list of dot-path field filters.
 *
 * Supported syntax:
 *   - Plain source:   context_foo.get_bar.get_items
 *   - With filters:   context_foo.get_bar.get_items[sku,qty,product.name]
 */
class ContextSourceParser
{
    /**
     * Returns the retriever path without the filter expression.
     *
     * @param string $source
     * @return string
     */
    public function getRetrieverPath(string $source): string
    {
        $bracketPos = strpos($source, '[');
        if ($bracketPos === false) {
            return $source;
        }

        return substr($source, 0, $bracketPos);
    }

    /**
     * Returns the list of dot-path field filters, or an empty array when none are specified.
     *
     * @param string $source
     * @return string[]
     */
    public function getFieldFilters(string $source): array
    {
        $bracketPos = strpos($source, '[');
        if ($bracketPos === false) {
            return [];
        }

        $closingPos = strpos($source, ']', $bracketPos);
        if ($closingPos === false) {
            return [];
        }

        $inner = substr($source, $bracketPos + 1, $closingPos - $bracketPos - 1);
        if (trim($inner) === '') {
            return [];
        }

        return array_map('trim', explode(',', $inner));
    }
}
