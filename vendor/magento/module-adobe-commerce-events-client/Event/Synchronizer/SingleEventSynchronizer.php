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

use Exception;
use Magento\AdobeCommerceEventsClient\Event\AdobeIoEventMetadataFactory;
use Magento\AdobeCommerceEventsClient\Event\Event;
use Magento\AdobeCommerceEventsClient\Event\EventSubscriberInterface;
use Magento\AdobeCommerceEventsClient\Event\ProviderConfigurationBuilder;
use Magento\AdobeIoEventsClient\Api\EventProviderInterface;
use Magento\AdobeIoEventsClient\Model\Data\EventProviderFactory;
use Magento\AdobeIoEventsClient\Model\EventMetadataClient;

/**
 * Synchronizes metadata with Adobe I/O for a single event at a time
 */
class SingleEventSynchronizer
{
    /**
     * @param EventMetadataClient $metadataClient
     * @param AdobeIoEventMetadataFactory $ioMetadataFactory
     * @param EventProviderFactory $eventProviderFactory
     * @param ProviderConfigurationBuilder $providerConfigurationBuilder
     */
    public function __construct(
        private EventMetadataClient $metadataClient,
        private AdobeIoEventMetadataFactory $ioMetadataFactory,
        private EventProviderFactory $eventProviderFactory,
        private ProviderConfigurationBuilder $providerConfigurationBuilder,
    ) {
    }

    /**
     * Synchronizes metadata with Adobe I/O for a single event
     *
     * @param Event $event
     * @param string $providerId
     * @param array $providerEvents
     * @param EventProviderInterface $defaultProvider
     * @return bool indicating if metadata for the event was created
     * @throws SynchronizerException
     */
    public function synchronize(
        Event $event,
        string $providerId,
        array $providerEvents,
        EventProviderInterface $defaultProvider
    ): bool {
        $configuration = null;
        $eventCode = EventSubscriberInterface::EVENT_PREFIX_COMMERCE . $event->getName();

        if (in_array($eventCode, $providerEvents)) {
            return false;
        }

        try {
            if ($providerId !== $defaultProvider->getId()) {
                $configuration = $this->providerConfigurationBuilder->build($providerId);
                if ($configuration === null) {
                    return false;
                }
                $provider = $this->eventProviderFactory->create(['data' => ['id' => $providerId]]);
            } else {
                $provider = $defaultProvider;
            }

            $this->metadataClient->createEventMetadata(
                $provider,
                $this->ioMetadataFactory->generate($eventCode),
                $configuration
            );
        } catch (Exception $e) {
            throw new SynchronizerException(__($e->getMessage()), $e);
        }
        return true;
    }
}
