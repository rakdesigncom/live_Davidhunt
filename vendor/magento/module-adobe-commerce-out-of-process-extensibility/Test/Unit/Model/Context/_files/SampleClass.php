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

namespace Magento\AdobeCommerceOutOfProcessExtensibility\Test\Unit\Model\Context\_files ;

class SampleClass
{
    public function getValueOne(): string
    {
        return "test_value_one";
    }

    public function getValueTwo(): string
    {
        return "test_value_two";
    }

    public function toArray(): array
    {
        return [
            'value_one' => $this->getValueOne(),
            'value_two' => $this->getValueTwo(),
        ];
    }
}
