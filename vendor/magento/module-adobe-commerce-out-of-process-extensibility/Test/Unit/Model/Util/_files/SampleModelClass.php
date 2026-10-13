<?php
/************************************************************************
 *
 * ADOBE CONFIDENTIAL
 * ___________________
 *
 * Copyright 2026 Adobe
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

namespace Magento\AdobeCommerceOutOfProcessExtensibility\Test\Unit\Model\Util\_files;

use Magento\Framework\DataObject;

/**
 * Fixture model class used to test modelInterfaceMap merging in ReflectionHelper.
 *
 * getRegion() returns a string (flat scalar stored in _data), while SampleModelInterface::getRegion() returns
 * SampleNestedObject. The merge must keep the model's simpler string type.
 */
class SampleModelClass extends DataObject
{
    /** @return string */
    public function getRegion(): string
    {
        return $this->getData('region');
    }

    public function getRegionId(): int
    {
        return (int) $this->getData('region_id');
    }
}
