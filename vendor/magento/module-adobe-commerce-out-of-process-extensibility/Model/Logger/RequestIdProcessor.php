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

use Magento\AdobeCommerceOutOfProcessExtensibility\Model\Request\RequestIdInterface;
use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

/**
 * Adds request ID to the context of log record.
 */
class RequestIdProcessor implements ProcessorInterface
{
    /**
     * @param RequestIdInterface $requestId
     */
    public function __construct(private RequestIdInterface $requestId)
    {
    }

    /**
     * Adds request ID to the context of log record.
     *
     * @param array|LogRecord $record
     * @return array|LogRecord
     */
    public function __invoke(array|LogRecord $record)
    {
        if ($record instanceof LogRecord) {
            return $record->with(context: array_merge(
                $record->context,
                [RequestIdInterface::REQUEST_ID_HEADER => $this->requestId->get()]
            ));
        }

        $record['context'][RequestIdInterface::REQUEST_ID_HEADER] = $this->requestId->get();
        return $record;
    }
}
