<?php
/**
 * @package     Plumrocket_LayeredNavigationLite
 * @copyright   Copyright (c) 2022 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

namespace Plumrocket\LayeredNavigationLite\Plugin\Block\LayeredNavigation\Navigation;

/**
 * @since 1.0.0
 */
class FilterRenderer
{

    /**
     * @var \Magento\Framework\View\LayoutInterface
     */
    protected $layout;

    /**
     * @var array
     */
    protected $optionValues = [];

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var \Magento\Framework\Module\Manager
     */
    private $moduleManager;

    /**
     * @var \Plumrocket\LayeredNavigationLite\Helper\Config
     */
    private $config;

    /**
     * @var \Plumrocket\LayeredNavigationLite\Model\FilterItem\Status
     */
    private $itemStatus;

    /**
     * @var array
     */
    private $filterRenderers;

    /**
     * @param \Magento\Framework\View\LayoutInterface $layout
     * @param \Magento\Framework\Module\Manager $moduleManager
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Plumrocket\LayeredNavigationLite\Helper\Config $config
     * @param \Plumrocket\LayeredNavigationLite\Model\FilterItem\Status $itemStatus
     * @param array $filterRenderers
     */
    public function __construct(
        \Magento\Framework\View\LayoutInterface $layout,
        \Magento\Framework\Module\Manager $moduleManager,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Plumrocket\LayeredNavigationLite\Helper\Config $config,
        \Plumrocket\LayeredNavigationLite\Model\FilterItem\Status $itemStatus,
        array $filterRenderers = []
    ) {
        $this->layout = $layout;
        $this->moduleManager = $moduleManager;
        $this->objectManager = $objectManager;
        $this->config = $config;
        $this->itemStatus = $itemStatus;
        $this->filterRenderers = $filterRenderers;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @param \Magento\LayeredNavigation\Block\Navigation\FilterRenderer $subject
     * @param \Closure $proceed
     * @param \Magento\Catalog\Model\Layer\Filter\FilterInterface $filter
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function aroundRender(
        \Magento\LayeredNavigation\Block\Navigation\FilterRenderer $subject,
        \Closure $proceed,
        \Magento\Catalog\Model\Layer\Filter\FilterInterface $filter
    ) {
        $isAmpRequest = false;

        if ($this->moduleManager->isEnabled('Plumrocket_Amp')) {
            $isAmpRequest = $this->objectManager->get(\Plumrocket\AmpApi\Api\IsAmpModeInterface::class)->execute();
        }

        if ($isAmpRequest || ! $this->config->isModuleEnabled()) {
            return $proceed($filter);
        }

        foreach ($filter->getItems() as $item) {
            $this->refactorRewritedValue($item);
        }
        $this->itemStatus->markActiveItems($filter->getItems());
        $this->optionValues = [];

        $block = null;
        foreach ($this->filterRenderers as $filterClass => $filterRenderer) {
            if ($filter instanceof $filterClass) {
                $block = $filterRenderer;
            }
        }
        if ($block) {
            return $this->layout->createBlock($block)->setFilter($filter)->toHtml();
        }

        return $proceed($filter);
    }

    /**
     * Set rewritten value.
     *
     * @param \Plumrocket\LayeredNavigationLite\Model\Catalog\Layer\Filter\Item $option
     */
    private function refactorRewritedValue($option): void
    {
        $value = $option->getRewritedValue();

        if (isset($this->optionValues[$value])) {
            $value .= '_' . $option->getValue();
            $option->setRewritedValue($value);
        }

        $this->optionValues[$value] = $value;
    }
}
