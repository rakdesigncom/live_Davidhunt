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

namespace Magento\AdobeCommerceEventsClient\Event\EventInfo\EventInfoExtender;

use Magento\AdobeCommerceEventsClient\Event\EventInfo\EventInfoExtenderInterface;
use Magento\AdobeCommerceEventsClient\Event\Filter\EventFieldsFilter;
use Magento\Framework\Model\AbstractModel;

/**
 * Extends event model fields for AbstractModel descendants.
 */
class AbstractModelExtender implements EventInfoExtenderInterface
{
    /**
     * Extends the result array if the class is a subclass of AbstractModel.
     *
     * @param string $className
     * @param array $result
     * @return array
     */
    public function extend(string $className, array $result): array
    {
        if (is_subclass_of($className, AbstractModel::class)) {
            $result[EventFieldsFilter::FIELD_ORIGINAL_DATA] = 'array';
            $result[EventFieldsFilter::FIELD_IS_NEW] = 'boolean';
        }
        return $result;
    }
}
