<?php
/**
 * Mega Menu Index Controller.
 *
 * @category  Site Search & Navigation
 * @package   Rootways_Megamenu
 * @author    Developer RootwaysInc <developer@rootways.com>
 * @copyright 2023 Rootways Inc. (https://www.rootways.com)
 * @license   Rootways Custom License
 * @link      https://www.rootways.com/media/extension_doc/license_agreement.pdf
 */
namespace Rootways\Megamenu\Controller\Adminhtml\Category\Save;

class Plugin
{
    //add thumnail field to $data for saving
    public function afterImagePreprocessing(\Magento\Catalog\Controller\Adminhtml\Category\Save $subject, $data)
    {
        /*
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $productMetadata = $objectManager->get('Magento\Framework\App\ProductMetadataInterface');
        $version = $productMetadata->getVersion();
        if ($version >= '2.3.4') {
            if (isset($data['megamenu_show_catimage_img']) && is_array($data['megamenu_show_catimage_img'])) {
                if (!empty($data['megamenu_show_catimage_img']['delete'])) {
                    $data['megamenu_show_catimage_img'] = null;
                } else {
                    if (isset($data['megamenu_show_catimage_img'][0]['name']) &&
                        isset($data['megamenu_show_catimage_img'][0]['tmp_name'])) {
                        $data['megamenu_show_catimage_img'] = $data['megamenu_show_catimage_img'][0]['name'];
                    } else {
                        unset($data['megamenu_show_catimage_img']);
                    }
                }
            } else {
                $data['megamenu_show_catimage_img'] = null;
            }
        }
        */
        return $data;
    }
}
