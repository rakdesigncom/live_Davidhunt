<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Event\Filter;

use Magento\AdobeCommerceEventsClient\Event\Context\ContextRetriever;
use Magento\AdobeCommerceEventsClient\Event\Converter\EventFieldConverter;
use Magento\AdobeCommerceEventsClient\Event\DataFilterInterface;
use Magento\AdobeCommerceEventsClient\Event\Event;
use Magento\AdobeCommerceEventsClient\Event\EventList;
use Magento\AdobeCommerceEventsClient\Event\Filter\FieldFilter\Field;
use Magento\AdobeCommerceEventsClient\Event\Filter\FieldFilter\FieldConverter;
use Magento\Framework\App\ObjectManager;

/**
 * Filters event payload according to the list of configured fields
 */
class EventFieldsFilter implements DataFilterInterface
{
    /**
     * Event field value used to bypass filtering of the event payload.
     */
    public const FIELD_WILDCARD = '*';

    /**
     * Event field name used to store original event data before filtering.
     */
    public const FIELD_ORIGINAL_DATA = '_origData';

    /**
     * Event field name used to indicate whether the object is new.
     */
    public const FIELD_IS_NEW = '_isNew';

    /**
     * Prefix for context-based fields.
     */
    public const FIELD_CONTEXT_PREFIX = 'context_';

    /**
     * @var EventList
     */
    private EventList $eventList;

    /**
     * @var FieldConverter
     */
    private FieldConverter $converter;

    /**
     * @var EventFieldConverter
     */
    private EventFieldConverter $eventFieldConverter;

    /**
     * @var ContextRetriever
     */
    private ContextRetriever $contextRetriever;

    /**
     * @param EventList $eventList
     * @param FieldConverter $converter
     * @param EventFieldConverter|null $eventFieldConverter
     * @param ContextRetriever|null $contextRetriever
     */
    public function __construct(
        EventList $eventList,
        FieldConverter $converter,
        ?EventFieldConverter $eventFieldConverter = null,
        ?ContextRetriever $contextRetriever = null
    ) {
        $this->eventList = $eventList;
        $this->converter = $converter;
        $this->eventFieldConverter = $eventFieldConverter ?: ObjectManager::getInstance()->get(
            EventFieldConverter::class
        );
        $this->contextRetriever = $contextRetriever ?: ObjectManager::getInstance()->get(
            ContextRetriever::class
        );
    }

    /**
     * @inheritDoc
     */
    public function filter(string $eventCode, array $eventData): array
    {
        $event = $this->eventList->get($eventCode);

        if ($this->shouldBypassFiltering($event)) {
            if ($event && !in_array(self::FIELD_ORIGINAL_DATA, $event->getFields())) {
                $this->filterOutOrigData($eventData);
            }

            return $eventData;
        }

        $filteredData = [];

        $fields = $this->converter->convert($event->getEventFields());

        foreach ($fields as $field) {
            $filteredData = array_replace_recursive(
                $filteredData,
                [$field->getName() => $this->processField($field, $eventData, $event)]
            );
        }

        return $filteredData;
    }

    /**
     * Processes eventData filtering according to given Field.
     *
     * @param Field $field
     * @param array $eventData
     * @param Event $event
     * @return array|mixed|null
     */
    private function processField(Field $field, array $eventData, Event $event)
    {
        $filteredData = [];

        $children = $field->getChildren();
        $converterClass = $field->getConverterClass();

        if (empty($children)) {
            $fieldValue = $this->processTailField($field, $eventData, $event);
            if ($fieldValue !== null && $converterClass !== null) {
                $fieldValue = $this->eventFieldConverter->convertField($field, $fieldValue, $event);
            }
            return $fieldValue;
        }

        if ($field->isArray()) {
            if (!isset($eventData[$field->getName()]) || !is_array($eventData[$field->getName()])) {
                return [];
            }

            foreach ($eventData[$field->getName()] as $eventDataItem) {
                $filteredData[] = $this->processChildren(
                    $children,
                    is_array($eventDataItem) ? $eventDataItem : [],
                    $event
                );
            }
        } else {
            $filteredData = array_replace_recursive(
                $filteredData,
                $this->processChildren($children, $eventData[$field->getName()] ?? [], $event)
            );
        }

        return $filteredData;
    }

    /**
     * Process children field elements
     *
     * @param Field[] $children
     * @param array $eventData
     * @param Event $event
     * @return array
     */
    private function processChildren(array $children, array $eventData, Event $event): array
    {
        $result = [];

        foreach ($children as $child) {
            if ($child->hasChildren()) {
                $result[$child->getName()] = $this->processField($child, $eventData, $event);
            } else {
                $childValue = $this->processTailField($child, $eventData, $event);
                if ($childValue != null && $child->getConverterClass() != null) {
                    $childValue = $this->eventFieldConverter->convertField($child, $childValue, $event);
                }
                $result[$child->getName()] = $childValue;
            }
        }
        return $result;
    }

    /**
     * Process field which has no children.
     *
     * Removes index from array type fields.
     *
     * @param Field $field
     * @param array $eventData
     * @param Event $event
     * @return array|mixed|null
     */
    private function processTailField(Field $field, array $eventData, Event $event)
    {
        if ($this->isContextField($field)) {
            return $this->contextRetriever->getContextValue($field->getSource(), $event);
        }

        if (!isset($eventData[$field->getName()])) {
            return null;
        }

        if ($field->isArray() && is_array($eventData[$field->getName()])) {
            return array_values($eventData[$field->getName()]);
        }

        return $eventData[$field->getName()];
    }

    /**
     * Recursively removes _origData keys from the array
     *
     * @param array $eventData
     * @return void
     */
    private function filterOutOrigData(array &$eventData): void
    {
        foreach ($eventData as $key => &$value) {
            if ($key === self::FIELD_ORIGINAL_DATA) {
                unset($eventData[$key]);
                continue;
            }

            if (is_array($value)) {
                $this->filterOutOrigData($value);
            }
        }
    }

    /**
     * Determines whether filtering should be bypassed for the given event.
     *
     * @param Event|null $event
     * @return bool
     */
    private function shouldBypassFiltering(?Event $event): bool
    {
        return !$event instanceof Event
            || empty($event->getFields())
            || in_array(self::FIELD_WILDCARD, $event->getFields());
    }

    /**
     * Determines if the field is a context-based field.
     *
     * @param Field $field
     * @return bool
     */
    private function isContextField(Field $field): bool
    {
        return $field->getSource() !== null && str_starts_with($field->getSource(), self::FIELD_CONTEXT_PREFIX);
    }
}
