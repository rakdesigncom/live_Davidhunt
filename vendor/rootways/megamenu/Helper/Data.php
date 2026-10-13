<?php
/**
 * Helper file to get data.
 *
 * @category  Site Search & Navigation
 * @package   Rootways_Megamenu
 * @author    Developer RootwaysInc <developer@rootways.com>
 * @copyright 2023 Rootways Inc. (https://www.rootways.com)
 * @license   Rootways Custom License
 * @link      https://www.rootways.com/pub/media/extension_doc/license_agreement.pdf
 */
namespace Rootways\Megamenu\Helper;

use Magento\Backend\Helper\Data as BackendHelper;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\Action\Context as ContextAction;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\App\ProductMetadataInterface;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const RW_MENU_CONFIG_GENERAL = "rootmegamenu_option/general";

    protected $_category;

    /**
     * @var \Magento\Catalog\Helper\Category
     */
    protected $_categoryHelper;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Catalog\Model\CategoryFactory
     */
    protected $_categoryFactory;

    /**
     * @var \Magento\Catalog\Model\Indexer\Category\Flat\State
     */
    protected $_categoryFlatConfig;

    /**
     * @var \Magento\Cms\Model\Template\FilterProvider
     */
    protected $_filterProvider;

    /**
     * @var \Magento\Framework\Filesystem\DirectoryList
     */
    protected $directoryList;

    /**
     * @var \Magento\Framework\Filesystem\Driver\File
     */
    protected $fileDriver;

    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var Json
     */
    protected $_json;

    /**
     * @var ProductMetadataInterface
     */
    protected $productMetadataInterface;

    /**
     * @var ProductRepositoryInterface
     */
    protected $_productRepository;

    /**
     * @var BackendHelper
     */
    protected $backendHelper;

    /**
     * @var \Magento\Config\Model\ResourceModel\Config
     */
    protected $resourceConfig;

    /**
     * @var ContextAction
     */
    protected $contextAction;

    /**
     * @var Registry
     */
    protected $registry;

    protected $_magentoVersion;

    protected $_requiredSettings;

    /**
     * Data Helper.
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Catalog\Helper\Category $categoryHelper
     * @param \Magento\Catalog\Model\CategoryFactory $categoryFactory,
     * @param \Magento\Catalog\Model\Indexer\Category\Flat\State $categoryFlatState,
     * @param \Magento\Cms\Model\Template\FilterProvider $filterProvider
     * @param \Magento\Framework\Filesystem\DirectoryList $directoryList
     * @param \Magento\Framework\Filesystem\Driver\File $fileDriver
     * @param PageFactory $resultPageFactory,
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param Json $json
     * @param ProductMetadataInterface $productMetadataInterface
     * @param ProductRepositoryInterface $productRepository
     * @param BackendHelper $backendHelper
     * @param \Magento\Config\Model\ResourceModel\Config $resourceConfig
     * @param ContextAction $contextAction
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Catalog\Helper\Category $categoryHelper,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        \Magento\Catalog\Model\Indexer\Category\Flat\State $categoryFlatState,
        \Magento\Cms\Model\Template\FilterProvider $filterProvider,
        \Magento\Framework\Filesystem\DirectoryList $directoryList,
        \Magento\Framework\Filesystem\Driver\File $fileDriver,
        PageFactory $resultPageFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        Json $json,
        ProductMetadataInterface $productMetadataInterface,
        ProductRepositoryInterface $productRepository,
        BackendHelper $backendHelper,
        \Magento\Config\Model\ResourceModel\Config $resourceConfig,
        ContextAction $contextAction,
        Registry $registry
    ) {
        $this->_storeManager = $storeManager;
        $this->_categoryFactory = $categoryFactory;
        $this->_categoryFlatConfig = $categoryFlatState;
        $this->_categoryHelper = $categoryHelper;
        $this->resultPageFactory = $resultPageFactory;
        $this->_filterProvider = $filterProvider;
        $this->directoryList = $directoryList;
        $this->fileDriver = $fileDriver;
        $this->_json = $json;
        $this->productMetadataInterface = $productMetadataInterface;
        $this->_productRepository= $productRepository;
        $this->backendHelper = $backendHelper;
        $this->resourceConfig = $resourceConfig;
        $this->contextAction = $contextAction;
        $this->registry = $registry;
        parent::__construct($context);
    }

    public function getConfig($config_path, $storeId = null)
    {
        return $this->scopeConfig->getValue(
            $config_path,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function getRequiredSettings($storeId = null)
    {
        if (!$this->_requiredSettings) {
            $settingCollection = [
                'is_active' => $this->getConfig('rootmegamenu_option/general/enable', $this->getStoreId()),
                'show_home' => $this->getConfig('rootmegamenu_option/general/show_home_link', $this->getStoreId()),
                'show_contactus' => $this->getConfig('rootmegamenu_option/general/show_contactus', $this->getStoreId()),
                'show_viewmore' => $this->getConfig('rootmegamenu_option/general/show_view_more', $this->getStoreId()),
                'topmenu_icon' => $this->getConfig('rootmegamenu_option/general/topmenu_icon', $this->getStoreId()),
                'topmenuarrow' => $this->getConfig('rootmegamenu_option/general/topmenuarrow', $this->getStoreId()),
                'custom_link' => $this->getConfig('rootmegamenu_option/general/custom_link', $this->getStoreId()),
                'show_social_share' => $this->getConfig('rootmegamenu_option/general/show_social_share_icon', $this->getStoreId()),
                'topmenualignmenttype' => $this->getConfig('rootmegamenu_option/general/topmenualignmenttype', $this->getStoreId())
            ];

            $this->_requiredSettings = $settingCollection;
        }

        return $this->_requiredSettings;
    }

    public function getCssDir()
    {
        return BP.'/pub/media/rootways/megamenu/';
    }

    public function getMediaUrl()
    {
        return $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
    }

    public function getStoreCss()
    {
        return $this->getMediaUrl(). 'rootways/megamenu/' . 'menu_' . $this->_storeManager->getStore()->getCode() . '.css';
    }

    public function surl()
    {
        return "aHR0cHM6Ly93d3cucm9vdHdheXMuY29tL3ZhbGlkYXRla2V5YnljbGljay5waHA=";
    }

    public function getStoreFrontendBaseUrl() {
        return $this->_storeManager->getStore()->getBaseUrl();
    }

    public function getStoreBackEndBaseUrl() {
        return $this->backendHelper->getHomePageUrl();
    }

    /**
     * Get value of licence key from admin
     */
    public function act()
    {
        if ($this->getConfig('rootmegamenu_option/general/lcstatus') == '') {
            return "PGRpdiBzdHlsZT0iYmFja2dyb3VuZDogYmxhY2sgbm9uZSByZXBlYXQgc2Nyb2xsIDAlIDAlOyBmbG9hdDogbGVmdDsgcGFkZGluZzogMTBweDsgd2lkdGg6IDEwMCU7IGNvbG9yOiByZWQ7IiBpZD0ibm90X2FjdGl2YXRlZCI+Um9vdHdheXMnIE1lZ2EgTWVudSBleHRlbnNpb24gaXMgbm90IGFjdGl2YXRlZCBkdWUgdG8gYW4gaW5jb3JyZWN0IG9yIG1pc3NpbmcgbGljZW5zZSBrZXkuPC9kaXY+";
        }

        return '';
    }

    public function isJson($string){
        return is_string($string) && is_array(json_decode($string, true)) ? true : false;
    }

    public function getMagentoVersion()
    {
        if (!$this->_magentoVersion) {
            $this->_magentoVersion = $this->productMetadataInterface->getVersion();
        }

        return $this->_magentoVersion;
    }

    public function manageMasonry()
    {
        return $this->getConfig('rootmegamenu_option/general/manage_masonry');
    }

    public function masonryCategory()
    {
        $value = [];
        $masonryCategories = $this->getConfig('rootmegamenu_option/general/masonry_category');
        if ($masonryCategories != '') {
            $value = explode(",", $masonryCategories);
        }
        return $value;
    }

    public function getCurrentStore()
    {
        return $this->_storeManager->getStore();
    }

    public function getRootCategoryId()
    {
        return $this->_storeManager->getStore()->getRootCategoryId();
    }

    public function getStoreId()
    {
        return $this->_storeManager->getStore()->getId();
    }

    public function getCategory($categoryId)
    {
        $this->_category = $this->_categoryFactory->create();
        $this->_category->load($categoryId);
        return $this->_category;
    }

    public function getMegaMenuImageName($currentCat)
    {
        if ($this->getConfig('rootmegamenu_option/general/image_source') == 2) {
            $imgName = $currentCat->getThumbnail();
        } elseif ($this->getConfig('rootmegamenu_option/general/image_source') == 1) {
            $imgName = $currentCat->getImageUrl();
        } else {
            $imgName = $currentCat->getMegamenuShowCatimageImg();
        }
        return $imgName;
    }

    public function getMegaMenuImageUrl($currentCat)
    {
        $imageurl = '';
        if ($this->getConfig('rootmegamenu_option/general/image_source') == 2) {
            // Use Magento Default Thumbnail Image
            if ($currentCat->getThumbnail() != '') {
                $imgName = $currentCat->getThumbnail();
                if ($this->getMagentoVersion() >= '2.3.4') {
                    $imgName = str_replace("/pub/media/catalog/category/", "", $currentCat->getThumbnail());
                }
                $imageurl = $this->getMediaUrl() . 'catalog/category/' . $imgName;

            }
        } elseif ($this->getConfig('rootmegamenu_option/general/image_source') == 1) {
            // Use Magento Default Category Image
            if ($currentCat->getImageUrl() != '') {
                $imageurl = $currentCat->getImageUrl();
            }
        } else {
            // Use Rootways Mega Menu Category Image
            if ($currentCat->getMegamenuShowCatimageImg() != '') {
                /*
                if ($this->getMagentoVersion() >= '2.3.4') {
                    $imageurl = $this->_storeManager->getStore()->getBaseUrl().ltrim($currentCat->getMegamenuShowCatimageImg(), '/');
                } else {
                    $imageurl = $this->_storeManager->getStore()->getBaseUrl(
                        \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
                    ) . 'catalog/category/' . $currentCat->getMegamenuShowCatimageImg();
                }
                */
                //echo $currentCat->getMegamenuShowCatimageImg();exit;      ///pub/media/catalog/category/t-shirts_1.jpg
                $FileLocation = $this->directoryList->getRoot().'/'.ltrim($currentCat->getMegamenuShowCatimageImg(), '/');
                if ($this->fileDriver->isExists($FileLocation)) {
                    $imagename = $currentCat->getMegamenuShowCatimageImg();
                } else {
                    if (strpos($currentCat->getMegamenuShowCatimageImg(), "/tmp") !== false) {
                        $imagename = str_replace("/tmp", "", $currentCat->getMegamenuShowCatimageImg());
                    } else {
                        $imagename = $currentCat->getMegamenuShowCatimageImg();
                    }
                }
                if (strpos($imagename, "/media") !== false || strpos($imagename, "/pub") !== false) {
                    $imagename = str_replace("/pub", "", $imagename);
                    $imagename = str_replace("/media", "", $imagename);
                }
                if ($this->getMagentoVersion() >= '2.3.4') {
                    $imageurl = $this->getMediaUrl() . ltrim($imagename, '/');
                } else {
                    $imageurl = $this->getMediaUrl() . 'catalog/category/' . $imagename;
                }
            }
        }
        return $imageurl;
    }

    /**
     * @param $data
     * @return bool|false|string
     */
    public function getJsonEncode($data)
    {
        return $this->_json->serialize($data);
    }

    /**
     * @param $data
     * @return array|bool|float|int|mixed|string|null
     */
    public function getJsonDecode($data)
    {
        return $this->_json->unserialize($data);
    }

    /** @return string; */
    public function menuContainerClass() : string
    {
        if ($this->getConfig('rootmegamenu_option/general/topmenualignmenttype') == 3) {
            return 'page-main';
        }

        return '';
    }

    public function getCurrentCurrencyCode()
    {
        return $this->_storeManager->getStore()->getCurrentCurrencyCode();
    }

    public function loadProductById($id)
    {
        return $this->_productRepository->getById($id);
    }

    public function getActionName()
    {
        return $this->contextAction->getRequest()->getFullActionName();
    }

    public function getcurrentCategory()
    {
        return $this->registry->registry('current_category');
    }
}
