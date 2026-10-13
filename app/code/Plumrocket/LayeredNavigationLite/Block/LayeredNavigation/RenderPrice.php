<?php
/**
 * @package     Plumrocket_LayeredNavigationLite
 * @copyright   Copyright (c) 2022 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

namespace Plumrocket\LayeredNavigationLite\Block\LayeredNavigation;

use Magento\Framework\View\Element\Template;

/**
 * @since 1.0.0
 */
class RenderPrice extends Template
{

    public const FILTER_PRICE_SLIDER_TEMPLATE = 'Plumrocket_LayeredNavigationLite::layer/renderer/price/slider.phtml';

    public const FILTER_PRICE_REQUEST_VAR = 'price';

    /**
     * @var \Magento\Framework\Json\Helper\Data
     */
    protected $_jsonHelper;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     */
    protected $productCollectionFactory;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\Collection $originalCollection
     */
    protected $originalCollection = null;

    /**
     * @param \Magento\Framework\View\Element\Template\Context               $context
     * @param \Magento\Framework\Json\Helper\Data                            $jsonHelper
     * @param \Magento\Framework\Registry                                    $registry
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory
     * @param array                                                          $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\Framework\Registry $registry,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        array $data = []
    ) {
        $this->_jsonHelper = $jsonHelper;
        parent::__construct($context, $data);
        $this->registry = $registry;
        $this->productCollectionFactory = $productCollectionFactory;
    }

    /**
     * Get Items
     *
     * @return array
     */
    public function getItems()
    {
        return $this->getFilter()->getItems();
    }

    /**
     * @inheritDoc
     */
    protected function _toHtml()
    {
        $this->assign('filterItems', $this->getItems());
        $html = $this->renderPriceTemplate();
        $this->assign('filterItems', []);
        return $html;
    }

    /**
     * Render price template.
     *
     * @return string
     */
    protected function renderPriceTemplate(): string
    {
        $this->setTemplate(self::FILTER_PRICE_SLIDER_TEMPLATE);
        return parent::_toHtml();
    }

    /**
     * Retrieve from value
     *
     * @return string
     */
    public function getFromValue()
    {
        $fromRequest = $this->_getRequestedPrice();
        return $fromRequest['min'] ?? $this->getMinValue();
    }

    /**
     * Retrieve "to'" value
     *
     * @return string
     */
    public function getToValue()
    {
        $fromRequest = $this->_getRequestedPrice();
        return $fromRequest['max'] ?? $this->getMaxValue();
    }

    /**
     * Get min value
     *
     * @return string
     */
    public function getMinValue()
    {
        if (null !== $this->_request->getParam(self::FILTER_PRICE_REQUEST_VAR)
            && !$this->_request->isXmlHttpRequest()
        ) {
            return $this->getOriginalMinValue();
        }

        $minPrice = $this->getFilter()
                         ->getLayer()
                         ->getProductCollection()
                         ->getMinPrice();

        return round($minPrice, 2);
    }

    /**
     * Get original collection min price value
     *
     * @return float
     */
    private function getOriginalMinValue()
    {
        /** @var \Magento\Catalog\Model\ResourceModel\Product\Collection $collection */
        $collection = $this->getOriginalCollection();

        return $collection->getMinPrice();
    }

    /**
     * Get max value
     *
     * @return string
     */
    public function getMaxValue()
    {
        if (null !== $this->_request->getParam(self::FILTER_PRICE_REQUEST_VAR)
            && !$this->_request->isXmlHttpRequest()
        ) {
            return $this->getOriginalMaxValue();
        }

        $maxPrice = $this->getFilter()
                         ->getLayer()
                         ->getProductCollection()
                         ->getMaxPrice();

        return round($maxPrice, 2);
    }

    /**
     * Get original collection max price value
     *
     * @return float
     */
    private function getOriginalMaxValue()
    {
        /** @var \Magento\Catalog\Model\ResourceModel\Product\Collection $collection */
        $collection = $this->getOriginalCollection();

        return $collection->getMaxPrice();
    }

    /**
     * Retrieve not filtered collection with price data
     *
     * @return \Magento\Catalog\Model\ResourceModel\Product\Collection\
     */
    private function getOriginalCollection()
    {
        if (null === $this->originalCollection) {
            $category = $this->registry->registry('current_category');
            $this->originalCollection = $this->productCollectionFactory->create()
                ->addPriceData();

            if ($category) {
                $this->originalCollection->addCategoryFilter($category);
            }
        }

        return $this->originalCollection;
    }

    /**
     * Retrieve prices from request
     *
     * @return string
     */
    public function getRequestedPrice()
    {
        $result = $this->_getRequestedPrice();
        return $this->_jsonHelper->jsonEncode($result);
    }

    /**
     * Retrieve requested price
     *
     * @return array
     */
    private function _getRequestedPrice()
    {
        $result = [];
        if ($this->_request->getParam(self::FILTER_PRICE_REQUEST_VAR)) {
            $prices = array_map(
                'floatval',
                explode('-', $this->_request->getParam(self::FILTER_PRICE_REQUEST_VAR))
            );

            if (!empty($prices[0])) {
                $result['min'] = round($prices[0] * $this->getCurrentCurrencyRate(), 2);
            }

            if (!empty($prices[1])) {
                $result['max'] = round($prices[1] * $this->getCurrentCurrencyRate(), 2);
            }
        }

        return $result;
    }

    /**
     * Retrieve currency symbol
     *
     * @return string
     */
    public function getCurrencySymbol()
    {
        return $this->_storeManager->getStore()->getCurrentCurrency()->getCurrencySymbol();
    }

    /**
     * Get current currency rate
     *
     * @return float
     */
    public function getCurrentCurrencyRate()
    {
        $currencyRate = (float)$this->_storeManager->getStore()->getCurrentCurrencyRate();
        return $currencyRate ?: 1;
    }
}
