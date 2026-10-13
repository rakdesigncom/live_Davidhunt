<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\AdobeCommerceEventsClient\Event\Converter;

use Exception;
use Magento\AdobeCommerceEventsClient\Event\Filter\EventFieldsFilter;
use Magento\Framework\Data\Collection;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class for converting data to event suitable format
 *
 * @api
 * @since 1.1.0
 */
class EventDataConverter
{
    private const MAX_DEPTH = 5;

    /**
     * Convert object or array of objects to array format
     *
     * @param mixed $objectOrArray
     * @return array
     * @throws Exception
     */
    public function convert($objectOrArray): array
    {
        if (is_object($objectOrArray)) {
            if ($this->hasToArrayMethod($objectOrArray)) {
                return $this->convertObjectAndAddOrigData($objectOrArray);
            }

            throw new LocalizedException(
                __(sprintf('Object %s can not be converted to array', get_class($objectOrArray)))
            );
        }

        if (is_array($objectOrArray)) {
            return $this->convertArray($objectOrArray);
        }

        throw new LocalizedException(__('Wrong type of input argument'));
    }

    /**
     * Converts event data to the array.
     *
     * @param array $data
     * @return array
     */
    private function convertArray(array $data): array
    {
        foreach (['data_object', 'collection', 'object'] as $key) {
            if (isset($data[$key]) && $this->hasToArrayMethod($data[$key])) {
                return $this->convertObjectAndAddOrigData($data[$key]);
            }
        }

        $result = [];
        foreach ($data as $key => $value) {
            if (is_object($value)) {
                if ($this->hasToArrayMethod($value)) {
                    $result[$key] = $this->convertObjectAndAddOrigData($value);
                }
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * Converts Object to an array and appends original data if the object has it
     *
     * @param object $object
     * @return array
     */
    private function convertObjectAndAddOrigData($object): array
    {
        $convertedResult = $this->convertAndCleanData($this->toArray($object));
        if (method_exists($object, 'getOrigData') && is_callable([$object, 'getOrigData'])) {
            $origData = $object->getOrigData();
            if (is_array($origData)) {
                $convertedResult[EventFieldsFilter::FIELD_ORIGINAL_DATA] = $this->convertAndCleanData($origData);
            }
        }
        if (method_exists($object, 'isObjectNew') && is_callable([$object, 'isObjectNew'])) {
            $convertedResult[EventFieldsFilter::FIELD_IS_NEW] = $object->isObjectNew();
        }

        return $convertedResult;
    }

    /**
     * Clears array from the cached items.
     *
     * Convert objects to array if possible otherwise clean array data from such objects.
     * If the converted object is instance of Collection returns only it `items` after conversion.
     * Maximum depth is added to avoid recursion.
     *
     * @param array $data
     * @param int $depth
     * @return array
     */
    private function convertAndCleanData(array $data, int $depth = 1): array
    {
        if ($depth > self::MAX_DEPTH) {
            return $data;
        }

        foreach ($data as $key => $value) {
            if (strpos($key, '_cache') === 0) {
                unset($data[$key]);
                continue;
            }

            if (is_object($value)) {
                if ($this->hasToArrayMethod($value)) {
                    $conversionResult = $this->convertAndCleanData($this->toArray($value), $depth + 1);
                    if ($value instanceof Collection && isset($conversionResult['items'])) {
                        $data[$key] = $conversionResult['items'];
                    } else {
                        $data[$key] = $conversionResult;
                    }
                } else {
                    unset($data[$key]);
                }
            }

            if (is_array($value)) {
                $data[$key] = $this->convertAndCleanData($value, $depth + 1);
            }
        }

        return $data;
    }

    /**
     * Checks if the input object has a toArray or callable __toArray method.
     *
     * @param object $object
     * @return bool
     */
    private function hasToArrayMethod($object): bool
    {
        return (method_exists($object, 'toArray')) ||
            (method_exists($object, '__toArray') && is_callable([$object, '__toArray']));
    }

    /**
     * Converts an object to an array if it has a toArray or __toArray method. Returns an empty array otherwise.
     *
     * @param object $object
     * @return array
     */
    private function toArray($object): array
    {
        if (method_exists($object, 'toArray')) {
            return $object->toArray();
        }

        if (method_exists($object, '__toArray') && is_callable([$object, '__toArray'])) {
            return $object->__toArray();
        }

        return [];
    }
}
