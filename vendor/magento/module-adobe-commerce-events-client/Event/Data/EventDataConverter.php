<?php
/************************************************************************
 *
 * ADOBE CONFIDENTIAL
 * ___________________
 *
 * Copyright 2024 Adobe
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

namespace Magento\AdobeCommerceEventsClient\Event\Data;

use Magento\AdobeCommerceEventsClient\Api\Data\EventDataInterface;
use Magento\AdobeCommerceEventsClient\Api\Data\EventDataInterfaceFactory;
use Magento\AdobeCommerceEventsClient\Api\Data\EventRuleInterface;
use Magento\AdobeCommerceEventsClient\Event\Event;
use Magento\AdobeCommerceEventsClient\Event\EventFactory;
use Magento\AdobeCommerceEventsClient\Event\EventField;

/**
 * Converts @see EventDataInterface data object to the Event object
 */
class EventDataConverter
{
    /**
     * @param EventFactory $eventFactory
     * @param EventDataInterfaceFactory $eventDataFactory
     */
    public function __construct(
        private EventFactory $eventFactory,
        private EventDataInterfaceFactory $eventDataFactory
    ) {
    }

    /**
     * Converts the event data object into event object using event factory.
     *
     * @param EventDataInterface $eventData
     * @return Event
     */
    public function convert(EventDataInterface $eventData): Event
    {
        $eventFields = [];

        foreach ($eventData->getFields() as $field) {
            $eventFields[] = $field->getData();
        }

        return $this->eventFactory->create([
            Event::EVENT_NAME => $eventData->getName(),
            Event::EVENT_PARENT => $eventData->getParent(),
            Event::EVENT_FIELDS => $eventFields,
            Event::EVENT_RULES => array_map(
                fn(EventRuleInterface $eventRule) => $eventRule->getData(),
                $eventData->getRules()
            ),
            Event::EVENT_PRIORITY => $eventData->isPriority(),
            Event::EVENT_DESTINATION => $eventData->getDestination(),
            Event::EVENT_HIPAA_AUDIT_REQUIRED => $eventData->isHipaaAuditRequired(),
            Event::EVENT_PROVIDER_ID => $eventData->getProviderId()
        ]);
    }

    /**
     * Converts an event object into an EventDataInterface object.
     *
     * @param Event $event
     * @return EventDataInterface
     */
    public function convertToEventData(Event $event): EventDataInterface
    {
        return $this->eventDataFactory->create(['data' => [
            Event::EVENT_NAME => $event->getName(),
            Event::EVENT_PARENT => $event->getParent() ?? '',
            Event::EVENT_FIELDS => $this->getFields($event),
            Event::EVENT_RULES => $event->getRules(),
            Event::EVENT_PRIORITY => $event->isPriority(),
            Event::EVENT_DESTINATION => $event->getDestination(),
            Event::EVENT_HIPAA_AUDIT_REQUIRED => $event->isHipaaAuditRequired(),
            Event::EVENT_PROVIDER_ID => $event->getProviderId() ?? Event::EVENT_PROVIDER_DEFAULT
        ]]);
    }

    /**
     * Returns an array of event field data arrays containing field name and field converter, if one is set.
     *
     * @param Event $event
     * @return array
     */
    private function getFields(Event $event): array
    {
        $fields = [];
        foreach ($event->getEventFields() as $eventField) {
            $eventFieldData = $eventField->getData();
            if (empty($eventFieldData[EventField::CONVERTER])) {
                unset($eventFieldData[EventField::CONVERTER]);
            }
            $fields[] = $eventFieldData;
        }
        return $fields;
    }
}
