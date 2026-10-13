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

namespace Magento\AdobeCommerceEventsClient\Event\Merger;

use Magento\AdobeCommerceEventsClient\Event\Event;
use Magento\AdobeCommerceEventsClient\Event\EventFactory;
use Magento\AdobeCommerceEventsClient\Event\EventField;
use Magento\AdobeCommerceEventsClient\Event\Rule\RuleInterface;

/**
 * @inheritDoc
 */
class EventMerger implements EventMergerInterface
{
    /**
     * @param EventFactory $eventFactory
     */
    public function __construct(private readonly EventFactory $eventFactory)
    {
    }

    /**
     * @inheritDoc
     */
    public function merge(Event $baseEvent, Event $eventToMerge): Event
    {
        return $this->eventFactory->create([
            Event::EVENT_NAME => $baseEvent->getName(),
            Event::EVENT_PARENT => $eventToMerge->getParent() ?: $baseEvent->getParent(),
            Event::EVENT_FIELDS =>
                $this->mergeFields($baseEvent->getEventFields(), $eventToMerge->getEventFields()),
            Event::EVENT_RULES =>
                $this->mergeRules($baseEvent->getRules(), $eventToMerge->getRules()),
            Event::EVENT_PRIORITY => $eventToMerge->isPriority(),
            Event::EVENT_HIPAA_AUDIT_REQUIRED => $eventToMerge->isHipaaAuditRequired(),
            Event::EVENT_DESTINATION => $eventToMerge->getDestination() ?: $baseEvent->getDestination(),
            Event::EVENT_ENABLED => $baseEvent->isEnabled(),
            Event::EVENT_PROCESSORS => $baseEvent->getProcessors(),
            Event::EVENT_XML_DEFINED => $baseEvent->isXmlDefined(),
            Event::EVENT_PROVIDER_ID => $eventToMerge->getProviderId() ?: $baseEvent->getProviderId()
        ]);
    }

    /**
     * Merges the existing field configuration with the updated field configuration
     *
     * @param EventField[] $baseFields
     * @param EventField[] $fieldsToMerge
     * @return array
     */
    public function mergeFields(array $baseFields, array $fieldsToMerge): array
    {
        return array_values(
            array_replace(
                $this->createMergeableFieldsArray($baseFields),
                $this->createMergeableFieldsArray($fieldsToMerge)
            )
        );
    }

    /**
     * Creates an array of event field data arrays with event names as keys
     *
     * @param EventField[] $eventFields
     * @return array
     */
    private function createMergeableFieldsArray(array $eventFields): array
    {
        $fieldData = [];
        foreach ($eventFields as $eventField) {
            $eventFieldData = $eventField->getData();
            if (empty($eventField->getConverter())) {
                unset($eventFieldData[EventField::CONVERTER]);
            }
            $fieldData[$eventField->getName()] = $eventFieldData;
        }
        return $fieldData;
    }

    /**
     *  Merges the existing field configuration with the updated field configuration
     *
     * @param array $baseRules
     * @param array $rulesToMerge
     * @return array
     */
    public function mergeRules(array $baseRules, array $rulesToMerge): array
    {
        return array_values(
            array_replace(
                $this->createMergeableRulesArray($baseRules),
                $this->createMergeableRulesArray($rulesToMerge)
            )
        );
    }

    /**
     * Creates an array of event rule data arrays with keys created using rule field and rule operator values
     *
     * @param array $eventRules
     * @return array
     */
    private function createMergeableRulesArray(array $eventRules): array
    {
        $rulesData = [];
        foreach ($eventRules as $eventRule) {
            $rulesData[
                $eventRule[RuleInterface::RULE_FIELD] . ':' . $eventRule[RuleInterface::RULE_OPERATOR]
            ] = $eventRule;
        }
        return $rulesData;
    }
}
