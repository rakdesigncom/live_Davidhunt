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

use Magento\AdobeIoEventsClient\Api\EventProviderInterface;
use Magento\AdobeIoEventsClient\Console\CreateEventProvider;
use Magento\AdobeIoEventsClient\Model\AdobeIOConfigurationProvider;

/**
 * Retrieves the default event provider for event metadata synchronization.
 */
class DefaultProviderRetriever
{
    /**
     * @param AdobeIOConfigurationProvider $configurationProvider
     */
    public function __construct(
        private readonly AdobeIOConfigurationProvider $configurationProvider,
    ) {
    }

    /**
     * Retrieves the default event provider for event metadata synchronization.
     *
     * Throws an exception if no default event provider is configured.
     *
     * @return EventProviderInterface
     * @throws SynchronizerException
     */
    public function retrieve(): EventProviderInterface
    {
        $provider = $this->configurationProvider->getProvider();
        if ($provider === null) {
            $error = 'A default event provider is not configured in the system configuration.';
            if (php_sapi_name() === 'cli') {
                $error .= sprintf(
                    ' Run bin/magento %s to configure a default event provider.',
                    CreateEventProvider::COMMAND_NAME
                );
            }
            throw new SynchronizerException(__($error));
        }
        return $provider;
    }
}
