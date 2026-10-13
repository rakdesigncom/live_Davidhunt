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

namespace Magento\AdobeCommerceEventsClient\Model\ResourceModel\EventProvider;

use Magento\AdobeCommerceEventsClient\Model\EventProvider;
use Magento\AdobeCommerceEventsClient\Model\ResourceModel\EventProvider as EventProviderResourceModel;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Resource collection for the event provider model
 */
class Collection extends AbstractCollection
{
    /**
     * @inheritDoc
     */
    protected function _construct(): void
    {
        $this->_init(EventProvider::class, EventProviderResourceModel::class);
    }
}
