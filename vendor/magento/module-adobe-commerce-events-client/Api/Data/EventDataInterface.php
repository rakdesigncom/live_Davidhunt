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
 * Interface for event data from webapi requests
 *
 * @api
 */
interface EventDataInterface
{
    /**
     * Sets event name
     *
     * @param string $name
     * @return EventDataInterface
     */
    public function setName(string $name): EventDataInterface;

    /**
     * Returns event name
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Sets event parent name
     *
     * @param string $parent
     * @return EventDataInterface
     */
    public function setParent(string $parent): EventDataInterface;

    /**
     * Returns event parent name
     *
     * @return string
     */
    public function getParent(): string;

    /**
     * Sets event fields
     *
     * @param \Magento\AdobeCommerceEventsClient\Api\Data\EventFieldInterface[] $fields
     * @return EventDataInterface
     */
    public function setFields(array $fields): EventDataInterface;

    /**
     * Returns event fields
     *
     * @return \Magento\AdobeCommerceEventsClient\Api\Data\EventFieldInterface[]
     */
    public function getFields(): array;

    /**
     * Sets event rules
     *
     * @param \Magento\AdobeCommerceEventsClient\Api\Data\EventRuleInterface[] $rules
     * @return EventDataInterface
     */
    public function setRules(array $rules): EventDataInterface;

    /**
     * Returns event fields
     *
     * @return \Magento\AdobeCommerceEventsClient\Api\Data\EventRuleInterface[]
     */
    public function getRules(): array;

    /**
     * Sets event destination
     *
     * @param string $destination
     * @return EventDataInterface
     */
    public function setDestination(string $destination): EventDataInterface;

    /**
     * Returns event destination
     *
     * @return string
     */
    public function getDestination(): string;

    /**
     * Sets event priority
     *
     * @param bool $priority
     * @return EventDataInterface
     */
    public function setPriority(bool $priority): EventDataInterface;

    /**
     * Returns event priority
     *
     * @return bool
     */
    public function isPriority(): bool;

    /**
     * Sets event requires HIPAA audit
     *
     * @param bool $hipaaAuditRequired
     * @return EventDataInterface
     */
    public function setHipaaAuditRequired(bool $hipaaAuditRequired): EventDataInterface;

    /**
     * Checks if event is required to be HIPAA audited
     *
     * @return bool
     */
    public function isHipaaAuditRequired(): bool;

    /**
     * Sets event provider id
     *
     * @param string $providerId
     * @return EventDataInterface
     */
    public function setProviderId(string $providerId): EventDataInterface;

    /**
     * Returns event provider id
     *
     * @return string
     */
    public function getProviderId(): string;
}
