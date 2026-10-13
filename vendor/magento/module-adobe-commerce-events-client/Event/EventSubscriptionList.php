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

namespace Magento\AdobeCommerceEventsClient\Event;

use Magento\AdobeCommerceEventsClient\Api\EventSubscriptionListInterface;
use Magento\AdobeCommerceEventsClient\Event\Data\EventDataConverter;
use Magento\Framework\Exception\LocalizedException;

/**
 * @inheritDoc
 */
class EventSubscriptionList implements EventSubscriptionListInterface
{
    /**
     * @param EventList $eventList
     * @param EventDataConverter $eventDataConverter
     */
    public function __construct(
        private EventList $eventList,
        private EventDataConverter $eventDataConverter
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getList(): array
    {
        try {
            $eventList = array_filter(
                $this->eventList->getAll(),
                fn (Event $event) => $event->isEnabled()
            );
            return array_map(
                fn (Event $event) => $this->eventDataConverter->convertToEventData($event),
                $eventList
            );
        } catch (EventInitializationException $e) {
            throw new LocalizedException(__('Failed to get event subscriptions. Error: %1', $e->getMessage()));
        }
    }
}
