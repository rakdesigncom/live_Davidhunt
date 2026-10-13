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
use Magento\AdobeCommerceEventsClient\Event\EventProvider\ValidatorInterface;
use Magento\Framework\Exception\ValidatorException;

/**
 * Validator for event provider's identifier.
 */
class ProviderIdValidator implements ValidatorInterface
{
    /**
     * Validates event provider's provider's identifier.
     *
     * @param EventProviderInterface $eventProvider
     * @param EventProviderInterface[] $allProviders
     * @return void
     * @throws ValidatorException
     */
    public function validate(EventProviderInterface $eventProvider, array $allProviders = []): void
    {
        if (empty($eventProvider->getProviderId())) {
            throw new ValidatorException(__('The event provider ID is required and can not be empty.'));
        }

        if (!$eventProvider->getId()) {
            if (isset($allProviders[$eventProvider->getProviderId()])) {
                throw new ValidatorException(
                    __('The event provider with ID "%1" already exists.', $eventProvider->getProviderId())
                );
            }
        }
    }
}
