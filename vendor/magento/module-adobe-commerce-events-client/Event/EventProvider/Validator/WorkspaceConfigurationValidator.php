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
use Magento\AdobeCommerceEventsClient\Config\Validator\WorkspaceFormatValidator;
use Magento\AdobeCommerceEventsClient\Event\EventProvider\ValidatorInterface;
use Magento\Framework\Exception\ValidatorException;

/**
 * Validator for event provider's workspace configuration.
 */
class WorkspaceConfigurationValidator implements ValidatorInterface
{
    /**
     * @param WorkspaceFormatValidator $validator
     */
    public function __construct(private readonly WorkspaceFormatValidator $validator)
    {
    }

    /**
     * Validates event provider's workspace configuration.
     *
     * @param EventProviderInterface $eventProvider
     * @param EventProviderInterface[] $allProviders
     * @return void
     * @throws ValidatorException
     */
    public function validate(EventProviderInterface $eventProvider, array $allProviders = []): void
    {
        $workspaceConfiguration = $eventProvider->getWorkspaceConfiguration();

        if (empty($workspaceConfiguration)) {
            return;
        }

        if (preg_match('/^\*+$/', $workspaceConfiguration)) {
            if (!isset($allProviders[$eventProvider->getProviderId()])) {
                throw new ValidatorException(
                    __('The workspace configuration has the wrong format. Provide a valid JSON string.')
                );
            }

            return;
        }

        $this->validator->validate($workspaceConfiguration);
    }
}
