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

use Magento\AdobeCommerceEventsClient\Api\Data\EventDataInterface;
use Magento\AdobeCommerceEventsClient\Api\EventSubscriberInterface;
use Magento\AdobeCommerceEventsClient\Event\Data\EventDataConverter;
use Magento\AdobeCommerceEventsClient\Event\EventProvider\ProviderConfigChecker;
use Magento\AdobeCommerceEventsClient\Event\Validator\ValidatorException;
use Magento\AdobeCommerceEventsClient\Event\EventSubscriberInterface as EventSubscriber;

/**
 * @inheritDoc
 */
class EventSubscribe implements EventSubscriberInterface
{
    /**
     * @param EventDataConverter $eventDataConverter
     * @param EventSubscriber $eventSubscriber
     * @param EventList $eventList
     * @param ProviderConfigChecker $providerConfigChecker
     */
    public function __construct(
        private EventDataConverter $eventDataConverter,
        private EventSubscriber $eventSubscriber,
        private EventList $eventList,
        private ProviderConfigChecker $providerConfigChecker
    ) {
    }

    /**
     * @inheritDoc
     */
    public function subscribe(EventDataInterface $event, bool $force = false): void
    {
        if (!$this->providerConfigChecker->check($event->getProviderId())) {
            throw new ValidatorException(
                __('No event provider is configured. Please configure the event provider before subscribing to events.')
            );
        }

        $this->eventSubscriber->subscribe($this->eventDataConverter->convert($event), $force);
    }

    /**
     * @inheritDoc
     */
    public function unsubscribe(string $name): void
    {
        try {
            $event = $this->eventList->get(strtolower($name));
        } catch (EventInitializationException $e) {
            throw new ValidatorException(
                __('The "%1" event subscription could not be read. Error: %2', $name, $e->getMessage())
            );
        }

        if ($event === null) {
            throw new ValidatorException(
                __('The "%1" event is not registered. You cannot unsubscribe from it.', $name)
            );
        }

        if (!$this->providerConfigChecker->check($event->getProviderId())) {
            throw new ValidatorException(
                __('No event provider is configured. Please configure the event provider before unsubscribing ' .
                    'from events.')
            );
        }

        $this->eventSubscriber->unsubscribe($event);
    }
}
