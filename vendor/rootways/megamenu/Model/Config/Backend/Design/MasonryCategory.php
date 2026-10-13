<?php
/**
 * Mega Menu Contactus Model.
 *
 * @category  Site Search & Navigation
 * @package   Rootways_Megamenu
 * @author    Developer RootwaysInc <developer@rootways.com>
 * @copyright 2023 Rootways Inc. (https://www.rootways.com)
 * @license   Rootways Custom License
 * @link      https://www.rootways.com/pub/media/extension_doc/license_agreement.pdf
 */
namespace Rootways\Megamenu\Model\Config\Backend\Design;

class MasonryCategory implements \Magento\Framework\Option\ArrayInterface
{
    protected $_categoryHelper;

    public function __construct(\Magento\Catalog\Helper\Category $catalogCategory)
    {
        $this->_categoryHelper = $catalogCategory;
    }

    /*
     * Return categories helper
     */

    public function getStoreCategories($sorted = false, $asCollection = false, $toLoad = true)
    {
        return $this->_categoryHelper->getStoreCategories($sorted, $asCollection, $toLoad);
    }

    /*
     * Option getter
     * @return array
     */
    public function toOptionArray()
    {


        $arr = $this->toArray();
        $ret = [];

        foreach ($arr as $key => $value) {

            $ret[] = [
                'value' => $key,
                'label' => $value
            ];
        }

        return $ret;
    }

    /*
     * Get options in "key-value" format
     * @return array
     */
    public function toArray()
    {

        $categories = $this->getStoreCategories(true, false, true);

        $catagoryList = [];
        foreach ($categories as $category) {

            $catagoryList[$category->getEntityId()] = __($category->getName());
        }

        return $catagoryList;
    }
}
