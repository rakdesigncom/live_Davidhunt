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

namespace Magento\AdobeCommerceEventsClient\Event\Validator\ProviderId;

use Magento\AdobeCommerceEventsClient\Event\Event;
use Magento\AdobeCommerceEventsClient\Event\EventProvider\EventProviderManagement;
use Magento\AdobeCommerceEventsClient\Event\Validator\EventValidatorInterface;
use Magento\AdobeCommerceEventsClient\Event\Validator\ValidatorException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Validates if the event provider id is correct.
 */
class EventProviderIdValidator implements EventValidatorInterface
{
    /**
     * @param EventProviderManagement $eventProviderManagement
     */
    public function __construct(private EventProviderManagement $eventProviderManagement)
    {
    }

    /**
     * Validates if the event provider id is in the list of registered event providers.
     *
     * Skips validation if the event provider id is empty.
     *
     * @param Event $event
     * @param bool $force
     * @return void
     * @throws ValidatorException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function validate(Event $event, bool $force = false): void
    {
        $providerId = $event->getProviderId();
        if (empty($providerId) || $providerId === Event::EVENT_PROVIDER_DEFAULT) {
            return;
        }

        try {
            $this->eventProviderManagement->getByProviderId($providerId);
        } catch (NoSuchEntityException $e) {
            throw new ValidatorException(__(
                'The event provider id "%1" is not configured. The list of configured event provider ids are: [%2]',
                $providerId,
                implode(', ', array_keys($this->eventProviderManagement->getList()))
            ));
        }
    }
}
