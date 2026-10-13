<?php
/**
 * @package     Plumrocket_LayeredNavigationLite
 * @copyright   Copyright (c) 2022 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

namespace Plumrocket\LayeredNavigationLite\Api;

/**
 * @since 1.0.0
 */
interface FiltersOptionsInterface
{

    /**
     * Get attribute option id by its escaped label.
     *
     * @param string $requestVar
     * @param string $optionCode
     * @return int|string
     */
    public function toOptionValue(string $requestVar, string $optionCode);

    /**
     * Get attribute option code by its id.
     *
     * @param string     $requestVar
     * @param int|string $optionValue
     * @return string
     */
    public function toOptionCode(string $requestVar, $optionValue): string;

    /**
     * Get attribute option label by its id.
     *
     * @param string     $requestVar
     * @param int|string $optionValue
     * @return string
     */
    public function toOptionLabel(string $requestVar, $optionValue): string;

    /**
     * Get category id by url key.
     *
     * @param string $urlKey
     * @return int
     */
    public function getCategoryId(string $urlKey): int;
}
