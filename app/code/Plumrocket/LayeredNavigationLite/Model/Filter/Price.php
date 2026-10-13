<?php
/**
 * @package     Plumrocket_LayeredNavigationLite
 * @copyright   Copyright (c) 2022 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

namespace Plumrocket\LayeredNavigationLite\Model\Filter;

class Price extends \Magento\CatalogSearch\Model\Layer\Filter\Price
{
    /**
     * Retrieve is radio type
     *
     * @return boolean
     */
    public function getIsRadio()
    {
        return true;
    }

    /**
     * Fix price value for frontend.
     *
     * Change price format from '50-59.011' to '50_59'
     *
     * @param string $label
     * @param mixed  $value
     * @param int    $count
     * @return \Magento\Catalog\Model\Layer\Filter\Item
     */
    protected function _createItem($label, $value, $count = 0)
    {
        $priceDelta = ['.011'];
        if (defined('Magento\CatalogSearch\Model\Layer\Filter\Price::PRICE_DELTA')) {
            $priceDelta[] = strstr((string) \Magento\CatalogSearch\Model\Layer\Filter\Price::PRICE_DELTA, '.');
        }

        if (is_array($value) && \count($value) === 2) {
            $value[1] = str_replace($priceDelta, '', $value[1]);
            $value[1] = str_replace('-', '_', $value[1]);
        } elseif (is_string($value)) {
            $value = str_replace($priceDelta, '', $value);
            $value = str_replace('-', '_', $value);
        }
        return parent::_createItem($label, $value, $count);
    }
}
