<?php

/*
 * Name:			darImg.php
 * Company: 		Star Digital
 * Author:			John Little
 * Function:		DAR Image Processor
 */

ini_set('memory_limit', '2048M');

$progConfig = new stdClass();

//NEED TO HAVE AN ARGUMENT WHICH WILL ALLOW matchingFilenames TO BE SPECIFIED FOR ANY FILE TYPE SPECIFIED IN setDownloadDets()

header('Content-type: text/html; charset=utf-8');

require_once dirname(__FILE__) . '/../html/app/bootstrap.php';

$bootstrap = \Magento\Framework\App\Bootstrap::create(BP, $_SERVER);

use \Magento\Framework\AppInterface as AppInterface;

$progConfig = new stdClass();

$progConfig->configType = 'dar';

require_once($progConfig->configType . 'NavConfig.php');

$progConfigObj = new progConfig();

$progConfig = $progConfigObj->progConfig;

require_once($progConfig->scriptDir . '/class_darImgProcessor.php');
require_once($progConfig->scriptLibDir . '/class_downloadFileLib.php');
require_once($progConfig->scriptLibDir . '/class_magentoImgImport.php');
require_once($progConfig->scriptLibDir . '/class_magentoImport.php');
require_once($progConfig->scriptLibDir . '/class_magentoGeneral.php');
require_once($progConfig->scriptLibDir . '/class_generalFuncs.php');

class CustomApp implements AppInterface {

    protected $pconfig;
    protected $objectManager;
    protected $filename;
    protected $storeID;
    protected $coverImage;

    public function __construct(
            \Magento\Framework\ObjectManagerInterface $objectManager
    ) {
        $this->objectManager = $objectManager;
    }

    public function launch() {
        $this->run();
        return $this->response;
    }

    public function run() {
        global $argv;

        $this->pconfig = new progConfig();
        //var_dump($this->pconfig);
        $this->filename = '/sitemap/image_sitemap.xml';
        $this->storeID = 1;
        $this->coverImage = 'product_page_image_small';
        $storeManager = $this->objectManager->get('\Magento\Store\Model\StoreManagerInterface');
        $storeManager->setCurrentStore($this->storeID);


        if (isset($argv[2]) && $argv[2] == 'imagesitemap') {
            //php darImageSitemap.php UAT imagesitemap
            $this->generateImageSitemap();
        }
    }

    /*
     * function generateImageSitemap
     *      
     */

    protected function generateImageSitemap() {
        $productFactory = $this->objectManager->get('\Magento\Catalog\Model\ResourceModel\Product\CollectionFactory');
        $productCollection = $productFactory->create();
        $productCollection->addAttributeToSelect('*');
        //$productCollection->addAttributeToFilter('is_facebook_product', 1);
        $productCollection->addAttributeToFilter('type_id', \Magento\Catalog\Model\Product\Type::TYPE_SIMPLE);
        $productCollection->addAttributeToFilter('status', \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED);
        //$productCollection->addAttributeToFilter('sku', '002MEH80');
        $productCollection->addWebsiteFilter(1);
        //$productCollection->getSelect()->limit(100);

        $logFilename = $this->pconfig->progConfig->localLogDir . '/image-sitemap-update-' . date('Y-m-d-h_i_s');
        generalLib::setGeneralLogFile($logFilename . '.log', false);
        generalLib::setErrorLogFile($logFilename . '-error.log', true, false);
        $this->objectManager->get('Magento\Framework\App\State')->setAreaCode(\Magento\Framework\App\Area::AREA_FRONTEND);
        //var_dump('dasdas');
        $resultPage=$this->objectManager->get('Magento\Framework\View\Result\LayoutFactory')->create();
        $blockInstance = $resultPage->getLayout()->createBlock('\MagicToolbox\MagicZoomPlus\Block\Product\View\Gallery');
//var_dump('dasdaaaas');
        try {
            file_put_contents($this->pconfig->progConfig->siteDir . $this->filename, $this->xmlHead());
            $imageHelper = $this->objectManager->get('\Magento\Catalog\Helper\Image');
            foreach ($productCollection as $product) {
                $_product = $this->objectManager->create('Magento\Catalog\Model\Product')->load($product->getId());
                //$images = $_product->getMediaGalleryImages();
                //var_dump('dasdas');
               $images= $blockInstance->getGalleryImagesCollection($_product);
              //var_dump($images); exit;
                $xml = '';
                if ($images) {

                    if ($this->imageCount($images, $_product, $imageHelper) == 0) {
                        continue;
                    }
                    $xml .= '<url>';
                    $xml .= $this->xmlProductBody($_product->getProductUrl());

                    foreach ($images as $image) {

                        //$largeImage = $imageHelper->init($_product, 'product_page_image_large')->setImageFile($image->getFile())->getUrl();
                        /*$largeImage = $imageHelper->init($product, 'product_page_image_large', ['width' => null, 'height' => null])
                                ->setImageFile($image->getFile())
                                ->keepAspectRatio(true)
                                ->keepFrame(false)
                                ->resize(1200, 1200)
                                ->getUrl();*/
                        $largeImage=$image->getData('medium_image_url');

                        //var_dump($largeImage); exit;

                        if (strpos($largeImage, 'placeholder') !== false) {
                            continue;
                        }
                        if ($largeImage) {
                            $xml .= $this->xmlImageBody($largeImage);
                        } /* else {
                          $xml .= $this->xmlImageBody($image->getUrl());
                          } */
                    }
                    $xml .= '</url>';
                    file_put_contents($this->pconfig->progConfig->siteDir . $this->filename, $xml, FILE_APPEND);
                    generalLib::mesg('There are ' . $images->count() . ' images to update for -> ' . $product->getSku());
                }
            }
            file_put_contents($this->pconfig->progConfig->siteDir . $this->filename, $this->xmlFooter(), FILE_APPEND);
        } catch (Exception $e) {
            generalLib::error($e->getMessage());
        }
    }

    protected function imageCount($images, $_product, $imageHelper) {
        $count = 0;
        foreach ($images as $image) {

            $largeImage = $imageHelper->init($_product, 'category_page_grid')->setImageFile($image->getFile())->getUrl();

            if (strpos($largeImage, 'placeholder') !== false) {
                continue;
            }
            if (!$largeImage) {
                continue;
            }
            ++$count;
        }
        return $count;
    }

    protected function xmlImageBody($imageUrl) {
        return '
                <image:image>
                <image:loc>' . $imageUrl . '</image:loc>
                </image:image>
                ';
    }

    protected function xmlProductBody($productUrl, $update = 'daily', $priority = '0.9') {
        return '
                <loc>' . $productUrl . '</loc>
                <lastmod>' . date('Y-m-d') . '</lastmod>
                <changefreq>' . $update . '</changefreq>
                <priority>' . $priority . '</priority>
                ';
    }

    protected function xmlHead() {
        return '<?xml version="1.0" encoding="UTF-8"?>
                <urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">
                ';
    }

    protected function xmlFooter() {
        return '
                </urlset>';
    }

    protected function isJson($string) {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }

    public function catchException(\Magento\Framework\App\Bootstrap $bootstrap, \Exception $exception) {
        return $exception;
    }

}

$app = $bootstrap->createApplication('CustomApp');

$bootstrap->run($app);
?>
