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

namespace Magento\AdobeCommerceEventsClient\Event\EventInfo;

use Magento\AdobeCommerceEventsClient\Event\Event;

/**
 * @inheritDoc
 */
class PredefinedEventInfoComposite implements PredefinedEventInfoInterface
{
    /**
     * @param PredefinedEventInfoInterface[] $predefinedEventInfoList
     */
    public function __construct(private array $predefinedEventInfoList = [])
    {
    }

    /**
     * @inheritDoc
     */
    public function get(Event $event): ?array
    {
        foreach ($this->predefinedEventInfoList as $predefinedEventInfo) {
            $eventInfo = $predefinedEventInfo->get($event);
            if ($eventInfo !== null) {
                return $eventInfo;
            }
        }
        return null;
    }
}
