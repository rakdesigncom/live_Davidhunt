<?php

namespace Fyb\Theme\Controller\Category;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Helper\Category as CategoryHelper;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Category\Attribute\LayoutUpdateManager;
use Magento\Catalog\Model\Design;
use Magento\Catalog\Model\Layer\Resolver;
use Magento\Catalog\Model\Product\ProductList\ToolbarMemorizer;
use Magento\Catalog\Model\Session;
use Magento\CatalogUrlRewrite\Model\CategoryUrlPathGenerator;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\Controller\Result\ForwardFactory;
use Magento\Framework\DataObject;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class View extends \Magento\Catalog\Controller\Category\View
{
    private $layerResolver;

    public function __construct(
        Context $context,
        Design $catalogDesign,
        Session $catalogSession,
        Registry $coreRegistry,
        StoreManagerInterface $storeManager,
        CategoryUrlPathGenerator $categoryUrlPathGenerator,
        PageFactory $resultPageFactory,
        ForwardFactory $resultForwardFactory,
        Resolver $layerResolver,
        CategoryRepositoryInterface $categoryRepository,
        ToolbarMemorizer $toolbarMemorizer = null,
        ?LayoutUpdateManager $layoutUpdateManager = null,
        CategoryHelper $categoryHelper = null,
        LoggerInterface $logger = null
    ) {
        parent::__construct(
            $context, $catalogDesign, $catalogSession, $coreRegistry, $storeManager, $categoryUrlPathGenerator,
            $resultPageFactory, $resultForwardFactory, $layerResolver, $categoryRepository, $toolbarMemorizer,
            $layoutUpdateManager, $categoryHelper, $logger
        );

        $this->layerResolver = $layerResolver;
    }

    public function execute()
    {
        $result = null;

        if ($this->_request->getParam(ActionInterface::PARAM_NAME_URL_ENCODED)) {
            //phpcs:ignore Magento2.Legacy.ObsoleteResponse
            return $this->resultRedirectFactory->create()->setUrl($this->_redirect->getRedirectUrl());
        }
        $category = $this->_initCategory();

        if ($category) {
            $isMenuViewCategory = $this->getRequest()->getParam('menu') && $category->hasChildren();
            if ($isMenuViewCategory) {
                $category->setIsAnchor(false);
                $category->setData('display_mode', 'PAGE');
                $category->setData('page_layout', '1column');
            }

            $this->layerResolver->create(Resolver::CATALOG_LAYER_CATEGORY);
            $settings = $this->_catalogDesign->getDesignSettings($category);
            if ($isMenuViewCategory) {
                $settings = $settings->addData(array_merge($settings->getData(), ['page_layout' => '1column']));
            }

            // apply custom design
            if ($settings->getCustomDesign()) {
                $this->_catalogDesign->applyCustomDesign($settings->getCustomDesign());
            }

            $this->_catalogSession->setLastViewedCategoryId($category->getId());

            $page = $this->resultPageFactory->create();
            // apply custom layout (page) template once the blocks are generated
            if ($settings->getPageLayout()) {
                $page->getConfig()->setPageLayout($settings->getPageLayout());
            }

            $pageType = $this->getPageType($category);

            if (!$category->hasChildren()) {
                // Two levels removed from parent.  Need to add default page type.
                $parentPageType = strtok($pageType, '_');
                $page->addPageLayoutHandles(['type' => $parentPageType], null, false);
            }
            $page->addPageLayoutHandles(['type' => $pageType], null, false);
            $categoryDisplayMode = is_string($category->getDisplayMode()) ?
                strtolower($category->getDisplayMode()) : '';

            $page->addPageLayoutHandles(['displaymode' => $categoryDisplayMode], null, false);
            $page->addPageLayoutHandles(['id' => $category->getId()]);

            // apply custom layout update once layout is loaded
            $this->applyLayoutUpdates($page, $settings);

            $page->getConfig()->addBodyClass('page-products')
                ->addBodyClass('categorypath-' . $this->categoryUrlPathGenerator->getUrlPath($category))
                ->addBodyClass('category-' . $category->getUrlKey());

            return $page;
        } elseif (!$this->getResponse()->isRedirect()) {
            $result = $this->resultForwardFactory->create()->forward('noroute');
        }
        return $result;
    }

    private function getPageType(Category $category) : string
    {
        $hasChildren = $category->hasChildren();
        if ($category->getIsAnchor()) {
            return  $hasChildren ? 'layered' : 'layered_without_children';
        }

        return $hasChildren ? 'default' : 'default_without_children';
    }

    private function applyLayoutUpdates(
        Page $page,
        DataObject $settings
    ) {
        $layoutUpdates = $settings->getLayoutUpdates();
        if ($layoutUpdates && is_array($layoutUpdates)) {
            foreach ($layoutUpdates as $layoutUpdate) {
                $page->addUpdate($layoutUpdate);
                $page->addPageLayoutHandles(['layout_update' => sha1($layoutUpdate)], null, false);
            }
        }

        //Selected files
        if ($settings->getPageLayoutHandles()) {
            $page->addPageLayoutHandles($settings->getPageLayoutHandles());
        }
    }
}
