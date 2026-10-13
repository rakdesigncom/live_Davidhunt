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

namespace Magento\AdobeCommerceEventsClient\Event;

/**
 * Removes the Commerce prefix from event names.
 */
class CommercePrefixRemover
{
    /**
     * Removes the result of removing Commerce prefix from the input event name.
     *
     * @param string $eventName
     * @return string
     */
    public function removePrefix(string $eventName): string
    {
        return str_replace(EventSubscriberInterface::EVENT_PREFIX_COMMERCE, '', $eventName);
    }
}
