<?php

namespace Rakdesign\Configurator\Plugin;

use Magento\Catalog\Helper\Product\View as ProductViewHelper;
use Magento\Catalog\Model\Product;
use Magento\Framework\DataObject;
use Magento\Framework\View\Result\Page as ResultPage;
use Magento\Framework\View\Result\Page;

class AddBuildLayoutHandle
{
    const PRODUCT_LAYOUT_HANDLE = 'catalog_product_view_type_build';

    /**
     * @var \Rakdesign\Configurator\Model\Shadelogic
     */
    protected $rakdesignConfiguratorLogic;

    public function __construct(
        \Rakdesign\Configurator\Model\Shadelogic $rakdesignConfiguratorLogic,
    )
    {
        $this->rakdesignConfiguratorLogic = $rakdesignConfiguratorLogic;
    }

    public function beforeInitProductLayout(
        ProductViewHelper $subject,
        $resultPage,
        $product,
        $params
    ) {
        if ($resultPage instanceof ResultPage) {
            $configuration = $this->rakdesignConfiguratorLogic->getConfiguration($product);

            if ($configuration) {
                $resultPage->addHandle([self::PRODUCT_LAYOUT_HANDLE]);
                $resultPage->getConfig()->addBodyClass('configurator-product-page');

                $pageMainTitle = $resultPage->getLayout()->getBlock('page.main.title');
                if ($pageMainTitle) {
                    $pageMainTitle->setPageTitle(__('Build Your Own'));
                }
            }
        }

        return [
            $resultPage,
            $product,
            $params,
        ];
    }
}
