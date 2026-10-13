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

/**
 * Class representing the results of event metadata synchronization.
 */
class MetadataSynchronizerResults
{
    /**
     * @param array $successMessages
     * @param array $failedEvents
     */
    public function __construct(
        private array $successMessages = [],
        private array $failedEvents = []
    ) {
    }

    /**
     * Returns the list of success messages.
     *
     * @return array
     */
    public function getSuccessMessages(): array
    {
        return $this->successMessages;
    }

    /**
     * Returns the list of failed events.
     *
     * @return array
     */
    public function getFailedEvents(): array
    {
        return $this->failedEvents;
    }

    /**
     * Adds an event to the list of failed events.
     *
     * @param string $eventName
     * @return void
     */
    public function addFailedEvent(string $eventName): void
    {
        $this->failedEvents[] = $eventName;
    }

    /**
     * Adds a message to the list of success messages.
     *
     * @param string $message
     * @return void
     */
    public function addSuccessMessage(string $message): void
    {
        $this->successMessages[] = $message;
    }
}
