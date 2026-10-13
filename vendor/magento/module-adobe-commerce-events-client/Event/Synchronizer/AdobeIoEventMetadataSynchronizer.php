<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Event\Synchronizer;

use Exception;
use Magento\AdobeCommerceEventsClient\Event\EventInitializationException;
use Psr\Log\LoggerInterface;

/**
 * Register events metadata in Adobe I/O.
 */
class AdobeIoEventMetadataSynchronizer
{
    /**
     * @param EventSyncList $eventSyncList
     * @param DefaultProviderRetriever $defaultProviderRetriever
     * @param RegisteredEventsFetcher $registeredEventsFetcher
     * @param SingleEventSynchronizer $singleEventSynchronizer
     * @param LoggerInterface $logger
     */
    public function __construct(
        private EventSyncList $eventSyncList,
        private DefaultProviderRetriever $defaultProviderRetriever,
        private RegisteredEventsFetcher $registeredEventsFetcher,
        private SingleEventSynchronizer $singleEventSynchronizer,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Register events metadata in Adobe I/O.
     *
     * Skips events that have destination that shouldn't be sync with Adobe I/O.
     *
     * @return MetadataSynchronizerResults
     * @throws EventInitializationException
     * @throws SynchronizerException
     */
    public function run(): MetadataSynchronizerResults
    {
        $metadataSyncResults = new MetadataSynchronizerResults();
        $events = $this->eventSyncList->getList();
        if (empty($events)) {
            return $metadataSyncResults;
        }
        $defaultProvider = $this->defaultProviderRetriever->retrieve();
        $registeredEventsFetchResult = $this->registeredEventsFetcher->getRegisteredEvents();

        foreach ($events as $event) {
            try {
                $providerId = $event->getProviderId() ?: $defaultProvider->getId();

                if ($registeredEventsFetchResult->hasProviderFailed($providerId)) {
                    $metadataSyncResults->addFailedEvent($event->getName());
                    continue;
                }

                if (!$registeredEventsFetchResult->hasProviderResult($providerId)) {
                    continue;
                }

                $metadataCreated = $this->singleEventSynchronizer->synchronize(
                    $event,
                    $providerId,
                    $registeredEventsFetchResult->getRegisteredEventsForProvider($providerId),
                    $defaultProvider
                );

                if ($metadataCreated) {
                    $metadataSyncResults->addSuccessMessage(sprintf(
                        'Event metadata was registered for the event "%s" with provider ID "%s"',
                        $event->getName(),
                        $providerId
                    ));
                }
            } catch (Exception $e) {
                $this->logger->error(
                    sprintf(
                        'An error occurred while registering metadata for event "%s". Error: %s',
                        $event->getName(),
                        $e->getMessage()
                    ),
                    ['destination' => ['internal', 'external']]
                );
                $metadataSyncResults->addFailedEvent($event->getName());
            }
        }

        return $metadataSyncResults;
    }
}
