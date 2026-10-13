<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Event;

use Magento\AdobeCommerceEventsClient\Api\Data\EventInterface;
use Magento\AdobeCommerceEventsClient\Event\EventDataPreparer\EventDataPreparerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Lock\LockManagerInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Class for sending event data in batches to the configured events service
 */
class EventBatchSender
{
    private const LOCK_NAME = 'event_batch_sender';
    private const LOCK_TIMEOUT = 60;

    private const EVENT_COUNT_PER_ITERATION = 1000;

    /**
     * @param LockManagerInterface $lockManager
     * @param ClientInterface $client
     * @param EventRetrieverInterface $eventRetriever
     * @param EventStatusUpdater $statusUpdater
     * @param LoggerInterface $logger
     * @param EventResponseHandlerInterface $eventResponseHandler
     * @param string $lockName
     * @param EventDataPreparerInterface[] $eventDataPreparers
     */
    public function __construct(
        private LockManagerInterface $lockManager,
        private ClientInterface $client,
        private EventRetrieverInterface $eventRetriever,
        private EventStatusUpdater $statusUpdater,
        private LoggerInterface $logger,
        private EventResponseHandlerInterface $eventResponseHandler,
        private string $lockName = self::LOCK_NAME,
        private array $eventDataPreparers = []
    ) {
    }

    /**
     * Sends events data in batches.
     *
     * Reads stored event data waiting to be sent, sends the data in batches to the Events Service, and updates stored
     * events based on the success or failure of sending the data.
     * Added locking mechanism to avoid the situation when two processes trying to process the same events.
     * Events are processed in iterations, during each iteration up to self::EVENT_COUNT_PER_ITERATION events
     * are selected.
     *
     * @return void
     * @throws LocalizedException
     */
    public function sendEventDataBatches(): void
    {
        do {
            $didLock = false;
            $waitingEvents = [];
            try {
                if (!$this->lockManager->lock($this->lockName, self::LOCK_TIMEOUT)) {
                    return;
                }

                $didLock = true;
                $waitingEvents = $this->eventRetriever->getEventsWithLimit(self::EVENT_COUNT_PER_ITERATION);
                $this->statusUpdater->updateStatus(
                    array_keys($waitingEvents),
                    EventInterface::SENDING_STATUS
                );
            } finally {
                if ($didLock) {
                    $this->lockManager->unlock(self::LOCK_NAME);
                }
            }

            $waitingEventsCount = count($waitingEvents);
            if ($waitingEventsCount) {
                $this->processEvents($waitingEvents);
            }
        } while ($waitingEventsCount === self::EVENT_COUNT_PER_ITERATION);
    }

    /**
     * Processes array of events.
     *
     * Splits events into batches before sending.
     *
     * @param array $waitingEvents
     * @return void
     */
    private function processEvents(array $waitingEvents): void
    {
        try {
            foreach ($this->eventDataPreparers as $preparer) {
                $waitingEvents = $preparer->execute($waitingEvents);
            }
        } catch (EventInitializationException $e) {
            $this->logger->info(sprintf(
                'Unable to apply processors due to error during event configuration initialization: %s.',
                $e->getMessage()
            ));
        }
        $eventIds = array_keys($waitingEvents);
        $eventData = array_values($waitingEvents);

        try {
            $response = $this->client->sendEventDataBatch($eventData);
            $this->eventResponseHandler->handle($response, $eventIds);
        } catch (Throwable $exception) {
            $this->statusUpdater->setFailure($eventIds, $exception->getMessage());
        }
    }
}
