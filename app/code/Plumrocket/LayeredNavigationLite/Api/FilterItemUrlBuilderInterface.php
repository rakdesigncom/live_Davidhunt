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
interface FilterItemUrlBuilderInterface
{

    /**
     * Create url to add filter option.
     *
     * @param string $requestVar
     * @param string $itemValue
     * @param bool   $removeCurrentValue
     * @return string
     */
    public function getAddFilterUrl(string $requestVar, string $itemValue, bool $removeCurrentValue = false): string;

    /**
     * Create url to remove filter option.
     *
     * @param string $requestVar
     * @param string $itemValue
     * @param bool $isRadioAttribute
     * @return string
     */
    public function getRemoveFilterUrl(string $requestVar, string $itemValue, bool $isRadioAttribute = false): string;

    /**
     * Create url to toggle filter option.
     *
     * @param string $requestVar
     * @param string $itemValue
     * @param bool   $removeCurrentValue
     * @return string
     */
    public function toggleFilterUrl(string $requestVar, string $itemValue, bool $removeCurrentValue = false): string;
}
