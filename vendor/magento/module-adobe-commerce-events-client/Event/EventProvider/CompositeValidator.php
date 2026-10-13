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

namespace Magento\AdobeCommerceEventsClient\Event\EventProvider;

use Magento\AdobeCommerceEventsClient\Api\Data\EventProviderInterface;

/**
 * Composite validator of event providers
 */
class CompositeValidator implements ValidatorInterface
{
    /**
     * @param ValidatorInterface[] $validators
     */
    public function __construct(private readonly array $validators = [])
    {
    }

    /**
     * @inheritDoc
     */
    public function validate(EventProviderInterface $eventProvider, array $allProviders = []): void
    {
        foreach ($this->validators as $validator) {
            $validator->validate($eventProvider, $allProviders);
        }
    }
}
