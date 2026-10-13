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

namespace Magento\AdobeCommerceEventsClient\Event\EventProvider\Webapi;

use Magento\AdobeCommerceEventsClient\Api\Data\EventProviderInterface;

/**
 * Obscures the workspace configuration in the output data for rest API response
 */
class WorkspaceConfigurationObscurer
{
    /**
     * Obscures the workspace configuration in the output data for rest API response
     *
     * @param EventProviderInterface $eventProvider
     * @param array $outputData
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function execute(EventProviderInterface $eventProvider, array $outputData): array
    {
        if (!empty($outputData[EventProviderInterface::WORKSPACE_CONFIGURATION])) {
            $outputData[EventProviderInterface::WORKSPACE_CONFIGURATION] =
                EventProviderInterface::OBSCURE_WORKSPACE_CONFIGURATION;
        }

        return $outputData;
    }
}
