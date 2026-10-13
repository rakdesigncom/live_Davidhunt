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

use Exception;
use Magento\AdobeCommerceEventsClient\Event\AdobeIoEventMetadata\ProviderNotConfiguredSubscriberException;
use Magento\AdobeCommerceEventsClient\Event\AdobeIoEventMetadata\SubscriberException;
use Magento\AdobeCommerceEventsClient\Event\AdobeIoEventMetadata\SubscriberInterface;
use Magento\AdobeCommerceEventsClient\Event\Validator\EventValidatorInterface;
use Magento\AdobeCommerceEventsClient\Event\Validator\ValidatorException;
use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\App\DeploymentConfig\Writer;
use Magento\Framework\Config\File\ConfigFilePool;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\RuntimeException;
use Psr\Log\LoggerInterface;

/**
 * @inheritDoc
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class EventSubscriber implements EventSubscriberInterface, EventSubscriptionUpdaterInterface
{
    private const ERROR_MISMATCH_ORG_IDS = 'IMS Organization Look up from AMS failed';

    /**
     * @param Writer $configWriter
     * @param DeploymentConfig $deploymentConfig
     * @param EventValidatorInterface $subscribeValidator
     * @param EventValidatorInterface $unsubscribeValidator
     * @param SubscriberInterface $ioMetadataSubscriber
     * @param EventList $eventList
     * @param LoggerInterface $logger
     * @param EventValidatorInterface $updateValidator
     */
    public function __construct(
        private Writer $configWriter,
        private DeploymentConfig $deploymentConfig,
        private EventValidatorInterface $subscribeValidator,
        private EventValidatorInterface $unsubscribeValidator,
        private SubscriberInterface $ioMetadataSubscriber,
        private EventList $eventList,
        private LoggerInterface $logger,
        private EventValidatorInterface $updateValidator
    ) {
    }

    /**
     * @inheritDoc
     */
    public function subscribe(Event $event, bool $force = false): void
    {
        $this->subscribeValidator->validate($event, $force);

        try {
            $this->addSubscriptionToConfig($event);
            $this->eventList->reset();
            $this->ioMetadataSubscriber->create($event);
            $this->logger->info(
                sprintf('Event subscription %s was added', $event->getName()),
                ['event' => $event, 'destination' => ['internal', 'external']]
            );
        } catch (ProviderNotConfiguredSubscriberException $e) {
            throw new ValidatorException(
                __(
                    'The event subscription has been saved, but an event provider to link event metadata to ' .
                    'was not found. Configure a provider and synchronize the event metadata.'
                ),
            );
        } catch (SubscriberException $e) {
            throw new ValidatorException(__($this->getSubscribeErrorMessage($e->getMessage()), $e->getMessage()));
        } catch (Exception $e) {
            throw new ValidatorException(__($e->getMessage()), $e, $e->getCode());
        }
    }

    /**
     * @inheritDoc
     */
    public function unsubscribe(Event $event): void
    {
        $this->unsubscribeValidator->validate($event);

        try {
            $this->unsubscribeFromEvent($event);
            $this->eventList->reset();
            $this->ioMetadataSubscriber->delete($event);
            $this->logger->info(
                sprintf('Subscription to event %s was removed', $event->getName()),
                ['event' => $event, 'destination' => ['internal', 'external']]
            );
        } catch (ProviderNotConfiguredSubscriberException $e) {
            throw new ValidatorException(
                __(
                    'The event subscription was removed without deleting event metadata, as a configured ' .
                    'event provider was not found.'
                )
            );
        } catch (SubscriberException $e) {
            throw new ValidatorException(
                __(
                    'The event subscription was removed, but event metadata cannot be deleted or it does not exist: ' .
                    $e->getMessage()
                )
            );
        } catch (Exception $e) {
            throw new ValidatorException(__($e->getMessage()), $e, $e->getCode());
        }
    }

    /**
     * @inheritDoc
     */
    public function update(Event $event): void
    {
        $this->updateValidator->validate($event);

        try {
            $this->addSubscriptionToConfig($event);
            $this->eventList->reset();

            $this->logger->info(
                sprintf('Event subscription %s was updated', $event->getName()),
                ['event' => $event, 'destination' => ['internal', 'external']]
            );
        } catch (Exception $e) {
            throw new ValidatorException(__($e->getMessage()), $e, $e->getCode());
        }
    }

    /**
     * Adds the subscription to the configuration
     *
     * @param Event $event
     * @return void
     * @throws FileSystemException
     * @throws RuntimeException
     */
    private function addSubscriptionToConfig(Event $event): void
    {
        $ioEvents = $this->deploymentConfig->get(self::IO_EVENTS_CONFIG_NAME, []);

        $ioEvents[$event->getName()] = $this->convertEventToConfig($event);
        $this->configWriter->saveConfig(
            [
                ConfigFilePool::APP_ENV => [
                    self::IO_EVENTS_CONFIG_NAME => $ioEvents
                ]
            ],
            true
        );
    }

    /**
     * Converts Event object to the configuration array
     *
     * @param Event $event
     * @return array
     */
    private function convertEventToConfig(Event $event): array
    {
        $eventData = [
            Event::EVENT_FIELDS => $this->convertFields($event),
            Event::EVENT_ENABLED => 1
        ];
        if (!empty($event->getRules())) {
            $eventData[Event::EVENT_RULES] = $event->getRules();
        }
        if (!empty($event->getParent())) {
            $eventData[Event::EVENT_PARENT] = $event->getParent();
        }
        if ($event->isPriority()) {
            $eventData[Event::EVENT_PRIORITY] = 1;
        }
        if ($event->isHipaaAuditRequired()) {
            $eventData[Event::EVENT_HIPAA_AUDIT_REQUIRED] = 1;
        }
        if (!empty($event->getDestination()) && $event->getDestination() !== Event::DESTINATION_DEFAULT) {
            $eventData[Event::EVENT_DESTINATION] = $event->getDestination();
        }
        if ($event->getProviderId()) {
            $eventData[Event::EVENT_PROVIDER_ID] = $event->getProviderId();
        }

        return $eventData;
    }

    /**
     * Converts event fields of an Event object to the config representation.
     *
     * @param Event $event
     * @return array
     */
    private function convertFields(Event $event): array
    {
        $eventFields = $event->getEventFields();
        $fields = [];
        foreach ($eventFields as $eventField) {
            $fieldOptionsArray = $eventField->toArray();
            $fields[] = count($fieldOptionsArray) <= 1 ? $eventField->getName() : $fieldOptionsArray;
        }
        return $fields;
    }

    /**
     * Returns an error message for the SubscriberException during event subscription.
     *
     * @param string $exceptionMessage
     * @return string
     */
    private function getSubscribeErrorMessage(string $exceptionMessage): string
    {
        $message = 'The event subscription has been saved, but event metadata has not been created.';
        $message .= str_contains($exceptionMessage, self::ERROR_MISMATCH_ORG_IDS)
            ? ' Check that the workspace configurations use the same IMS organization ID.'
            : ' Check your configuration and synchronize the event metadata.';

        return $message .  ' Event metadata creation error: %1';
    }

    /**
     * Allows to unsubscribe from event ignoring the case of the event name.
     *
     * For example, CaseSensitive.observer.customer_save_commit_after could be set as an alias name.
     *
     * @param Event $event
     * @return void
     * @throws FileSystemException
     * @throws RuntimeException
     */
    private function unsubscribeFromEvent(Event $event): void
    {
        $ioEvents = $this->deploymentConfig->get(self::IO_EVENTS_CONFIG_NAME, []);
        foreach ($ioEvents as $eventName => &$eventData) {
            if (strcasecmp($eventName, $event->getName()) === 0) {
                $eventData[Event::EVENT_ENABLED] = 0;
            }
        }

        $this->configWriter->saveConfig(
            [
                ConfigFilePool::APP_ENV => [
                    self::IO_EVENTS_CONFIG_NAME => $ioEvents
                ]
            ],
            true
        );
    }
}
