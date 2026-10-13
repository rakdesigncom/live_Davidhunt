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

namespace Magento\AdobeCommerceEventsClient\Event\Data;

use Magento\AdobeCommerceEventsClient\Api\Data\EventDataInterface;
use Magento\AdobeCommerceEventsClient\Event\Event;
use Magento\Framework\DataObject;

/**
 * Event data for processing webapi requests.
 */
class EventData extends DataObject implements EventDataInterface
{
    /**
     * @inheritDoc
     */
    public function setName(string $name): EventDataInterface
    {
        $this->setData(Event::EVENT_NAME, $name);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return $this->getData(Event::EVENT_NAME) ?: '';
    }

    /**
     * @inheritDoc
     */
    public function setParent(string $parent): EventDataInterface
    {
        $this->setData(Event::EVENT_PARENT, $parent);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getParent(): string
    {
        return $this->getData(Event::EVENT_PARENT) ?: '';
    }

    /**
     * @inheritDoc
     */
    public function setProviderId(string $providerId): EventDataInterface
    {
        $this->setData(Event::EVENT_PROVIDER_ID, $providerId);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getProviderId(): string
    {
        return $this->getData(Event::EVENT_PROVIDER_ID) ?: '';
    }

    /**
     * @inheritDoc
     */
    public function setFields(array $fields): EventDataInterface
    {
        $this->setData(Event::EVENT_FIELDS, $fields);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getFields(): array
    {
        return $this->getData(Event::EVENT_FIELDS) ?: [];
    }

    /**
     * @inheritDoc
     */
    public function setRules(array $rules): EventDataInterface
    {
        $this->setData(Event::EVENT_RULES, $rules);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getRules(): array
    {
        return $this->getData(Event::EVENT_RULES) ?: [];
    }

    /**
     * @inheritDoc
     */
    public function setDestination(string $destination): EventDataInterface
    {
        $this->setData(Event::EVENT_DESTINATION, $destination);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getDestination(): string
    {
        return $this->getData(Event::EVENT_DESTINATION) ?: '';
    }

    /**
     * @inheritDoc
     */
    public function setPriority(bool $priority): EventDataInterface
    {
        $this->setData(Event::EVENT_PRIORITY, $priority);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function isPriority(): bool
    {
        return (bool)$this->getData(Event::EVENT_PRIORITY);
    }

    /**
     * @inheritDoc
     */
    public function setHipaaAuditRequired(bool $hipaaAuditRequired): EventDataInterface
    {
        $this->setData(Event::EVENT_HIPAA_AUDIT_REQUIRED, $hipaaAuditRequired);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function isHipaaAuditRequired(): bool
    {
        return (bool)$this->getData(Event::EVENT_HIPAA_AUDIT_REQUIRED);
    }
}
