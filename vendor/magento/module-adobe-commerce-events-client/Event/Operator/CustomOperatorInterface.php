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

namespace Magento\AdobeCommerceEventsClient\Event\Operator;

use Magento\AdobeCommerceEventsClient\Event\Rule\Rule;

/**
 * Interface for custom operator classes
 */
interface CustomOperatorInterface
{
    /**
     * Verifies that the field value meets the condition.
     *
     * @param Rule $rule
     * @param array $eventData
     * @return bool
     * @throws OperatorException
     */
    public function verify(Rule $rule, array $eventData): bool;
}
