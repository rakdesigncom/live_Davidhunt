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

namespace Magento\AdobeCommerceEventsClient\Ui\DataProvider;

use Magento\AdobeCommerceEventsClient\Api\Data\EventProviderInterface;
use Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider;

/**
 * Provider for event providers
 */
class EventProviderDataProvider extends DataProvider
{
    public const ACTIVE = 'active';

    public const FORM_DATA_SOURCE = 'event_provider_form_data_source';
    public const GRID_DATA_SOURCE = 'event_provider_grid_data_source';

    /**
     * @inheritDoc
     */
    public function getData(): array
    {
        $data = parent::getData();

        foreach ($data['items'] ?? [] as $key => $item) {
            if (!empty($item[EventProviderInterface::WORKSPACE_CONFIGURATION])) {
                $data['items'][$key][EventProviderInterface::WORKSPACE_CONFIGURATION] =
                    EventProviderInterface::OBSCURE_WORKSPACE_CONFIGURATION;
            }
        }

        return $data;
    }
}
