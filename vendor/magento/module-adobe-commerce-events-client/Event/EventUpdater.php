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

namespace Magento\AdobeCommerceEventsClient\Event;

use Magento\AdobeCommerceEventsClient\Api\Data\EventDataInterface;
use Magento\AdobeCommerceEventsClient\Api\EventUpdaterInterface;
use Magento\AdobeCommerceEventsClient\Event\Data\EventData;
use Magento\AdobeCommerceEventsClient\Event\Data\EventDataConverter;
use Magento\AdobeCommerceEventsClient\Event\Merger\EventMergerInterface;
use Magento\AdobeCommerceEventsClient\Event\Validator\ValidatorException;

/**
 * @inheritDoc
 */
class EventUpdater implements EventUpdaterInterface
{
    /**
     * @param EventSubscriptionUpdaterInterface $eventSubscriptionUpdater
     * @param EventList $eventList
     * @param EventMergerInterface $eventMerger
     * @param EventDataConverter $eventDataConverter
     */
    public function __construct(
        private EventSubscriptionUpdaterInterface $eventSubscriptionUpdater,
        private EventList $eventList,
        private EventMergerInterface $eventMerger,
        private EventDataConverter $eventDataConverter
    ) {
    }

    /**
     * @inheritDoc
     */
    public function update(EventDataInterface $event): void
    {
        try {
            $existingSubscription = $this->eventList->get($event->getName());
        } catch (EventInitializationException $e) {
            throw new ValidatorException(__(
                'Unable to read the list of current event subscriptions: %1',
                $e->getMessage()
            ));
        }

        if ($existingSubscription === null) {
            throw new ValidatorException(__(
                'Unable to update the event subscription. A subscription for %1 does not exist',
                $event->getName()
            ));
        }

        if (!$existingSubscription->isEnabled()) {
            throw new ValidatorException(__(
                'Unable to update the event subscription. The subscription for %1 is not enabled',
                $event->getName()
            ));
        }

        /** @var EventData $event */
        if (!$event->hasData(Event::EVENT_PRIORITY)) {
            $event->setPriority($existingSubscription->isPriority());
        }
        if (!$event->hasData(Event::EVENT_HIPAA_AUDIT_REQUIRED)) {
            $event->setHipaaAuditRequired($existingSubscription->isHipaaAuditRequired());
        }

        $updatedEvent = $this->eventMerger->merge($existingSubscription, $this->eventDataConverter->convert($event));
        $this->eventSubscriptionUpdater->update($updatedEvent);
    }
}
