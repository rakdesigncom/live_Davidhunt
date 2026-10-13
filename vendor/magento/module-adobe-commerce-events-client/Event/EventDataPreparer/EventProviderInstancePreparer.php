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

namespace Magento\AdobeCommerceEventsClient\Event\EventDataPreparer;

use Magento\AdobeCommerceEventsClient\Api\EventProviderManagementInterface;
use Magento\AdobeCommerceEventsClient\Event\ClientInterface;
use Magento\AdobeCommerceEventsClient\Event\Event;
use Magento\AdobeCommerceEventsClient\Event\EventInitializationException;
use Magento\AdobeCommerceEventsClient\Event\EventList;

/**
 * Adds instance id to the event data if the event is associated with a custom provider
 */
class EventProviderInstancePreparer implements EventDataPreparerInterface
{
    /**
     * @param EventList $eventList
     * @param EventProviderManagementInterface $eventProviderManagement
     */
    public function __construct(
        private readonly EventList $eventList,
        private readonly EventProviderManagementInterface $eventProviderManagement
    ) {
    }

    /**
     * Adds instance id to the event data if the event is associated with a custom provider
     *
     * @param array $waitingEvents
     * @return array
     */
    public function execute(array $waitingEvents): array
    {
        foreach ($waitingEvents as &$eventData) {
            $eventData[ClientInterface::INSTANCE_ID] = $this->getInstanceId($eventData['eventCode']);
        }

        return $waitingEvents;
    }

    /**
     * Returns instance id for the given event code. Returns null if the event is not associated with a custom provider
     *
     * @param string $eventCode
     * @return string|null
     */
    private function getInstanceId(string $eventCode): ?string
    {
        try {
            $event = $this->eventList->get($eventCode);

            if ($event !== null
                && !empty($event->getProviderId())
                && $event->getProviderId() !== Event::EVENT_PROVIDER_DEFAULT
            ) {
                $providers = $this->eventProviderManagement->getList();
                if (!isset($providers[$event->getProviderId()])) {
                    return null;
                }

                return $providers[$event->getProviderId()]->getInstanceId();
            }
        } catch (EventInitializationException $e) {
            return null;
        }

        return null;
    }
}
