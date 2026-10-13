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
    protected $attributes;
    protected $baseUrl;
    protected $storeName;

    public function __construct(
            \Magento\Framework\ObjectManagerInterface $objectManager
    ) {
        $this->objectManager = $objectManager;
        $this->storeName = [
            1 => 'B2C-darlighting-co-uk',
            2 => 'B2B-darlighting-com',
        ];
        $this->attributes = array(
            'showcaselink',
            'measurements',
            'basemeasurements',
            'shademeasurements',
            'fixing_plate_dimensions',
            'product_finish',
            'product_material',
            'cable',
            'nettweight',
            'shadeincluded',
            'shadereference',
            'shadematerial',
            'lamp',
            'bulbinc',
            'recommendedlamp',
            'lumens',
            'efficiency',
            'lamplife',
            'colourtemp',
            'colourrendering',
            'symbols',
            'iprating',
            'suitable',
            'electrical_class',
            'lamp_second',
            'bulbinc_second',
            'recommendedlamp_second',
            'lumens_second',
            'efficiency_second',
            'lamplife_second',
            'colourtemp_second',
            'colourrendering_second',
            'usefultoknow'
        );
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


        if (isset($argv[2]) && $argv[2] == 'createreport' && isset($argv[3])) {
            //php darWebReport.php UAT createreport 1
            $this->baseUrl = $storeManager->getStore($argv[3])->getBaseUrl();
            $this->createReport($argv[3]);
        }
    }

    /**
     *
     * Create csv with report
     *
     * @param    int storeId
     * @return      void
     *
     */
    protected function createReport($storeId) {

        $products = $this->getProductCollection($storeId);
        $logFilename = $this->pconfig->progConfig->localLogDir . '/web-report-store-' . $storeId . '-time-' . date('Y-m-d-h_i_s');
        //var_dump($logFilename); exit;
        generalLib::setGeneralLogFile($logFilename . '.log', false);
        generalLib::setErrorLogFile($logFilename . '-error.log', true, false);
        $this->objectManager->get('Magento\Framework\App\State')->setAreaCode(\Magento\Framework\App\Area::AREA_FRONTEND);
        try {
            $fp = fopen($this->pconfig->progConfig->baseDir . '/html/pub/webservice/report/report_' . $this->storeName[$storeId] . '.csv', 'w');
            $list = $this->prepareForExport($products, $storeId);

            foreach ($list as $fields) {
                if (isset($fields['sku']) && $fields['sku'] != 'sku') {
                    generalLib::mesg('Report for ' . $fields['sku']);
                }
                fputcsv($fp, $fields);
            }
            fclose($fp);
        } catch (Exception $e) {
            generalLib::error($e->getMessage());
        }
        return;
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
                        //->addAttributeToFilter('sku', [/*'LYA0550','YAV0564', 'YON0522', 'KET5446', 'LYA0350',*/'002BLO14070'])
                        ->addStoreFilter($storeId)
                        ->addUrlRewrite()
                        ->addAttributeToFilter('status', \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED);
        //->setPageSize(3);
    }

    /**
     *
     * get product images
     *
     * @param    \Magento\Catalog\Model\ProductFactory $_product,string cover
     * @return      string
     *
     */
    protected function getProductImages($_product, $cover) {
        $imagesArray = [];
        $images = $_product->getMediaGalleryImages();
        if (!empty($images)) {
            foreach ($images as $data) {
                if (basename($cover) != basename($data->getUrl())) {
                    array_push($imagesArray, $data->getUrl());
                }
            }
        }
        return implode(', ', $imagesArray);
    }

    /**
     *
     * check if product is available for sale for certain store
     *
     * @param    \Magento\Catalog\Model\ProductFactory $_product, int storeID, bool saleable
     * @return      string
     *
     */
    protected function checkSaleable($_product, $storeId, $saleable) {
        switch ($storeId) {
            case 1:
                if ($_product->getExtensionAttributes()->getStockItem()->getQty() <= 0) {
                    $saleable = false;
                }
                break;
            case 2:
                if((bool)$_product->getShadeRangeProduct()){
                    $saleable = false;
                }
                break;
            default:
                break;
        }

        return ((bool) $saleable) ? 'yes' : 'no';
    }

    /**
     *
     * get related products
     *
     * @param    \Magento\Catalog\Model\ProductFactory $_product
     * @return     string
     *
     */
    protected function getRelated($_product) {
        $return = array();
        $relatedProducts = $_product->getRelatedProducts();
        if (!empty($relatedProducts)) {
            foreach ($relatedProducts as $relatedProduct) {
                $return[] = $relatedProduct->getSku();
            }
        }
        return implode(', ', $return);
    }

    /**
     *
     * get upSell products
     *
     * @param    \Magento\Catalog\Model\ProductFactory $_product
     * @return     string
     *
     */
    protected function getUpsell($_product) {
        $return = array();
        $upSellProducts = $_product->getUpSellProducts();
        if (!empty($upSellProducts)) {
            foreach ($upSellProducts as $upSellProduct) {
                $return[] = $upSellProduct->getSku();
            }
        }
        return implode(', ', $return);
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
            return 'image does not exist ->  '.$this->baseUrl . 'pub/media/catalog/product' . $_product->getThumbnail();
        }
        return $this->baseUrl . 'pub/media/catalog/product' . $_product->getThumbnail();
    }

    /**
     *
     * get measurement images
     *
     * @param    string sku
     * @return     string
     *
     */
    protected function getMeasurementImages($sku) {
        if (file_exists($this->pconfig->progConfig->baseDir . '/html/pub/media/wysiwyg/pdf-data-sheet/' . str_replace('/', '-', $sku) . '_M.jpg')) {
            return $this->baseUrl . 'pub/media/wysiwyg/pdf-data-sheet/' . str_replace('/', '-', $sku) . '_M.jpg';
        }
        return '';
    }

    /**
     *
     * get attribute value
     *
     * @param    collection/item $product,string attribute
     * @return     string
     *
     */
    protected function getAttributeText($product, $attribute) {
        $data = $product->getAttributeText($attribute);
        if (!$data || (is_array($data) && empty($data))) {
            //var_dump($attribute,$product->getData($attribute));
            return $this->stripText($product->getData($attribute));
        }
        if (is_array($data)) {
            return implode(', ', $data);
        }
        return false;
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
     * prepare array for csv export
     *
     * @param    \Magento\Catalog\Model\ProductFactory $products, int $storeID
     * @return      array
     *
     */
    protected function prepareForExport($products, $storeId) {
        $return = array();
        $kkey = 0;

        foreach ($products as $product) {
            $_product = $this->getProductObject($product->getId());
            $return[$kkey]['id'] = $product->getId();
            $return[$kkey]['sku'] = $product->getSku();
            $return[$kkey]['url'] = preg_replace('/\\?.*/', '',$product->setStoreId($storeId)->getProductUrl());
            $return[$kkey]['title'] = $product->getName();
            $return[$kkey]['description'] = $this->stripText($product->getDescription());
            //var_dump($return[$kkey]['description']); exit;
            $return[$kkey]['rrp'] = number_format((float) $product->getRrp() * 1.2, 2);
            $return[$kkey]['rrp_vat'] = number_format((float) $product->getRrpVat(), 2);
            if ($storeId != 1) {
                $return[$kkey]['price'] = number_format((float) $product->getPrice(), 2).' - price excluding customer discount';
            }
            $return[$kkey]['qty'] = $_product->getExtensionAttributes()->getStockItem()->getQty();
            $return[$kkey]['saleable'] = $this->checkSaleable($_product, $storeId, $product->isSaleable());

            $return[$kkey]['cover'] = $this->getCover($_product);
            $return[$kkey]['images'] = $this->getProductImages($_product, $return[$kkey]['cover']);
            $return[$kkey]['measurement_images'] = $this->getMeasurementImages($product->getSku());
            foreach ($this->attributes as $attribute) {
                $data = $this->getAttributeText($product, $attribute);
                //var_dump($data);
                if ($data) {
                    $return[$kkey][$attribute] = $data;
                } else {
                    $return[$kkey][$attribute] = '';
                }
            }
            $return[$kkey]['recommended_products'] = $this->getRelated($_product);
            $return[$kkey]['you_may_also_need'] = $this->getUpsell($_product);
            $kkey++;
        }
        
        foreach ($return[0] as $key => $item) {
            $return[$kkey][$key] = $key;
        }
        return array_reverse($return);
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
