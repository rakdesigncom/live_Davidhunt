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

namespace Magento\AdobeCommerceEventsClient\Event\Synchronizer;

use Magento\AdobeCommerceEventsClient\Event\Event;
use Magento\AdobeCommerceEventsClient\Event\EventInitializationException;
use Magento\AdobeCommerceEventsClient\Event\EventList;

/**
 *  Returns a list of subscribed events to synchronize with Adobe I/O
 */
class EventSyncList
{
    /**
     * @param EventList $eventList
     * @param SynchronizerDestinationPool $synchronizerDestinationPool
     */
    public function __construct(
        private readonly EventList $eventList,
        private readonly SynchronizerDestinationPool $synchronizerDestinationPool
    ) {
    }

    /**
     * Returns a list of event codes for events that need to be synced.
     *
     * Filters events whose destination is not registered for synchronization.
     *
     * @return Event[]
     * @throws EventInitializationException
     */
    public function getList(): array
    {
        return array_filter(
            $this->eventList->getAll(),
            fn(Event $event) => in_array($event->getDestination(), $this->synchronizerDestinationPool->getList())
        );
    }
}
