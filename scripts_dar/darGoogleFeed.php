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
    protected $baseUrl;
    protected $storeName;
    protected $placeholder;
    protected $attributes;
    protected $brandMap;

    public function __construct(
            \Magento\Framework\ObjectManagerInterface $objectManager
    ) {
        $this->objectManager = $objectManager;
        $this->placeholder = 'https://darlighting.co.uk/pub/media/catalog/product/placeholder/default/dar-place-holder_6_1.jpg';
        $this->attributes = [
            'measurements' => 'General',
            'basemeasurements'=>'General',
            'shademeasurements'=>'General',
            'product_material'=>'General',
            'product_finish'=>'General',
            'nettweight'=>'General',
            'shadeincluded'=>'General',
            'shadematerial'=>'General',
            'lamp'=>'General',
            'bulbinc'=>'General',
            'lumens'=>'General',
            'efficiency'=>'General',
            'lamplife'=>'General',
            'colourtemp'=>'General',
            'colourrendering'=>'General',
            'lamp_second'=>'General',
            'bulbinc_second'=>'General',
            'lamp_second'=>'General',
            'numlamps'=>'General',
            'class'=>'General', 
        ];
        $this->brandMap=[
            'DAR'=>'dar Lighting',
            'MAJORS' =>'Laura Ashley',
            'DHL' => 'David Hunt Lighting',
            'WISEBUYS' => 'dar Lighting',
            'LIGHT SHADE STUDIO' => 'The Light Shade Studio',
            'CONTRACT'=>'dar Lighting'
            
        ];
    }

    public function launch() {
        $this->run();
        return $this->response;
    }

    public function run() {
        global $argv;
        $this->pconfig = new progConfig();


        $storeManager = $this->objectManager->get('\Magento\Store\Model\StoreManagerInterface');

        $storeManager->setCurrentStore(\Magento\Store\Model\Store::ADMIN_CODE);

        var_dump($storeManager->getStore()->getBaseUrl());
        if (isset($argv[2]) && $argv[2] == 'updatefeed') {
            //php darGoogleFeed.php UAT updatefeed
            $this->baseUrl = $storeManager->getStore(isset($argv[3]) ? $argv[3] : 1)->getBaseUrl();
            $this->updateFeed(isset($argv[3]) ? $argv[3] : 1);
        }
    }

    protected function updateFeed($storeId) {

        $products = $this->getProductCollection($storeId);
        $logFilename = $this->pconfig->progConfig->localLogDir . '/_googlefeed-' . $storeId . '-time-' . date('Y-m-d-h_i_s');

        generalLib::setGeneralLogFile($logFilename . '.log', false);
        generalLib::setErrorLogFile($logFilename . '-error.log', true, false);
        $this->objectManager->get('Magento\Framework\App\State')->setAreaCode(\Magento\Framework\App\Area::AREA_FRONTEND);
        try {
            $list = $this->prepareForExport($products, $storeId);
            $xml = new SimpleXMLElement('<rss xmlns:g="http://base.google.com/ns/1.0" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" version="2.0" xsi:noNamespaceSchemaLocation="http://manufacturers.google.com/files/Manufacturer-Center-Product-Feed.xsd" />');

            $this->to_xml($xml, $list);
            $fp = fopen($this->pconfig->progConfig->baseDir . '/html/pub/googlefeed/googlefeed_' . $storeId . '.xml', 'w');
            fwrite($fp, $xml->asXML());
            fclose($fp);
        } catch (Exception $e) {
            generalLib::error($e->getMessage());
        }
        return;
    }

    protected function to_xml(SimpleXMLElement $object, array $data) {
        foreach ($data as $key => $value) {
            if (is_numeric($key)) {
                $key = 'item';
            }
            if (is_array($value)) {
                if (strpos($key, 'additional_image_link') !== false) {
                    foreach ($value as $vitem){
                        $new_object = $object->addChild('additional_image_link', htmlspecialchars($vitem), 'http://base.google.com/ns/1.0');
                    }
                    continue;
                }elseif (strpos($key, 'product_detail') !== false) {
                    $key = 'product_detail';
                    $new_object = $object->addChild($key, false, 'http://base.google.com/ns/1.0');
                } else {
                    $new_object = $object->addChild($key);
                }

                $this->to_xml($new_object, $value);
            } else {
                $xmlNamespace='';
                if (strpos($key, 'g_') === 0) {
                    $xmlNamespace='http://base.google.com/ns/1.0';
                    $key= substr($key, 2);
                }
                $object->addChild($key, htmlspecialchars($value), $xmlNamespace);
            }
        }
    }

    /**
     *
     * get product collection
     *
     * @param    int storeId
     * @return      \Magento\Catalog\Model\Product
     *
     */
    protected function getProductCollection($storeId) {
        return $this->objectManager->create('Magento\Catalog\Model\Product')
                        ->getCollection()
                        ->addAttributeToSelect('*')
                        //->addAttributeToFilter('sku', ['LA3756065-Q','ABA5250','ACA0322', 'ABA4750', 'ABA5050','CRI0429','LYA0550', 'YAV0564', 'YON0522', 'KET5446', 'LYA0350', 'YAV0564'])
                        ->addStoreFilter($storeId)
                        ->addUrlRewrite()
                        ->addAttributeToFilter('status', \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED);
        //->setPageSize(3);
    }

    /**
     *
     * prepare array for csv export
     *
     * @param    \Magento\Catalog\Model\ProductFactory $products, int $storeID
     * @return      array
     *
     */
    protected function prepareForExport($products, $storeId) {
        $return = array();
        $kkey = 0;
        $return['channel']['title'] = 'där lighting';
        $return['channel']['link'] = 'https://www.darlighting.co.uk/';
        $return['channel']['description'] = 'Delivering you the next lighting trends';
        //$brandsa=[];
        foreach ($products as $product) {
            if(!$product->getData('gtin')){
                continue;
            }
          
            $_product = $this->getProductObject($product->getId());
            /* if(in_array($_product->getData('product_brand'), $brandsa)){
                continue;
            }
            $brandsa[]=$_product->getData('product_brand');*/
            $return['channel'][$kkey]['g_id'] = $product->getSku();
            $return['channel'][$kkey]['g_brand'] = isset($this->brandMap[$_product->getData('product_brand')]) ? $this->brandMap[$_product->getData('product_brand')] : $_product->getData('product_brand');
            $return['channel'][$kkey]['g_title'] = $product->getName();
            $return['channel'][$kkey]['g_gtin'] = $product->getData('gtin');
            $return['channel'][$kkey]['g_suggested_retail_price'] = 'GBP ' . number_format((float) $product->getRrp() * 1.2, 2);

            $colour=$this->getAttributeText($product, 'color');
            if($colour){
                $return['channel'][$kkey]['g_color'] = $this->getAttributeText($product, 'color');
            }
            $return['channel'][$kkey]['g_material'] = $this->getAttributeText($product, 'product_material');

            $attKey = 0;
            foreach ($this->attributes as $attribute => $sectionName) {
                $data = $this->getAttributeText($product, $attribute);
                $attributeObj = $_product->getResource()->getAttribute($attribute);
                $attributeLabel = $attributeObj->getFrontend()->getLabel($_product);
                if ($data) {
                    $return['channel'][$kkey]['product_detail_' . $attKey]['g_section_name'] = $sectionName;
                    $return['channel'][$kkey]['product_detail_' . $attKey]['g_attribute_name'] = $attributeLabel;
                    $return['channel'][$kkey]['product_detail_' . $attKey]['g_attribute_value'] = $data;
                    $attKey++;
                }
            }
            $return['channel'][$kkey]['g_description'] = strip_tags($product->getDescription());
            $return['channel'][$kkey]['g_image_link'] = $this->getCover($_product);
            $return['channel'][$kkey]['g_additional_image_link'] = $this->getImages($_product,$return['channel'][$kkey]['g_image_link']);
            $return['channel'][$kkey]['g_product_page_url'] = preg_replace('/\\?.*/', '', $product->setStoreId($storeId)->getProductUrl());
          

            $kkey++;
        }
        return $return;
    }

    protected function isJson($string) {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }

    public function catchException(\Magento\Framework\App\Bootstrap $bootstrap, \Exception $exception) {
        return $exception;
    }

    /**
     *
     * get product object
     *
     * @param    int productId
     * @return      \Magento\Catalog\Model\ProductFactory
     *
     */
    protected function getProductObject($productId) {
        $productModel = $this->objectManager->get('\Magento\Catalog\Model\ProductFactory');
        return $productModel->create()->load($productId);
    }

    /**
     *
     * get attribute value
     *
     * @param    string text
     * @return     string
     *
     */
    protected function stripText($text) {
        $from = ["'", '"'];
        $to = ['', ''];
        return preg_replace('/\s+/', ' ', trim($text));
    }

    /**
     *
     * get product cover
     *
     * @param    \Magento\Catalog\Model\ProductFactory $_product
     * @return     string
     *
     */
    protected function getCover($_product) {
        if (!file_exists($this->pconfig->progConfig->baseDir . '/html/pub/media/catalog/product' . $_product->getThumbnail())) {
            return $this->placeholder;
        }
        return $this->baseUrl . 'pub/media/catalog/product' . $_product->getThumbnail();
    }
    
     /**
     *
     * get product images
     *
     * @param    \Magento\Catalog\Model\ProductFactory $_product
     * @return     string
     *
     */
    protected function getImages($_product,$cover) {
        $productimages = $_product->getMediaGalleryImages();
        $images=array();
        foreach ($productimages as $productimage) {
            if(basename($cover) !=basename($productimage['url'])){
                $images[]=$productimage['url'];
            }
        }
        return $images;
    }

    /**
     *
     * get attribute value
     *
     * @param    collection/item $product,string attribute
     * @return     string
     *
     */
    protected function getAttributeText($product, $attribute, $keepArray = false) {
        $data = $product->getAttributeText($attribute);
        if (!$data || (is_array($data) && empty($data))) {
            $data = $this->stripText($product->getData($attribute));
        }
        if (is_array($data)) {
            ksort($data);
            if ($keepArray) {
                return $data;
            }
            return implode('/', $data);
        }
        if ($this->stringIsFloat($data)) {
            $data = number_format((float) $data, 0);
        }
        if ($keepArray && !is_array($data)) {
            $data = [0 => $data];
        }
        return $data;
    }

    /**
     *
     * check if string is float value
     *
     * @param    string
     * @return     bool
     *
     */
    protected function stringIsFloat($string) {
        $float_value = (float) $string;
        if (strval($float_value) == $string) {
            return true;
        }
        return false;
    }

}

$app = $bootstrap->createApplication('CustomApp');

$bootstrap->run($app);
?>
