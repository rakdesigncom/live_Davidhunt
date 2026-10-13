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

namespace Magento\AdobeCommerceEventsClient\Event\EventStorageWriter;

use Magento\AdobeCommerceEventsClient\Event\Config;
use Magento\AdobeCommerceEventsClient\Event\Event;
use Magento\Framework\Serialize\Serializer\Json;
use Psr\Log\LoggerInterface;
use InvalidArgumentException;

/**
 * Validates that event data size does not exceed maximum allowed limit
 */
class EventDataSizeValidator
{
    /**
     * @param LoggerInterface $logger
     * @param Config $config
     * @param Json $json
     */
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly Config $config,
        private readonly Json $json
    ) {
    }

    /**
     * Validates event data size
     *
     * @param Event $event
     * @param array $eventData
     * @return bool
     */
    public function validate(Event $event, array $eventData): bool
    {
        try {
            $eventDataSize = strlen($this->json->serialize($eventData));
            $maxEventDataSize = $this->config->getMaxEventDataSize();
            if ($eventDataSize > $maxEventDataSize) {
                $this->logger->error(
                    sprintf(
                        'Event data size for the event "%s" exceeds maximum allowed size of %d bytes. ' .
                        'Actual size: %d bytes.',
                        $event->getName(),
                        $maxEventDataSize,
                        $eventDataSize
                    ),
                    ['destination' => ['internal', 'external']]
                );
                return false;
            }
        } catch (InvalidArgumentException $e) {
            $this->logger->error(
                sprintf(
                    'Failed to serialize event data for the event "%s" for size validation: %s',
                    $event->getName(),
                    $e->getMessage()
                ),
                ['destination' => ['internal', 'external']]
            );
            return false;
        }

        return true;
    }
}
