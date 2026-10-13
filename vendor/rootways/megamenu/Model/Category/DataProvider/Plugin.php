<?php
/**
 * Mega Menu Plugin Model.
 *
 * @category  Site Search & Navigation
 * @package   Rootways_Megamenu
 * @author    Developer RootwaysInc <developer@rootways.com>
 * @copyright 2023 Rootways Inc. (https://www.rootways.com)
 * @license   Rootways Custom License
 * @link      https://www.rootways.com/media/extension_doc/license_agreement.pdf
 */
namespace Rootways\Megamenu\Model\Category\DataProvider;

class Plugin
{
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Rootways\Megamenu\Helper\Data
     */
    protected $_customhelper;

    /**
     * Index constructor.
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Rootways\Megamenu\Helper\Data $helper
     */
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Rootways\Megamenu\Helper\Data $helper
    ) {
        $this->_storeManager = $storeManager;
        $this->_customhelper = $helper;
    }

    //retrieve thumnail data for output
    public function afterGetData(\Magento\Catalog\Model\Category\DataProvider $subject, $result)
    {
        if ($this->_customhelper->getMagentoVersion() < '2.3.4') {
            $category = $subject->getCurrentCategory();
            $categoryData = $result[$category->getId()];

            if (isset($categoryData['megamenu_show_catimage_img'])) {
                unset($categoryData['megamenu_show_catimage_img']);
                $categoryData['megamenu_show_catimage_img'][0]['name'] =
                $category->getData('megamenu_show_catimage_img');
                $categoryData['megamenu_show_catimage_img'][0]['url'] =
                $this->getThumbnailUrl($category->getData('megamenu_show_catimage_img'));
            }

            $result[$category->getId()] = $categoryData;
        }

        return $result;
    }

    public function beforeGetData(\Magento\Catalog\Model\Category\DataProvider $subject)
    {
        if ($this->_customhelper->getMagentoVersion() >= '2.3.4') {
            $category = $subject->getCurrentCategory();
            $categoryData = $category->getData();
            if (isset($categoryData['megamenu_show_catimage_img'])) {
                unset($categoryData['megamenu_show_catimage_img']);
                $imagename = $category->getData('megamenu_show_catimage_img');
                if (!empty($category->getData('megamenu_show_catimage_img')) &&
                    strpos($category->getData('megamenu_show_catimage_img'), "/tmp") !== false
                ) {
                    $imagename = str_replace("/tmp", "", $category->getData('megamenu_show_catimage_img'));
                }
                $categoryData['megamenu_show_catimage_img'] = str_replace("/pub/media/catalog/tmp/category/", "", $imagename);
                $category->setData($categoryData);
            }
        }

        return $subject;
    }

    /*
    public function aroundGetData(\Magento\Catalog\Model\Category\DataProvider $subject, callable $proceed)
    {
        $category = $subject->getCurrentCategory();
        $categoryData = $category->getData();
        if (isset($categoryData['megamenu_show_catimage_img'])) {
            unset($categoryData['megamenu_show_catimage_img']);
            $categoryData['megamenu_show_catimage_img'] = str_replace("/pub/media/catalog/tmp/category/", "", $category->getData('megamenu_show_catimage_img'));
        }
        $this->loadedData[$category->getId()] = $categoryData;

        $result = $proceed();
        return $result;
    }
    */

    public function getThumbnailUrl($imageName)
    {
        $url = $this->_storeManager->getStore()->getBaseUrl(
            \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
        ) . 'catalog/category/' . $imageName;
        return $url;
    }
}
