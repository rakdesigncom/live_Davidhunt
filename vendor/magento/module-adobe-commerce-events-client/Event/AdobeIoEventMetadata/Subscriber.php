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

namespace Magento\AdobeCommerceEventsClient\Event\AdobeIoEventMetadata;

use Exception;
use Magento\AdobeCommerceEventsClient\Event\AdobeIoEventMetadataFactory;
use Magento\AdobeCommerceEventsClient\Event\Event;
use Magento\AdobeCommerceEventsClient\Event\EventSubscriberInterface;
use Magento\AdobeCommerceEventsClient\Event\ProviderConfigurationBuilder;
use Magento\AdobeIoEventsClient\Exception\InvalidConfigurationException;
use Magento\AdobeIoEventsClient\Model\AdobeIOConfigurationProvider;
use Magento\AdobeIoEventsClient\Model\Data\EventProviderFactory;
use Magento\AdobeIoEventsClient\Model\EventMetadataClient;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Creates and deletes event metadata for the provided event
 */
class Subscriber implements SubscriberInterface
{
    /**
     * @param AdobeIOConfigurationProvider $configurationProvider
     * @param AdobeIoEventMetadataFactory $eventMetadataFactory
     * @param EventMetadataClient $metadataClient
     * @param EventProviderFactory $eventProviderFactory
     * @param ProviderConfigurationBuilder $providerConfigurationBuilder
     */
    public function __construct(
        private AdobeIOConfigurationProvider $configurationProvider,
        private AdobeIoEventMetadataFactory $eventMetadataFactory,
        private EventMetadataClient $metadataClient,
        private EventProviderFactory $eventProviderFactory,
        private ProviderConfigurationBuilder $providerConfigurationBuilder
    ) {
    }

    /**
     * @inheritDoc
     */
    public function create(Event $event): bool
    {
        [$provider, $configuration] = $this->getProviderAndConfiguration($event);

        if ($provider === null) {
            return false;
        }

        try {
            $this->metadataClient->createEventMetadata(
                $provider,
                $this->eventMetadataFactory->generate(
                    EventSubscriberInterface::EVENT_PREFIX_COMMERCE . $event->getName()
                ),
                $configuration
            );
            return true;
        } catch (Exception $e) {
            throw new SubscriberException(__($e->getMessage()), $e);
        }
    }

    /**
     * @inheritDoc
     */
    public function delete(Event $event): bool
    {
        [$provider, $configuration] = $this->getProviderAndConfiguration($event);

        if ($provider === null) {
            return false;
        }

        try {
            return $this->metadataClient->deleteEventMetadata(
                $provider,
                $this->eventMetadataFactory->generate(
                    EventSubscriberInterface::EVENT_PREFIX_COMMERCE . $event->getName()
                ),
                $configuration
            );
        } catch (Exception $e) {
            throw new SubscriberException(__($e->getMessage()), $e);
        }
    }

    /**
     * Returns provider and configuration for the event
     *
     * @param Event $event
     * @return array
     * @throws ProviderNotConfiguredSubscriberException
     * @throws SubscriberException
     */
    private function getProviderAndConfiguration(Event $event): array
    {
        $providerId = $event->getProviderId();
        $configuration = null;

        if (!empty($providerId)) {
            try {
                $configuration = $this->providerConfigurationBuilder->build($providerId);
                if ($configuration === null) {
                    return [null, null];
                }
                $provider = $this->eventProviderFactory->create(['data' => ['id' => $providerId]]);
            } catch (NoSuchEntityException|InvalidConfigurationException $e) {
                throw new SubscriberException(__(sprintf(
                    'Error creating event metadata for provider with ID %s. Error: %s',
                    $providerId,
                    $e->getMessage()
                )));
            }
        } else {
            $provider = $this->configurationProvider->getProvider();
            if ($provider === null) {
                throw new ProviderNotConfiguredSubscriberException(__('An event provider is not configured'));
            }
        }

        return [$provider, $configuration];
    }
}
