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
 * Applies dot-path field filters to an array of items or a single associative array.
 *
 * Given a list of items and dot-path field selectors such as
 * ["sku", "qty", "product.name", "product.media_gallery_entries.file"],
 * each item is reduced to only the requested fields, preserving the nested structure.
 *
 * Use {@see filter()} for a list of items and {@see filterItem()} for a single associative array.
 */
class ContextArrayFieldFilter
{
    /**
     * Filters each item in $items, keeping only the fields specified by $fieldPaths.
     *
     * @param array $items
     * @param string[] $fieldPaths Dot-separated paths, e.g. ["sku", "product.name"]
     * @return array
     */
    public function filter(array $items, array $fieldPaths): array
    {
        return array_map(fn($item) => $this->filterItem($item, $fieldPaths), $items);
    }

    /**
     * Reduces a single item to only the fields specified by $fieldPaths.
     *
     * @param mixed $item
     * @param string[] $fieldPaths
     * @return mixed
     */
    public function filterItem(mixed $item, array $fieldPaths): mixed
    {
        if (!is_array($item)) {
            return $item;
        }

        $result = [];
        foreach ($fieldPaths as $fieldPath) {
            $parts = explode('.', $fieldPath, 2);
            $key = $parts[0];

            if (!array_key_exists($key, $item)) {
                continue;
            }

            if (count($parts) === 1) {
                $result[$key] = $item[$key];
            } else {
                $nestedPath = $parts[1];
                $childValue = $item[$key];

                if (is_array($childValue) && array_is_list($childValue)) {
                    $nestedResult = $this->filter($childValue, [$nestedPath]);
                } else {
                    $nestedResult = $this->filterItem($childValue, [$nestedPath]);
                }

                if (isset($result[$key]) && is_array($result[$key]) && is_array($nestedResult)) {
                    $result[$key] = $this->mergeNested($result[$key], $nestedResult);
                } else {
                    $result[$key] = $nestedResult;
                }
            }
        }

        return $result;
    }

    /**
     * Deep-merges two arrays, combining list arrays by index rather than concatenating them.
     *
     * @param array $a
     * @param array $b
     * @return array
     */
    private function mergeNested(array $a, array $b): array
    {
        if (array_is_list($a) && array_is_list($b)) {
            $result = [];
            $count = max(count($a), count($b));
            for ($i = 0; $i < $count; $i++) {
                $itemA = $a[$i] ?? [];
                $itemB = $b[$i] ?? [];
                $result[] = (is_array($itemA) && is_array($itemB))
                    ? $this->mergeNested($itemA, $itemB)
                    : $itemB ?? $itemA;
            }
            return $result;
        }

        $result = $a;
        foreach ($b as $key => $value) {
            if (isset($result[$key]) && is_array($result[$key]) && is_array($value)) {
                $result[$key] = $this->mergeNested($result[$key], $value);
            } else {
                $result[$key] = $value;
            }
        }
        return $result;
    }
}
