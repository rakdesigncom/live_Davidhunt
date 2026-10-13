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

namespace Magento\AdobeCommerceEventsClient\Config;

use Magento\AdobeCommerceEventsClient\Event\InvalidConfigurationException;

/**
 * Returns merged configuration for Subscription sources
 */
class SubscriptionLoader
{
    /**
     * @param SubscriptionSourcePool $subscriptionSourcePool
     */
    public function __construct(private SubscriptionSourcePool $subscriptionSourcePool)
    {
    }

    /**
     * Returns an array of merged events subscription from all sources
     *
     * @return array
     * @throws InvalidConfigurationException
     */
    public function getEventSubscriptions(): array
    {
        $eventSubscriptionData = [];

        foreach ($this->subscriptionSourcePool->getSources() as $source) {
            foreach ($source->getEventSubscriptions() as $eventName => $eventData) {
                $eventName = strtolower($eventName);
                if (isset($eventSubscriptionData[$eventName]) && $source->isOptional()) {
                    continue;
                }

                if (isset($eventSubscriptionData[$eventName])) {
                    $eventSubscriptionData[$eventName] = array_replace_recursive(
                        $eventSubscriptionData[$eventName],
                        $eventData
                    );
                } else {
                    $eventSubscriptionData[$eventName] = $eventData;
                }
            }
        }

        return $eventSubscriptionData;
    }
}
