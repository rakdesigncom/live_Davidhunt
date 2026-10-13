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

namespace Magento\AdobeCommerceEventsClient\Event\Validator\Destination;

use Magento\AdobeCommerceEventsClient\Event\Event;
use Magento\AdobeCommerceEventsClient\Event\EventStorageWriter\EventDestinationResolver;
use Magento\AdobeCommerceEventsClient\Event\Validator\EventValidatorInterface;
use Magento\AdobeCommerceEventsClient\Event\Validator\ValidatorException;

/**
 * Validates if the event destination is correct
 */
class EventDestinationValidator implements EventValidatorInterface
{
    /**
     * @param EventDestinationResolver $destinationResolver
     */
    public function __construct(private EventDestinationResolver $destinationResolver)
    {
    }

    /**
     * Validates if the event destination is in the list of registered destinations.
     *
     * Skips validation if the destination is the default or empty or the force argument is set to true.
     *
     * @param Event $event
     * @param bool $force
     * @return void
     * @throws ValidatorException
     */
    public function validate(Event $event, bool $force = false): void
    {
        if ($force) {
            return;
        }

        $destination = $event->getDestination();
        if (empty($destination) || $destination === Event::DESTINATION_DEFAULT) {
            return;
        }

        $registeredDestinations = $this->destinationResolver->getDestinations();
        if (!in_array($destination, $registeredDestinations)) {
            throw new ValidatorException(__(
                'The destination "%1" is not registered. The list of registered destinations: [%2]',
                $destination,
                implode(', ', $registeredDestinations)
            ));
        }
    }
}
