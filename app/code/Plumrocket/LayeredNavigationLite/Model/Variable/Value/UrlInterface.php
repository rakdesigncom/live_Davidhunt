<?php
/**
 * @package     Plumrocket_LayeredNavigationLite
 * @copyright   Copyright (c) 2022 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

namespace Plumrocket\LayeredNavigationLite\Model\Variable\Value;

/**
 * @since 1.0.0
 */
interface UrlInterface
{

    /**
     * Encode value to use it url.
     *
     * @param string     $variable
     * @param string|int $value
     * @return string
     */
    public function encode(string $variable, $value): string;

    /**
     * Decode url value.
     *
     * @param string       $variable
     * @param string|array $value
     * @return string
     */
    public function decode(string $variable, string $value): string;
}
