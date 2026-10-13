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
use Magento\AdobeCommerceEventsClient\Event\EventProvider\EventProviderManagement;
use Magento\AdobeIoEventsClient\Model\Data\EventProviderFactory;
use Magento\AdobeIoEventsClient\Model\EventMetadataClient;
use Psr\Log\LoggerInterface;

/**
 * Fetches events with metadata registered in Adobe I/O
 */
class RegisteredEventsFetcher
{
    /**
     * @param EventMetadataClient $metadataClient
     * @param EventProviderManagement $eventProviderManagement
     * @param EventProviderFactory $eventProviderFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly EventMetadataClient $metadataClient,
        private readonly EventProviderManagement $eventProviderManagement,
        private readonly EventProviderFactory $eventProviderFactory,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Fetches the list of registered events for each provider configured in the Commerce instance
     *
     * @return RegisteredEventsFetchResult
     */
    public function getRegisteredEvents(): RegisteredEventsFetchResult
    {
        $registeredEventsFetchResult = new RegisteredEventsFetchResult();
        foreach ($this->eventProviderManagement->getList() as $provider) {
            if (empty($provider->getWorkspaceConfiguration())) {
                continue;
            }

            try {
                $registeredEventMetadata = $this->metadataClient->listRegisteredEventMetadata(
                    $this->eventProviderFactory->create(['data' => ['id' => $provider->getProviderId()]])
                );

                $registeredEventsFetchResult->addRegisteredEventsForProvider(
                    $provider->getProviderId(),
                    array_map(
                        fn($eventMetadata) => $eventMetadata->getEventCode(),
                        $registeredEventMetadata
                    )
                );
            } catch (Exception $e) {
                $this->logger->error(sprintf(
                    'An error occurred while fetching previously registered events for provider with ' .
                    'id "%s". Error: %s',
                    $provider->getProviderId(),
                    $e->getMessage()
                ));
                $registeredEventsFetchResult->addFailedProvider($provider->getProviderId());
            }
        }
        return $registeredEventsFetchResult;
    }
}
