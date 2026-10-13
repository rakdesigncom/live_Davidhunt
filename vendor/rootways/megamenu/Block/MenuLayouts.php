<?php
namespace Rootways\Megamenu\Block;

use Magento\Catalog\Model\Category as ModelCategory;
use Magento\Framework\View\Element\Template;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Store\Model\StoreManagerInterface;

class MenuLayouts extends Template
{
    /**
     * @var \Magento\Catalog\Model\CategoryRepository
     */
    protected $categoryRepository;

    /**
     * @var \Magento\Catalog\Helper\Category
     */
    protected $_categoryHelper;

    /**
     * @var \Magento\Catalog\Model\Indexer\Category\Flat\State
     */
    protected $categoryFlatConfig;

    /**
     * @var \Magento\Theme\Block\Html\Topmenu
     */
    protected $topMenu;

    /**
     * @var \Magento\Cms\Model\Template\FilterProvider
     */
    protected $_filterProvider;

    /**
     * @var \Rootways\Megamenu\Helper\Data
     */
    protected $_customhelper;

    /**
     * @var \Magento\Config\Model\ResourceModel\Config
     */
    protected $resourceConfig;

    /**
     * @var \Rootways\Megamenu\Model\Category\DataProvider\Plugin
     */
    protected $_customcatimage;

    /**
     * @var \Magento\Config\Model\ResourceModel\Config
     */
    protected $_customresourceConfig;

    /**
     * @var CategoryCollectionFactory
     */
    protected $categoryCollectionFactory;

    /** @var StoreManagerInterface */
    protected $storeManager;

    protected $_categoryCollection;

    protected $_baseUrl;

    /**
     * Store categories cache
     *
     * @var array
     */
    protected $_storeCategories = [];

    /**
     * @var \Magento\Catalog\Model\CategoryFactory
     */
    protected $_categoryFactory;

    /**
     * Lib data collection factory
     *
     * @var \Magento\Framework\Data\CollectionFactory
     */
    protected $_dataCollectionFactory;

    /**
     * @param Template\Context $context
     * @param \Magento\Catalog\Model\CategoryRepository $categoryRepository
     * @param \Magento\Catalog\Helper\Category $categoryHelper
     * @param \Magento\Catalog\Model\Indexer\Category\Flat\State $categoryFlatState
     * @param \Magento\Theme\Block\Html\Topmenu $topMenu
     * @param \Magento\Cms\Model\Template\FilterProvider $filterProvider
     * @param \Rootways\Megamenu\Helper\Data $helper
     * @param \Rootways\Megamenu\Model\Category\DataProvider\Plugin $customCatImage
     * @param \Magento\Config\Model\ResourceModel\Config $resourceConfig
     * @param CategoryCollectionFactory $categoryCollectionFactory
     * @param StoreManagerInterface $storeManager
     * @param \Magento\Catalog\Model\CategoryFactory $categoryFactory
     * @param \Magento\Framework\Data\CollectionFactory $dataCollectionFactory
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Catalog\Model\CategoryRepository $categoryRepository,
        \Magento\Catalog\Helper\Category $categoryHelper,
        \Magento\Catalog\Model\Indexer\Category\Flat\State $categoryFlatState,
        \Magento\Theme\Block\Html\Topmenu $topMenu,
        \Magento\Cms\Model\Template\FilterProvider $filterProvider,
        \Rootways\Megamenu\Helper\Data $helper,
        \Rootways\Megamenu\Model\Category\DataProvider\Plugin $customCatImage,
        \Magento\Config\Model\ResourceModel\Config $resourceConfig,
        CategoryCollectionFactory $categoryCollectionFactory,
        StoreManagerInterface $storeManager,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        \Magento\Framework\Data\CollectionFactory $dataCollectionFactory
    ) {
        $this->categoryRepository = $categoryRepository;
        $this->_categoryHelper = $categoryHelper;
        $this->categoryFlatConfig = $categoryFlatState;
        $this->topMenu = $topMenu;
        $this->_filterProvider = $filterProvider;
        $this->_customhelper = $helper;
        $this->_customcatimage = $customCatImage;
        $this->_customresourceConfig = $resourceConfig;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->storeManager = $storeManager;
        $this->_categoryFactory = $categoryFactory;
        $this->_dataCollectionFactory = $dataCollectionFactory;
        parent::__construct($context);
    }

    /**
     * Retrieve current store categories
     *
     * @param bool|string $sorted
     * @param bool $asCollection
     * @param bool $toLoad
     * @return \Magento\Framework\Data\Tree\Node\Collection or
     * \Magento\Catalog\Model\ResourceModel\Category\Collection or array
     */
    /*public function getSpecificCategoryAsStoreParentCategory($parent = 2,$sorted = false, $asCollection = false, $toLoad = true)
    {
        //$parent = $this->_storeManager->getStore()->getRootCategoryId();
        $cacheKey = sprintf('%d-%d-%d-%d', $parent, $sorted, $asCollection, $toLoad);
        if (isset($this->_storeCategories[$cacheKey])) {
            return $this->_storeCategories[$cacheKey];
        }
        $category = $this->_categoryFactory->create();
        if (!$category->checkId($parent)) {
            if ($asCollection) {
                return $this->_dataCollectionFactory->create();
            }
            return [];
        }

        $recursionLevel = 0;
        $storeCategories = $category->getCategories($parent, $recursionLevel, $sorted, $asCollection, $toLoad);

        $this->_storeCategories[$cacheKey] = $storeCategories;
        return $storeCategories;
    }*/

    public function getLoadedCat($childCategory)
    {
        if (gettype($childCategory) == 'object') {
            $load_cat = $this->categoryRepository->get($childCategory->getId(), $this->_customhelper->getStoreId());
        } else {
            $load_cat = $this->categoryRepository->get($childCategory, $this->_customhelper->getStoreId());
        }

        return $load_cat;
    }
}
