<?php
/************************************************************************
 *
 * ADOBE CONFIDENTIAL
 * ___________________
 *
 * Copyright 2025 Adobe
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
 * ************************************************************************
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Event\Operator;

use Magento\AdobeCommerceEventsClient\Event\Filter\EventFieldsFilter;
use Magento\AdobeCommerceEventsClient\Event\Rule\Rule;

/**
 * Verifies that value for provided event data field has changed.
 */
class OnChangeOperator implements CustomOperatorInterface
{
    /**
     * Verifies that value for provided event data field has changed.
     *
     * @param Rule $rule
     * @param array $eventData
     * @return bool
     * @throws OperatorException
     */
    public function verify(Rule $rule, array $eventData): bool
    {
        $ruleValue = $rule->getValue();
        if (empty($ruleValue)) {
            if (($eventData[EventFieldsFilter::FIELD_IS_NEW] ?? false)) {
                return true;
            }

            if (empty($eventData[EventFieldsFilter::FIELD_ORIGINAL_DATA])) {
                throw new OperatorException(
                    __('Event payload does not contain original data for comparison in onChange operator.')
                );
            }
        }

        $path = $rule->getField();
        $currentValue = $this->getValueByPath($eventData, $path);
        if (empty($ruleValue)) {
            $origValue = $this->getValueByPath(
                $eventData[EventFieldsFilter::FIELD_ORIGINAL_DATA],
                $path,
                'origin data'
            );
        } else {
            $origValue = $this->getValueByPath($eventData, $ruleValue);
        }

        return $currentValue != $origValue;
    }

    /**
     * Get value from array by dot-notation path
     *
     * @param array $data
     * @param string $path
     * @param string $dataName
     * @return mixed
     * @throws OperatorException
     */
    private function getValueByPath(array $data, string $path, string $dataName = 'event data'): mixed
    {
        $keys = explode('.', $path);
        $value = $data;

        foreach ($keys as $key) {
            if (!isset($value[$key])) {
                throw new OperatorException(
                    __('Path "%1" does not exist in %2 for comparison in onChange operator.', $path, $dataName)
                );
            }
            $value = $value[$key];
        }

        return $value;
    }
}
