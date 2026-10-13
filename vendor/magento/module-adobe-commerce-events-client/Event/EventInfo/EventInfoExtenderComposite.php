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

namespace Magento\AdobeCommerceEventsClient\Event\EventInfo;

/**
 * Composite for EventInfoExtenderInterface implementations.
 */
class EventInfoExtenderComposite implements EventInfoExtenderInterface
{
    /**
     * @param EventInfoExtenderInterface[] $extenders
     */
    public function __construct(private readonly array $extenders)
    {
    }

    /**
     * Extends the result array with model-specific fields.
     *
     * @param string $className
     * @param array $result
     * @return array
     */
    public function extend(string $className, array $result): array
    {
        foreach ($this->extenders as $extender) {
            $result = $extender->extend($className, $result);
        }
        return $result;
    }
}
