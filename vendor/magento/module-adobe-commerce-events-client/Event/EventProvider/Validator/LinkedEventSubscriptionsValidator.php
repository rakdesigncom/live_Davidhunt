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

namespace Magento\AdobeCommerceEventsClient\Event\EventProvider\Validator;

use Magento\AdobeCommerceEventsClient\Api\Data\EventProviderInterface;
use Magento\AdobeCommerceEventsClient\Event\EventInitializationException;
use Magento\AdobeCommerceEventsClient\Event\EventList;
use Magento\AdobeCommerceEventsClient\Event\EventProvider\ValidatorInterface;
use Magento\Framework\Exception\ValidatorException;

/**
 * Validator to check if the event provider has linked event subscriptions.
 */
class LinkedEventSubscriptionsValidator implements ValidatorInterface
{
    /**
     * @param EventList $eventList
     */
    public function __construct(private readonly EventList $eventList)
    {
    }

    /**
     * Validates if the event provider has linked event subscriptions.
     *
     * @param EventProviderInterface $eventProvider
     * @param EventProviderInterface[] $allProviders
     * @return void
     * @throws ValidatorException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function validate(EventProviderInterface $eventProvider, array $allProviders = []): void
    {
        try {
            $linkedEvents = [];
            foreach ($this->eventList->getAll() as $event) {
                if ($event->getProviderId() === $eventProvider->getProviderId() && $event->isEnabled()) {
                    $linkedEvents[] = $event->getName();
                }
            }

            if (!empty($linkedEvents)) {
                throw new ValidatorException(
                    __(
                        'The event provider has linked event subscriptions: [%1]. ' .
                        'Remove the linked event subscriptions or change their provider IDs ' .
                        'before removing the event provider.',
                        implode(', ', $linkedEvents)
                    )
                );
            }
        } catch (EventInitializationException $e) {
            throw new ValidatorException(
                __('An error occurred while fetching event subscriptions: %1', $e->getMessage())
            );
        }
    }
}
