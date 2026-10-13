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

namespace Magento\AdobeCommerceEventsClient\Test\Unit\Event\Hipaa;

use Magento\AdobeCommerceEventsClient\Event\Hipaa\HipaaCustomerChecker;
use Magento\Framework\App\Config\ScopeConfigInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Tests for @see HipaaCustomerChecker
 */
class HipaaCustomerCheckerTest extends TestCase
{
    /**
     * @var ScopeConfigInterface|MockObject
     */
    private ScopeConfigInterface|MockObject $scopeConfigMock;

    /**
     * @var HipaaCustomerChecker
     */
    private HipaaCustomerChecker $hipaaCustomerChecker;

    protected function setUp(): void
    {
        $this->scopeConfigMock = $this->createMock(ScopeConfigInterface::class);
        $this->hipaaCustomerChecker = new HipaaCustomerChecker($this->scopeConfigMock);
    }

    public function testIsHipaaCustomerReturnsTrueWhenFlagIsSet(): void
    {
        $this->scopeConfigMock->expects(self::once())
            ->method('isSetFlag')
            ->with('general/instance/hipaa')
            ->willReturn(true);

        $this->assertTrue($this->hipaaCustomerChecker->isHipaaCustomer());
    }

    public function testIsHipaaCustomerReturnsFalseWhenFlagIsNotSet(): void
    {
        $this->scopeConfigMock->expects(self::once())
            ->method('isSetFlag')
            ->with('general/instance/hipaa')
            ->willReturn(false);

        $this->assertFalse($this->hipaaCustomerChecker->isHipaaCustomer());
    }
}
