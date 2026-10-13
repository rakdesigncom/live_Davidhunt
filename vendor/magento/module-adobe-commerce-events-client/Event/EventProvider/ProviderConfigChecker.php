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

namespace Magento\AdobeCommerceEventsClient\Event\EventProvider;

use Magento\AdobeCommerceEventsClient\Event\Event;
use Magento\AdobeIoEventsClient\Model\AdobeIOConfigurationProvider;

/**
 * Checks the event provider configuration
 */
class ProviderConfigChecker
{
    /**
     * @param AdobeIOConfigurationProvider $configurationProvider
     */
    public function __construct(
        private readonly AdobeIOConfigurationProvider $configurationProvider
    ) {
    }

    /**
     * Checks that the input providerId is for a non-default provider or a default provider is configured
     *
     * @param string|null $providerId
     * @return bool
     */
    public function check(?string $providerId = null): bool
    {
        if (!empty($providerId) && $providerId !== Event::EVENT_PROVIDER_DEFAULT) {
            return true;
        }

        return $this->configurationProvider->isConfigured();
    }
}
