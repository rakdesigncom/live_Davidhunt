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
 * Returns events subscription configuration from the specific source
 */
interface SubscriptionSourceInterface
{
    /**
     * Returns an array of events subscription configuration where keys are used as event name
     *
     * @return array
     * @throws InvalidConfigurationException if event configuration is invalid
     */
    public function getEventSubscriptions(): array;

    /**
     * Returns true if the events subscription from this source is optional
     *
     * @return bool
     */
    public function isOptional(): bool;
}
