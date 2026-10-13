<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Event;

use Magento\Framework\DataObject;

/**
 * Event field data object
 */
class EventField extends DataObject
{
    public const NAME = 'name';
    public const CONVERTER = 'converter';
    public const SOURCE = 'source';

    /**
     * Returns subscribed field name
     *
     * @return string
     */
    public function getName(): string
    {
        return (string)$this->getData(self::NAME);
    }

    /**
     * Returns subscribed field converter name
     *
     * @return string|null
     */
    public function getConverter(): ?string
    {
        return $this->getData(self::CONVERTER);
    }

    /**
     * Returns subscribed field source.
     *
     * This is used only for context fields.
     *
     * @return string|null
     */
    public function getSource(): ?string
    {
        return $this->getData(self::SOURCE);
    }
}
