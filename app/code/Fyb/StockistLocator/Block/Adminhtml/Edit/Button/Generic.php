<?php
declare(strict_types=1);

namespace Fyb\StockistLocator\Block\Adminhtml\Edit\Button;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\UiComponent\Context;
use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class Generic implements ButtonProviderInterface
{
    /**
     * Url Builder
     *
     * @var Context
     */
    protected $context;

    /**
     * Registry
     *
     * @var Registry
     */
    protected $registry;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

    /**
     * Generic constructor
     *
     * @param Context $context
     * @param Registry $registry
     */
    public function __construct(
        Context $context,
        Registry $registry,
        RequestInterface $request,
    ) {
        $this->context = $context;
        $this->registry = $registry;
        $this->request = $request;
    }

    /**
     * Generate url by route and parameters
     *
     * @param string $route
     * @param array $params
     * @return string
     */
    public function getUrl($route = '', $params = [])
    {
        return $this->context->getUrl($route, $params);
    }

    public function getStockist()
    {
        return $this->registry->registry('stockist');
    }

    public function getStockistId()
    {
        return  $this->request->getParam('id');
    }

    /**
     * {@inheritdoc}
     */
    public function getButtonData()
    {
        return [];
    }
}
