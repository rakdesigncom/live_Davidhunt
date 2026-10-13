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

namespace Magento\AdobeCommerceEventsClient\Config\SubscriptionSource;

use Magento\AdobeCommerceEventsClient\Config\Reader;
use Magento\AdobeCommerceEventsClient\Config\SubscriptionSourceInterface;

/**
 * Returns event subscriptions configuration from XML files
 */
class XmlConfiguration implements SubscriptionSourceInterface
{
    /**
     * @param Reader $reader
     */
    public function __construct(private Reader $reader)
    {
    }

    /**
     * Returns event subscriptions configuration from io_events.xml files
     *
     * @return array
     */
    public function getEventSubscriptions(): array
    {
        return $this->reader->read();
    }

    /**
     * @inheritDoc
     */
    public function isOptional(): bool
    {
        return false;
    }
}
