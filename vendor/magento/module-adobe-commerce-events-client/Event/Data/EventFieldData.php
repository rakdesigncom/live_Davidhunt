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

use Magento\AdobeCommerceEventsClient\Api\Data\EventFieldInterface;
use Magento\AdobeCommerceEventsClient\Event\EventField;
use Magento\Framework\DataObject;

/**
 * Data object for event fields
 */
class EventFieldData extends DataObject implements EventFieldInterface
{
    /**
     * @inheritDoc
     */
    public function setName(string $name): EventFieldInterface
    {
        $this->setData(EventField::NAME, $name);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return $this->getData(EventField::NAME);
    }

    /**
     * @inheritDoc
     */
    public function setConverter(string $converter): EventFieldInterface
    {
        $this->setData(EventField::CONVERTER, $converter);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getConverter(): string
    {
        return $this->getData(EventField::CONVERTER);
    }

    /**
     * @inheritDoc
     */
    public function setSource(string $source): EventFieldInterface
    {
        return $this->setData(EventField::SOURCE, $source);
    }

    /**
     * @inheritDoc
     */
    public function getSource(): string
    {
        return $this->getData(EventField::SOURCE);
    }
}
