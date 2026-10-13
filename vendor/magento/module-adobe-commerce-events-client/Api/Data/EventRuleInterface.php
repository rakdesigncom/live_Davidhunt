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

namespace Magento\AdobeCommerceEventsClient\Api\Data;

/**
 * Interface for event rule data from webapi requests
 *
 * @api
 */
interface EventRuleInterface
{
    /**
     * Sets event rule field name
     *
     * @param string $field
     * @return EventRuleInterface
     */
    public function setField(string $field): EventRuleInterface;

    /**
     * Returns event rule field name
     *
     * @return string
     */
    public function getField(): string;

    /**
     * Sets event rule operator
     *
     * @param string $operator
     * @return EventRuleInterface
     */
    public function setOperator(string $operator): EventRuleInterface;

    /**
     * Returns event rule operator
     *
     * @return string
     */
    public function getOperator(): string;

    /**
     * Sets event rule value
     *
     * @param string $value
     * @return EventRuleInterface
     */
    public function setValue(string $value): EventRuleInterface;

    /**
     * Returns event rule value
     *
     * @return string
     */
    public function getValue(): string;
}
