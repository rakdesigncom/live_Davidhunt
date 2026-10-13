<?php
/**
 * @package     Plumrocket_LayeredNavigationLite
 * @copyright   Copyright (c) 2022 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

declare(strict_types=1);

namespace Plumrocket\LayeredNavigationLite\Model\FilterItem;

use Magento\Catalog\Model\Layer\Filter\Item;

/**
 * @since 1.1.2
 */
class Status
{
    /**
     * Active Filter List.
     *
     * @var null|array
     */
    private $activeFilters;

    /**
     * Mark active filter items.
     *
     * @param array $items
     * @return void
     */
    public function markActiveItems(array $items): void
    {
        foreach ($items as $item) {
            $item->setData(
                'is_active',
                $this->isActive($item)
            );
        }
    }

    /**
     * Is filter active.
     *
     * @param \Magento\Catalog\Model\Layer\Filter\Item $item
     * @return bool
     */
    private function isActive(Item $item): bool
    {
        $value = (string) $item->getValue();
        $additionalValue = str_replace(' ', '_', strtolower($value));
        if ($value == '0') {
            $value = 'no';
        }

        $filterObject = $item->getData('filter');
        $attributeCode = $filterObject->getData('pf_attribute_code');
        $activeFilters = $this->getActiveFilters($filterObject->getLayer());

        return $item->getIsActive()
            || (isset($activeFilters[$attributeCode])
                && (in_array($value, $activeFilters[$attributeCode])
                    || in_array($additionalValue, $activeFilters[$attributeCode]))
            );
    }

    /**
     * Retrieve active filter.
     *
     * @param object $layer
     * @return array
     */
    protected function getActiveFilters($layer): array
    {
        if (null === $this->activeFilters) {
            $this->activeFilters = [];

            if (! empty($layer->getState()->getFilters())) {
                foreach ($layer->getState()->getFilters() as $filter) {
                    $attributeCode = $filter->getData('filter')->getData('pf_attribute_code');
                    $value = $filter->getValue();

                    if (!is_array($value)) {
                        $value = strtolower((string) $value);
                    }

                    $this->activeFilters[$attributeCode][] = $value;
                }
            }
        }

        return $this->activeFilters;
    }
}
