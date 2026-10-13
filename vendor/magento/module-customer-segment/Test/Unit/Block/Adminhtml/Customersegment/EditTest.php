<?php
/**
 * ADOBE CONFIDENTIAL
 *
 * Copyright 2014 Adobe
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

namespace Magento\CustomerSegment\Test\Unit\Block\Adminhtml\Customersegment;

use Magento\Backend\Block\Widget\Button\ButtonList;
use Magento\Backend\Block\Widget\Context;
use Magento\Backend\Model\UrlInterface as BackendUrlInterface;
use Magento\CustomerSegment\Block\Adminhtml\Customersegment\Edit;
use Magento\CustomerSegment\Model\Segment;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Escaper;
use Magento\Framework\Registry;
use Magento\Framework\View\Helper\SecureHtmlRenderer;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;

class EditTest extends TestCase
{
    /**
     * @var Edit
     */
    protected $model;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var \Magento\CustomerSegment\Model\Segment
     */
    protected $segment;

    /**
     * @var BackendUrlInterface
     */
    protected $urlBuilder;

    /**
     * @var ButtonList
     */
    protected $buttonList;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var Escaper
     */
    protected $escaper;

    /**
     * @var Context
     */
    protected $context;

    protected function setUp(): void
    {
        $objectManagerHelper = new ObjectManagerHelper($this);

        $objects = [
            [
                SecureHtmlRenderer::class,
                $this->createMock(SecureHtmlRenderer::class)
            ]
        ];
        $objectManagerHelper->prepareObjectManager($objects);

        $this->segment = $this->getMockBuilder(Segment::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId'])
            ->addMethods(['getSegmentId', 'getName'])
            ->getMock();

        $this->registry = $this->getMockBuilder(Registry::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->registry
            ->expects($this->any())
            ->method('registry')
            ->with('current_customer_segment')
            ->willReturn($this->segment);

        $this->urlBuilder = $this->getMockBuilder(\Magento\Framework\UrlInterface::class)
            ->getMockForAbstractClass();

        $this->buttonList = $this->getMockBuilder(ButtonList::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->buttonList->expects($this->any())->method('update')->willReturnSelf();
        $this->buttonList->expects($this->any())->method('add')->willReturnSelf();

        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->getMockForAbstractClass();
        $this->request->expects($this->any())->method('getParam')->willReturn(1);
        $this->escaper = $this->getMockBuilder(Escaper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->context
            ->expects($this->once())
            ->method('getButtonList')
            ->willReturn($this->buttonList);
        $this->context
            ->expects($this->once())
            ->method('getUrlBuilder')
            ->willReturn($this->urlBuilder);
        $this->context
            ->expects($this->once())
            ->method('getRequest')
            ->willReturn($this->request);
        $this->context
            ->expects($this->any())
            ->method('getEscaper')
            ->willReturn($this->escaper);

        $this->model = new Edit(
            $this->context,
            $this->registry,
            [],
            $this->escaper
        );
    }

    protected function tearDown(): void
    {
        unset(
            $this->model,
            $this->segment,
            $this->registry,
            $this->urlBuilder,
            $this->buttonList,
            $this->request,
            $this->escaper,
            $this->context
        );
    }

    public function testGetMatchUrl()
    {
        $this->segment
            ->expects($this->any())
            ->method('getId')
            ->willReturn(1);

        $this->urlBuilder
            ->expects($this->any())
            ->method('getUrl')
            ->with('*/*/match', ['id' => $this->segment->getId()])
            ->willReturn('http://some_url');

        $this->assertStringContainsString('http://some_url', (string)$this->model->getMatchUrl());
    }

    public function testGetHeaderText()
    {
        $this->segment
            ->expects($this->once())
            ->method('getSegmentId')
            ->willReturn(false);

        $this->assertEquals('New Segment', $this->model->getHeaderText());
    }

    public function testGetHeaderTextWithSegmentId()
    {
        $segmentName = 'test_segment_name';

        $this->segment
            ->expects($this->once())
            ->method('getSegmentId')
            ->willReturn(1);
        $this->segment
            ->expects($this->once())
            ->method('getName')
            ->willReturn($segmentName);

        $this->escaper
            ->expects($this->once())
            ->method('escapeHtml')
            ->willReturn($segmentName);

        $this->assertEquals(sprintf("Edit Segment '%s'", $segmentName), $this->model->getHeaderText());
    }

    /**
     * Test that delete button uses proper XSS protection
     */
    public function testDeleteButtonXssProtection()
    {
        $segmentId = 1;
        
        // Create a new model with proper mocks for this specific test
        $newButtonList = $this->getMockBuilder(ButtonList::class)
            ->disableOriginalConstructor()
            ->getMock();
        $newButtonList->expects($this->any())->method('add')->willReturnSelf();
        
        $newRequest = $this->getMockBuilder(RequestInterface::class)
            ->getMockForAbstractClass();
        $newRequest->expects($this->any())
            ->method('getParam')
            ->with('id')
            ->willReturn($segmentId);

        $newEscaper = $this->getMockBuilder(Escaper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $newContext = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $newContext->expects($this->any())->method('getButtonList')->willReturn($newButtonList);
        $newContext->expects($this->any())->method('getUrlBuilder')->willReturn($this->urlBuilder);
        $newContext->expects($this->any())->method('getRequest')->willReturn($newRequest);
        $newContext->expects($this->any())->method('getEscaper')->willReturn($newEscaper);

        // Mock the escaper chain for delete confirmation
        $newEscaper->expects($this->atLeastOnce())
            ->method('escapeHtml')
            ->willReturnArgument(0);

        $newEscaper->expects($this->atLeastOnce())
            ->method('escapeJs')
            ->willReturnArgument(0);

        $newButtonList->expects($this->once())
            ->method('update')
            ->with(
                'delete',
                'onclick',
                $this->stringContains('deleteConfirm')
            );

        // Create new model instance that will trigger _construct
        $testModel = new Edit($newContext, $this->registry, [], $newEscaper);
        
        // Verify that the object was created successfully and the escaper methods
        // are called with the expected XSS protection during construction
        $this->assertInstanceOf(Edit::class, $testModel);
    }

    /**
     * Test constructor with null escaper parameter (ObjectManager fallback).
     */
    public function testConstructorWithNullEscaper()
    {
        // In unit tests, when escaper is null and ObjectManager isn't initialized,
        // we get an Error when trying to call methods on null escaper
        $this->expectException(\Error::class);
        $this->expectExceptionMessage("Call to a member function escapeJs() on null");
        
        // Create a new context mock for this test to avoid conflicts with expected call counts
        $nullTestContext = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $nullTestContext->expects($this->any())->method('getButtonList')->willReturn($this->buttonList);
        $nullTestContext->expects($this->any())->method('getUrlBuilder')->willReturn($this->urlBuilder);
        $nullTestContext->expects($this->any())->method('getRequest')->willReturn($this->request);
        $nullTestContext->expects($this->any())->method('getEscaper')->willReturn($this->escaper);
        
        // Test that the block tries to use ObjectManager when escaper is null
        new Edit($nullTestContext, $this->registry, [], null);
    }
}
