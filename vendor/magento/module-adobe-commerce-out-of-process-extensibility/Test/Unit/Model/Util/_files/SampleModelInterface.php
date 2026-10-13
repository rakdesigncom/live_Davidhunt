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

/**
 * Fixture interface for modelInterfaceMap merging test.
 *
 * getRegion() conflicts with SampleModelClass::getRegion() — this interface version returns SampleNestedObject,
 * but the model returns a plain string. The merge must keep the model's type.
 * getFirstname() and getLastname() are EAV-only fields not defined on SampleModelClass; they must be added.
 */
interface SampleModelInterface
{
    /** @return SampleNestedObject */
    public function getRegion(): SampleNestedObject;

    public function getFirstname(): string;

    public function getLastname(): string;
}
