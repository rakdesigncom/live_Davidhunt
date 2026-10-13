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

namespace Magento\AdobeCommerceEventsClient\Event\EventProvider\Webapi;

use Magento\AdobeCommerceEventsClient\Api\Data\EventDataInterface;
use Magento\AdobeCommerceEventsClient\Api\Data\EventProviderInterface;
use Magento\AdobeCommerceEventsClient\Event\Event;
use Magento\AdobeIoEventsClient\Model\AdobeIOConfigurationProvider;

/**
 * Replaces the default provider ID with a provider ID from system configuration
 */
class EventDataProviderConverter
{
    /**
     * @param AdobeIOConfigurationProvider $configurationProvider
     */
    public function __construct(private readonly AdobeIOConfigurationProvider $configurationProvider)
    {
    }

    /**
     * Replaces the default provider ID with a provider ID from system configuration.
     *
     * Does not modify the 'default' provider ID value if the provider is not configured in the system configuration.
     *
     * @param EventDataInterface $eventProvider
     * @param array $outputData
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function execute(EventDataInterface $eventProvider, array $outputData): array
    {
        $defaultProvider = $this->configurationProvider->getProvider();

        if ($defaultProvider === null) {
            return $outputData;
        }

        if (empty($outputData[EventProviderInterface::PROVIDER_ID])
            || $outputData[EventProviderInterface::PROVIDER_ID] === Event::EVENT_PROVIDER_DEFAULT
        ) {
            $outputData[EventProviderInterface::PROVIDER_ID] = $defaultProvider->getId();
        }

        return $outputData;
    }
}
