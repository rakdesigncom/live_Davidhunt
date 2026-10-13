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

namespace Magento\AdobeCommerceEventsClient\Event\Validator;

use Magento\AdobeCommerceEventsClient\Event\Event;

/**
 * Validation that Event has at least one field configured with a name attribute.
 */
class FieldsConfiguredValidator implements EventValidatorInterface
{
    /**
     * Validates that Event has at least one field configured with a name attribute.
     *
     * @param Event $event
     * @param bool $force
     * @throws ValidatorException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function validate(Event $event, bool $force = false): void
    {
        $eventFields = $event->getEventFields();

        if (empty($eventFields)) {
            throw new ValidatorException(__('At least one field must be configured for the event.'));
        }

        foreach ($eventFields as $field) {
            if (!$field->hasData(Event::EVENT_NAME)) {
                throw new ValidatorException(
                    __('Each field must have a name attribute.')
                );
            }
        }
    }
}
