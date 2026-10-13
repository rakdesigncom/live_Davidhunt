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

namespace Magento\AdobeCommerceEventsClient\Event\Validator;

use Magento\AdobeCommerceEventsClient\Event\Event;

/**
 * Validates an Event the represents an update to a subscription
 */
class UpdateEventValidator implements EventValidatorInterface
{
    /**
     * Validates that the Event does not represent an update for a subscription in an io_events.xml file
     *
     * @param Event $event
     * @param bool $force
     * @throws ValidatorException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function validate(Event $event, bool $force = false): void
    {
        if ($event->isXmlDefined()) {
            throw new ValidatorException(__(
                'Unable to update the event as it is registered in an io_events.xml file. ' .
                'Events registered in io_events.xml files are required and can not be modified.'
            ));
        }
    }
}
