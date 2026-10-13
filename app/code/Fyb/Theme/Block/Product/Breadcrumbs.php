<?php

namespace Fyb\Theme\Block\Product;

use Magento\Catalog\Helper\Data;
use Magento\Framework\View\Element\Template\Context;

class Breadcrumbs extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Magento\Catalog\Helper\Data
     */
    protected $_catalogData;

    public function __construct(
        Context $context,
        Data $catalogData,
        array $data = []
    ) {
        $this->_catalogData = $catalogData;
        parent::__construct($context, $data);
    }

    public function getTitleSeparator($store = null)
    {
        $separator = (string)$this->_scopeConfig->getValue(
            'catalog/seo/title_separator',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
        return ' ' . $separator . ' ';
    }

    protected function _prepareLayout()
    {
        if ($breadcrumbsBlock = $this->getLayout()->getBlock('breadcrumbs')) {
            $breadcrumbsBlock->addCrumb(
                'home',
                [
                    'label' => __('Home'),
                    'title' => __('Go to Home Page'),
                    'link' => $this->_storeManager->getStore()->getBaseUrl()
                ]
            );

            $title = [];
            $path = $this->_catalogData->getBreadcrumbPath();
            $product = $this->_catalogData->getProduct();

            if ($product && count($path) == 1) {
                $category = $this->getProductLastCategory($product);
                if ($category && $category->getId()) {
                    $pathInStore = $category->getPathInStore();
                    $pathIds = array_reverse(explode(',', $pathInStore));
                    $categories = $category->getParentCategories();
                    $newPath = [];

                    foreach ($pathIds as $categoryId) {
                        if (isset($categories[$categoryId]) && $categories[$categoryId]->getName()) {
                            $newPath['category'.$categoryId] = [
                                'label' => $categories[$categoryId]->getName(),
                                'link' => $category->getUrl()
                            ];
                        }
                    }

                    if (isset($path['product'])) {
                        $newPath['product'] = $path['product'];
                    }

                    $path = $newPath;
                }
            }

            foreach ($path as $name => $breadcrumb) {
                $breadcrumbsBlock->addCrumb($name, $breadcrumb);
                $title[] = $breadcrumb['label'];
            }

            $this->pageConfig->getTitle()->set(join($this->getTitleSeparator(), array_reverse($title)));
        }

        return parent::_prepareLayout();
    }

    protected function getProductLastCategory($product)
    {
        $categoryCollection = clone $product->getCategoryCollection();
        $categoryCollection->clear();
        $categoryCollection->addAttributeToSort('level', $categoryCollection::SORT_ORDER_DESC)
            ->addAttributeToFilter(
                'path',
                ['like' => "1/" . $this->_storeManager->getStore()->getRootCategoryId() . "/%"]
            )->addAttributeToSelect(
                'name'
            )->addIsActiveFilter();
        $categoryCollection->setPageSize(1);

        return $categoryCollection->getFirstItem();
    }
}
