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

namespace Magento\AdobeCommerceEventsClient\Event\Validator\EventCode;

use Magento\AdobeCommerceEventsClient\Event\Event;
use Magento\AdobeCommerceEventsClient\Event\EventSubscriberInterface;
use Magento\AdobeCommerceEventsClient\Event\Validator\EventValidatorInterface;
use Magento\AdobeCommerceEventsClient\Event\Validator\ValidatorException;

/**
 * Validates that event code has correct prefix
 */
class PrefixValidator implements EventValidatorInterface
{
    /**
     * @inheritDoc
     */
    public function validate(Event $event, bool $force = false): void
    {
        $this->validateEventCode($event->getParent() ?: $event->getName());
    }

    /**
     * Validates that the input event code contains a valid prefix.
     *
     * @param string $eventCode
     * @return void
     * @throws ValidatorException
     */
    private function validateEventCode(string $eventCode) : void
    {
        $eventCodeParts = explode('.', $eventCode, 2);
        if (count($eventCodeParts) === 1) {
            throw new ValidatorException(
                __(
                    'Event code "%1" must consist of a type label and an event code separated by a dot: '
                    . '"<type>.<event_code>". Valid types: %2',
                    $eventCode,
                    implode(', ', EventSubscriberInterface::EVENT_TYPES)
                )
            );
        }

        $prefix = $eventCodeParts[0];
        if (!in_array($prefix, EventSubscriberInterface::EVENT_TYPES)) {
            throw new ValidatorException(
                __(
                    'Invalid event type "%1" in the provided event code "%2". Valid types: %3',
                    $prefix,
                    $eventCode,
                    implode(', ', EventSubscriberInterface::EVENT_TYPES)
                )
            );
        }
    }
}
