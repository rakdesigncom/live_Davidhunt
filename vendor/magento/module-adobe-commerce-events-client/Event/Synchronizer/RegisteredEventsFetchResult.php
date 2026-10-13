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
 * Class representing the results of fetching registered event metadata.
 */
class RegisteredEventsFetchResult
{
    /**
     * @param array $registeredEventsByProvider
     * @param array $failedProviders
     */
    public function __construct(
        private array $registeredEventsByProvider = [],
        private array $failedProviders = []
    ) {
    }

    /**
     * Checks if registered metadata for the specified provider has been fetched
     *
     * @param string $providerId
     * @return bool
     */
    public function hasProviderResult(string $providerId): bool
    {
        return array_key_exists($providerId, $this->registeredEventsByProvider);
    }

    /**
     * Stores the registered event codes for the specified provider
     *
     * @param string $providerId
     * @param array $eventCodes
     * @return void
     */
    public function addRegisteredEventsForProvider(string $providerId, array $eventCodes): void
    {
        $this->registeredEventsByProvider[$providerId] = $eventCodes;
    }

    /**
     * Returns the list of registered event codes stored for the provider
     *
     * @param string $providerId
     * @return array
     */
    public function getRegisteredEventsForProvider(string $providerId): array
    {
        return $this->registeredEventsByProvider[$providerId] ?? [];
    }

    /**
     * Adds the specified provider to the list of providers for which fetching metadata failed
     *
     * @param string $providerId
     * @return void
     */
    public function addFailedProvider(string $providerId): void
    {
        $this->failedProviders[] = $providerId;
    }

    /**
     * Checks if fetching metadata for the specified provider failed
     *
     * @param string $providerId
     * @return bool
     */
    public function hasProviderFailed(string $providerId): bool
    {
        return in_array($providerId, $this->failedProviders);
    }
}
