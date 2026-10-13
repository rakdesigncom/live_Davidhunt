<?php
/**
 * ADOBE CONFIDENTIAL
 *
 * Copyright 2018 Adobe
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

namespace Magento\Rma\Test\Unit\Block\Adminhtml\Rma;

use Magento\Backend\Block\Widget\Context as WidgetContext;
use Magento\Backend\Block\Widget\Button\ButtonList;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Escaper;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Helper\SecureHtmlRenderer;
use Magento\Rma\Block\Adminhtml\Rma\Edit;
use Magento\Rma\Model\Rma;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Tests Magento\Rma\Block\Adminhtml\Rma\Edit.
 */
class EditTest extends TestCase
{
    /**
     * @var Edit
     */
    private $model;

    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var WidgetContext
     */
    private $context;

    /**
     * @var RequestInterface|MockObject
     */
    private $request;

    /**
     * @var Rma
     */
    private $rma;

    /**
     * @var UrlInterface|MockObject
     */
    private $urlBuilder;

    /**
     * @var Escaper|MockObject
     */
    private $escaper;

    /**
     * @var ButtonList|MockObject
     */
    private $buttonList;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);

        // Prepare SecureHtmlRenderer for ObjectManagerHelper
        $secureHtmlRendererMock = $this->createMock(SecureHtmlRenderer::class);
        $objects = [
            [
                SecureHtmlRenderer::class,
                $secureHtmlRendererMock
            ]
        ];
        $this->objectManager->prepareObjectManager($objects);

        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->addMethods(['getServer'])
            ->getMockForAbstractClass();
        $this->urlBuilder = $this->getMockBuilder(UrlInterface::class)
            ->getMockForAbstractClass();
        $this->escaper = $this->createMock(Escaper::class);
        $this->buttonList = $this->createMock(ButtonList::class);
        $this->rma = $this->createMock(Rma::class);
        $this->rma->method('getOrderId')->willReturn(1);
        $this->rma->method('getCustomerId')->willReturn(1);
    }

    /**
     * Method to test get back url with referrer.
     *
     * @param string $referrer
     * @param string $expectation
     * @return void
     *
     * @dataProvider getBackUrlDataProvider
     */
    public function testGetBackUrlWithReferrer(string $referrer, string $expectation): void
    {
        $this->request->method('getServer')
            ->with('HTTP_REFERER', '')
            ->willReturnOnConsecutiveCalls('', $referrer);
        $this->createEditMockModel();
        $this->urlBuilder->expects($this->once())->method('getUrl')
            ->willReturnCallback(
                function (string $route = '', array $params = []) {
                    $routeParams = '';
                    array_walk($params, function ($value, $key) use (&$routeParams) {
                        $routeParams .= (strlen($routeParams) ? '/' : '') . $key . '/' . $value;
                    });

                    return "http://localhost/admin/{$route}{$routeParams}";
                }
            );

        $this->assertStringContainsString($expectation, $this->model->getBackUrl());
    }

    /**
     * @return array
     */
    public function getBackUrlDataProvider(): array
    {
        return [
            ['http://localhost/admin/sales/order/view/order_id/1', 'sales/order/view/order_id/1'],
            ['http://localhost/admin/customer/index/edit/id/1', 'customer/index/edit/id/1']
        ];
    }

    /**
     * Method to create edit mock model.
     *
     * @return void
     */
    private function createEditMockModel(): void
    {
        $this->context = $this->objectManager->getObject(
            WidgetContext::class,
            [
                'request' => $this->request,
                'urlBuilder' => $this->urlBuilder,
                'escaper' => $this->escaper,
                'buttonList' => $this->buttonList
            ]
        );

        $this->model = $this->objectManager->getObject(
            Edit::class,
            [
                'context' => $this->context
            ]
        );
        $this->objectManager->setBackwardCompatibleProperty($this->model, '_rma', $this->rma);
    }

    /**
     * Test that close button uses proper XSS protection
     *
     * @return void
     */
    public function testCloseButtonXssProtection(): void
    {
        $rmaId = 1;

        // Create separate mocks for this test to avoid conflicts
        $escaperForTest = $this->createMock(Escaper::class);
        $escaperForTest->expects($this->atLeastOnce())
            ->method('escapeHtml')
            ->willReturnArgument(0);
        $escaperForTest->expects($this->atLeastOnce())
            ->method('escapeJs')
            ->willReturnArgument(0);

        $buttonListForTest = $this->createMock(ButtonList::class);
        $buttonListForTest->expects($this->atLeastOnce())
            ->method('add')
            ->willReturnSelf();
        $buttonListForTest->expects($this->atLeastOnce())
            ->method('remove')
            ->willReturnSelf();

        $urlBuilderForTest = $this->getMockBuilder(UrlInterface::class)
            ->getMockForAbstractClass();
        $urlBuilderForTest->expects($this->atLeastOnce())
            ->method('getUrl')
            ->willReturnCallback(function ($route, $params = []) use ($rmaId) {
                return 'http://localhost/admin/' . str_replace('*/*', 'rma', $route)
                    . '/id/' . ($params['entity_id'] ?? $rmaId);
            });

        $requestForTest = $this->getMockBuilder(RequestInterface::class)
            ->addMethods(['getServer'])
            ->getMockForAbstractClass();
        $requestForTest->method('getServer')->willReturn('');

        // Create new context for this test
        $contextForTest = $this->objectManager->getObject(
            WidgetContext::class,
            [
                'request' => $requestForTest,
                'urlBuilder' => $urlBuilderForTest,
                'escaper' => $escaperForTest,
                'buttonList' => $buttonListForTest
            ]
        );

        // Create RMA model mock with proper status (not closed)
        $rmaForTest = $this->createMock(Rma::class);
        $rmaForTest->method('getOrderId')->willReturn(1);
        $rmaForTest->method('getCustomerId')->willReturn(1);
        $rmaForTest->method('getStatus')->willReturn('pending'); // Not a closed status
        $rmaForTest->method('getId')->willReturn($rmaId);

        // Create registry mock that returns our RMA
        $registryForTest = $this->createMock(\Magento\Framework\Registry::class);
        $registryForTest->expects($this->any())
            ->method('registry')
            ->with('current_rma')
            ->willReturn($rmaForTest);

        // Create Edit model which will trigger _construct and escaper calls
        $editForTest = $this->objectManager->getObject(
            Edit::class,
            [
                'context' => $contextForTest,
                'registry' => $registryForTest
            ]
        );

        // Test that the close URL method works
        $closeUrl = $editForTest->getCloseUrl();
        $this->assertStringContainsString('/close/', $closeUrl);
    }
}
