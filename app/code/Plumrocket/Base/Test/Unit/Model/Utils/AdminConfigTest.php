<?php
/**
 * @package     Plumrocket_Base
 * @copyright   Copyright (c) 2023 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

declare(strict_types=1);

namespace Plumrocket\Base\Test\Unit\Model\Utils;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Store\Model\ScopeInterface;
use PHPUnit\Framework\Constraint\IsEqual;
use PHPUnit\Framework\TestCase;
use Plumrocket\Base\Model\ConfigUtils;
use Plumrocket\Base\Model\Utils\AdminConfig;

/**
 * @since 2.9.4
 */
class AdminConfigTest extends TestCase
{

    /**
     * @var \Magento\Framework\App\RequestInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $requestMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|\Plumrocket\Base\Model\ConfigUtils
     */
    private $configUtilsMock;

    /**
     * @var \Plumrocket\Base\Model\Utils\AdminConfig
     */
    private $adminConfig;

    protected function setUp(): void
    {
        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
                                 ->disableOriginalConstructor()
                                 ->getMockForAbstractClass();

        $this->configUtilsMock = $this->getMockBuilder(ConfigUtils::class)
                                 ->disableOriginalConstructor()
                                 ->getMock();

        $this->adminConfig = new AdminConfig($this->requestMock, $this->configUtilsMock);
    }

    /**
     * @covers \Plumrocket\Base\Model\Utils\AdminConfig::getScopedConfig
     * @return void
     */
    public function testNoParams(): void
    {
        $this->requestMock->method('getParam')->willReturn(null);

        $this->configUtilsMock
            ->expects($this->once())
            ->method('getConfig')
            ->with('test/group/field', new IsEqual(null), ScopeConfigInterface::SCOPE_TYPE_DEFAULT);
        $this->adminConfig->getScopedConfig('test/group/field');
    }

    /**
     * @covers \Plumrocket\Base\Model\Utils\AdminConfig::getScopedConfig
     * @return void
     */
    public function testWebsite(): void
    {
        $this->requestMock->method('getParam')->willReturnCallback(function ($param) {
            return ($param === 'website') ? '1' : null;
        });

        $this->configUtilsMock
            ->expects($this->once())
            ->method('getConfig')
            ->with('test/group/field', new IsEqual('1'), ScopeInterface::SCOPE_WEBSITE);
        $this->adminConfig->getScopedConfig('test/group/field');
    }

    /**
     * @covers \Plumrocket\Base\Model\Utils\AdminConfig::getScopedConfig
     * @return void
     */
    public function testStore(): void
    {
        $this->requestMock->method('getParam')->willReturnCallback(function ($param) {
            return ($param === 'store') ? '1' : null;
        });

        $this->configUtilsMock
            ->expects($this->once())
            ->method('getConfig')
            ->with('test/group/field', new IsEqual('1'), ScopeInterface::SCOPE_STORE);
        $this->adminConfig->getScopedConfig('test/group/field');
    }
}
