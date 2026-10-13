<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Event\Processor;

use Magento\AdobeCommerceEventsClient\Event\DataFilterInterface;
use Magento\AdobeCommerceEventsClient\Event\Event;
use Magento\AdobeCommerceEventsClient\Event\EventInitializationException;
use Magento\AdobeCommerceEventsClient\Event\EventMetadataCollector;
use Magento\AdobeCommerceEventsClient\Event\EventSubscriberInterface;
use Magento\AdobeCommerceEventsClient\Event\Hipaa\HipaaCustomerInterface;
use Magento\AdobeCommerceEventsClient\Event\Metadata\EventMetadataException;
use Magento\AdobeCommerceEventsClient\Model\Event as EventModel;
use Magento\AdobeCommerceEventsClient\Model\EventException;
use Magento\AdobeCommerceEventsClient\Model\EventFactory;
use Magento\Framework\DataObject\IdentityGeneratorInterface;

/**
 * Converts event data to event model
 */
class EventModelFactory implements EventModelFactoryInterface
{
    /**
     * @param EventFactory $eventFactory
     * @param DataFilterInterface $eventDataFilter
     * @param EventMetadataCollector $metadataCollector
     * @param IdentityGeneratorInterface $identityGenerator
     * @param HipaaCustomerInterface $hipaaCustomer
     */
    public function __construct(
        private EventFactory $eventFactory,
        private DataFilterInterface $eventDataFilter,
        private EventMetadataCollector $metadataCollector,
        private IdentityGeneratorInterface $identityGenerator,
        private HipaaCustomerInterface $hipaaCustomer
    ) {
    }

    /**
     * Converts event data to event model.
     *
     * Filters event data according to event registration configuration.
     * Collects and set metadata to the event model.
     *
     * @param Event $event
     * @param array $eventData
     * @return EventModel
     * @throws EventException
     * @throws EventInitializationException
     * @throws EventMetadataException
     */
    public function create(Event $event, array $eventData): EventModel
    {
        $eventCode = EventSubscriberInterface::EVENT_PREFIX_COMMERCE . $event->getName();
        $eventModel = $this->eventFactory->create();
        $eventModel->setTrackId($this->identityGenerator->generateId());
        $eventModel->setEventCode($eventCode);
        $eventModel->setEventData($this->eventDataFilter->filter($eventCode, $eventData));
        $eventModel->setMetadata($this->metadataCollector->getMetadata());
        $eventModel->setPriority((int)$event->isPriority());
        $isHipaaEvent = $event->isHipaaAuditRequired() || $this->hipaaCustomer->isHipaaCustomer();
        $eventModel->setHipaaAuditRequired((int)$isHipaaEvent);

        return $eventModel;
    }
}
