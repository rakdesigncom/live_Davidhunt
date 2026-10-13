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

namespace Magento\AdobeCommerceEventsClient\Ui\Component\Listing\Status;

use Magento\AdobeCommerceEventsClient\Api\Data\EventInterface;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * Returns options for the status field
 */
class StatusOptionSource implements OptionSourceInterface
{
    /**
     * @inheritDoc
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => '',
                'label' => '-- Select the event status --'
            ],
            [
                'value' => EventInterface::WAITING_STATUS,
                'label' => 'Waiting'
            ],
            [
                'value' => EventInterface::SUCCESS_STATUS,
                'label' => 'Success'
            ],
            [
                'value' => EventInterface::FAILURE_STATUS,
                'label' => 'Failure'
            ],
            [
                'value' => EventInterface::SENDING_STATUS,
                'label' => 'Sending'
            ]
        ];
    }
}
