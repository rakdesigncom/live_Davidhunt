<?php

namespace Fyb\Product\Block\Product\ProductList;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Block\Product\Context;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility as ProductVisibility;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Checkout\Model\ResourceModel\Cart as CartResourceModel;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Module\Manager;

class Downloadable extends \Magento\Catalog\Block\Product\ListProduct
{
    /**
     * @var Collection
     */
    protected $_itemCollection;

    /**
     * Checkout session
     *
     * @var CheckoutSession
     */
    protected $_checkoutSession;

    /**
     * Catalog product visibility
     *
     * @var ProductVisibility
     */
    protected $_catalogProductVisibility;

    /**
     * Checkout cart
     *
     * @var CartResourceModel
     */
    protected $_checkoutCart;

    /**
     * @var Manager
     */
    protected $moduleManager;

    protected $_productCollectionFactory;
    protected $categoryFactory;

    /**
     * @param Context $context
     * @param \Magento\Framework\Data\Helper\PostHelper $postDataHelper
     * @param \Magento\Catalog\Model\Layer\Resolver $layerResolver
     * @param \Magento\Catalog\Api\CategoryRepositoryInterface $categoryRepository
     * @param \Magento\Framework\Url\Helper\Data $urlHelper
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory
     * @param CartResourceModel $checkoutCart
     * @param ProductVisibility $catalogProductVisibility
     * @param CheckoutSession $checkoutSession
     * @param Manager $moduleManager
     * @param array $data
     */
    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        \Magento\Framework\Data\Helper\PostHelper $postDataHelper,
        \Magento\Catalog\Model\Layer\Resolver $layerResolver,
        CategoryRepositoryInterface $categoryRepository,
        \Magento\Framework\Url\Helper\Data $urlHelper,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        CartResourceModel $checkoutCart,
        ProductVisibility $catalogProductVisibility,
        CheckoutSession $checkoutSession,
        Manager $moduleManager,
        array $data = []
    ) {
        $this->_checkoutCart = $checkoutCart;
        $this->_catalogProductVisibility = $catalogProductVisibility;
        $this->_checkoutSession = $checkoutSession;
        $this->moduleManager = $moduleManager;
        $this->_productCollectionFactory = $productCollectionFactory;
        $this->categoryFactory = $categoryFactory;

        parent::__construct(
            $context,
            $postDataHelper,
            $layerResolver,
            $categoryRepository,
            $urlHelper,
            $data
        );
    }

    /**
     * @inheridoc
     */
    protected function _getProductCollection()
    {
        if ($this->_productCollection === null) {
            $catalogueCategory = $this->getCatalogueCategoryId();
            /** @var \Magento\Catalog\Model\ResourceModel\Product\Collection $collection */
            $collection = $this->_productCollectionFactory->create();
            $collection->addAttributeToSelect('*')
                ->addStoreFilter()
                ->addCategoryFilter($catalogueCategory)
                ->addMediaGalleryData();

            $collection->addAttributeToFilter(
                'visibility',
                ['in' => $this->_catalogProductVisibility->getVisibleInCatalogIds()]
            )->addAttributeToFilter('status', Status::STATUS_ENABLED);

            if ($this->moduleManager->isEnabled('Magento_Checkout')) {
                $this->_addProductAttributesAndPrices($collection);
            }

            $collection->addAttributeToSort('position');
            $collection->load();
            foreach ($collection as $product) {
                $product->setDoNotUseCategoryId(true);
            }

            $this->_productCollection = $collection;
        }
        return $this->_productCollection;
    }

    public function getMediaUrl()
    {
        $om = \Magento\Framework\App\ObjectManager::getInstance();
        $storeManager = $om->get('Magento\Store\Model\StoreManagerInterface');
        $currentStore = $storeManager->getStore();
        return $currentStore->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
    }

    protected function getCatalogueCategoryId()
    {
        $rootCat = $this->categoryFactory->create()->load($this->_storeManager->getStore()->getRootCategoryId());
        $collection = $this->categoryFactory->create()
            ->getCollection()
            ->addAttributeToFilter('name', 'Catalogues')->setPageSize(1);
        $collection->addPathFilter($rootCat->getPath());

        return $collection->getFirstItem();
    }

    /**
     * Before to html handler
     *
     * @return $this
     */
    protected function _beforeToHtml()
    {
        $this->_getProductCollection();
        return parent::_beforeToHtml();
    }
}
