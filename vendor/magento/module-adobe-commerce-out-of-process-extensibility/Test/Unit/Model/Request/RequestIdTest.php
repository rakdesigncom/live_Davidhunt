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

namespace Magento\AdobeCommerceOutOfProcessExtensibility\Test\Unit\Model\Request;

use Magento\AdobeCommerceOutOfProcessExtensibility\Model\Request\RequestId;
use Magento\Framework\DataObject\IdentityGeneratorInterface;
use PHPUnit\Framework\TestCase;

/**
 * Tests for @see RequestId
 */
class RequestIdTest extends TestCase
{
    public function testGetReturnsSameIdOnMultipleCalls()
    {
        $generatorMock = $this->createMock(IdentityGeneratorInterface::class);
        $generatorMock->expects($this->once())
            ->method('generateId')
            ->willReturn('test-id-123');

        $requestId = new RequestId($generatorMock);

        $firstId = $requestId->get();
        $secondId = $requestId->get();
        $thirdId = $requestId->get();

        $this->assertSame('test-id-123', $firstId);
        $this->assertSame($firstId, $secondId);
        $this->assertSame($firstId, $thirdId);
    }
}
