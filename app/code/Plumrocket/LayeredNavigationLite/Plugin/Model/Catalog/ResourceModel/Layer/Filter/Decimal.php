<?php
/**
 * @package     Plumrocket_LayeredNavigationLite
 * @copyright   Copyright (c) 2022 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

namespace Plumrocket\LayeredNavigationLite\Plugin\Model\Catalog\ResourceModel\Layer\Filter;

use Magento\Catalog\Model\ResourceModel\Layer\Filter\Decimal as DecimalFilter;

/**
 * Fix magento bug with null max and min values for PHP 8.1.
 *
 * @since 1.2.2
 */
class Decimal
{

    /**
     * Convert null to integer.
     *
     * @param DecimalFilter $subject
     * @param array         $result
     * @return array
     */
    public function afterGetMinMax(DecimalFilter $subject, array $result): array
    {
        foreach ($result as $valueKey => $value) {
            if (null === $value) {
                $result[$valueKey] = 0;
            }
        }

        return $result;
    }
}
