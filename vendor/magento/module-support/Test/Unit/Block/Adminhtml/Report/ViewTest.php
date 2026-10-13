<?php
/**
 * ADOBE CONFIDENTIAL
 *
 * Copyright 2015 Adobe
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
 */
declare(strict_types=1);

namespace Magento\Support\Test\Unit\Block\Adminhtml\Report;

use Magento\Backend\Block\Widget\Context;
use Magento\Backend\Block\Widget\Button\ButtonList;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\AuthorizationInterface;
use Magento\Framework\Escaper;
use Magento\Framework\Registry;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Helper\SecureHtmlRenderer;
use Magento\Support\Block\Adminhtml\Report\View;
use Magento\Support\Model\Report;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ViewTest extends TestCase
{
    /**
     * @var View
     */
    protected $viewReportBlock;

    /**
     * @var Context
     */
    protected $context;

    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManagerHelper;

    /**
     * @var Registry|MockObject
     */
    protected $coreRegistryMock;

    /**
     * @var Report|MockObject
     */
    protected $reportMock;

    /**
     * @var UrlInterface|MockObject
     */
    protected $urlBuilderMock;

    /**
     * @var RequestInterface|MockObject
     */
    protected $requestMock;

    /**
     * @var Escaper|MockObject
     */
    protected $escaperMock;

    /**
     * @var ButtonList|MockObject
     */
    protected $buttonListMock;
    
    protected function setUp(): void
    {
        $this->coreRegistryMock = $this->getMockBuilder(Registry::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->reportMock = $this->getMockBuilder(Report::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->urlBuilderMock = $this->getMockBuilder(UrlInterface::class)
            ->getMockForAbstractClass();
        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
            ->getMockForAbstractClass();

        $this->escaperMock = $this->createMock(Escaper::class);
        $this->buttonListMock = $this->createMock(ButtonList::class);

        $this->objectManagerHelper = new ObjectManagerHelper($this);

        // Prepare SecureHtmlRenderer for ObjectManagerHelper
        $secureHtmlRendererMock = $this->createMock(SecureHtmlRenderer::class);
        $objects = [
            [
                SecureHtmlRenderer::class,
                $secureHtmlRendererMock
            ]
        ];
        $this->objectManagerHelper->prepareObjectManager($objects);

        $this->context = $this->objectManagerHelper->getObject(
            Context::class,
            [
                'urlBuilder' => $this->urlBuilderMock,
                'request' => $this->requestMock,
                'escaper' => $this->escaperMock,
                'buttonList' => $this->buttonListMock
            ]
        );

        $this->viewReportBlock = $this->objectManagerHelper->getObject(
            View::class,
            [
                'context' => $this->context,
                'coreRegistry' => $this->coreRegistryMock
            ]
        );
    }

    public function testGetReport()
    {
        $this->coreRegistryMock->expects($this->once())
            ->method('registry')
            ->with('current_report')
            ->willReturn($this->reportMock);

        $this->assertSame($this->reportMock, $this->viewReportBlock->getReport());
    }

    public function testGetDownloadUrl()
    {
        $id = 1;
        $downloadUrl = '/download/url';

        $this->requestMock->expects($this->any())
            ->method('getParam')
            ->with('id', null)
            ->willReturn($id);
        $this->urlBuilderMock->expects($this->once())
            ->method('getUrl')
            ->with('*/*/download', ['id' => $id])
            ->willReturn($downloadUrl);

        $this->assertEquals($downloadUrl, $this->viewReportBlock->getDownloadUrl());
    }

    /**
     * Test that delete button uses proper XSS protection
     */
    public function testDeleteButtonXssProtection()
    {
        $reportId = 1;

        // Set up expectations for escaper calls that happen during _construct
        $escaperMock = $this->createMock(Escaper::class);
        $escaperMock->expects($this->atLeastOnce())
            ->method('escapeHtml')
            ->willReturnArgument(0);
        $escaperMock->expects($this->atLeastOnce())
            ->method('escapeJs')
            ->willReturnArgument(0);

        $buttonListMock = $this->createMock(ButtonList::class);
        $buttonListMock->expects($this->atLeastOnce())
            ->method('update')
            ->willReturnSelf();
        $buttonListMock->expects($this->atLeastOnce())
            ->method('remove')
            ->willReturnSelf();
        $buttonListMock->expects($this->atLeastOnce())
            ->method('add')
            ->willReturnSelf();

        $urlBuilderMock = $this->getMockBuilder(UrlInterface::class)
            ->getMockForAbstractClass();
        $urlBuilderMock->expects($this->atLeastOnce())
            ->method('getUrl')
            ->willReturnCallback(function ($route, $params = []) use ($reportId) {
                return 'http://localhost/admin/' . str_replace('*/*/', 'support/report/', $route)
                    . '/id/' . ($params['id'] ?? $reportId);
            });

        $requestMock = $this->getMockBuilder(RequestInterface::class)
            ->getMockForAbstractClass();
        $requestMock->expects($this->any())
            ->method('getParam')
            ->willReturn($reportId);

        // Create new context for this test
        $contextForTest = $this->objectManagerHelper->getObject(
            Context::class,
            [
                'urlBuilder' => $urlBuilderMock,
                'request' => $requestMock,
                'escaper' => $escaperMock,
                'buttonList' => $buttonListMock
            ]
        );

        // Create View object which will trigger _construct and the escaper calls
        $viewForTest = $this->objectManagerHelper->getObject(
            View::class,
            [
                'context' => $contextForTest,
                'coreRegistry' => $this->coreRegistryMock
            ]
        );

        // Verify that the object was created successfully and getDeleteUrl works
        $deleteUrl = $viewForTest->getDeleteUrl();
        $this->assertStringContainsString('/delete/', $deleteUrl);
    }
}
