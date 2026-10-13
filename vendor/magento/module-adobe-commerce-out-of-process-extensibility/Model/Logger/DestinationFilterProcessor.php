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

namespace Magento\AdobeCommerceOutOfProcessExtensibility\Model\Logger;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

/**
 * Filters out the destination from the context of log record.
 */
class DestinationFilterProcessor implements ProcessorInterface
{
    /**
     * @param bool $enabled
     */
    public function __construct(private readonly bool $enabled = true)
    {
    }

    /**
     * Filters out the destination from the context of log record.
     *
     * @param array|LogRecord $record
     * @return array|LogRecord
     */
    public function __invoke(array|LogRecord $record)
    {
        if (!$this->enabled || !isset($record->context['destination'])) {
            return $record;
        }

        if ($record instanceof LogRecord) {
            $context = $record->context;
            unset($context['destination']);
            return $record->with(context: $context);
        } else {
            unset($record['context']['destination']);
            return $record;
        }
    }
}
