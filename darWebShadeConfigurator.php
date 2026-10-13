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

$baseDir = dirname(__FILE__, 2);
require_once $baseDir . '/app/bootstrap.php';

$bootstrap = \Magento\Framework\App\Bootstrap::create(BP, $_SERVER);

use \Magento\Framework\AppInterface as AppInterface;
use Magento\Framework\UrlInterface;

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
    protected $delimiter;
    protected $mediaRoot;
    protected $qtyDelimiter;
    protected $productShadeArray;
    protected $productShadeQtyArray;
    protected $baseOptionsForShadeArray;
    protected $associateConfigurationShadeToBase;
    protected $shadeOptionsForBaseArray;
    protected $links;
    protected $shadeOptionsForBaseQtyArray;
    protected $stores;

    public function __construct(
            \Magento\Framework\ObjectManagerInterface $objectManager
    ) {
        $this->objectManager = $objectManager;
        $this->delimiter = '|';
        $this->qtyDelimiter = '*';
        $this->baseOptionsForShadeArray = [];
        $this->productShadeArray = [];
        $this->productShadeQtyArray = [];
        $this->links = [];
        $this->associateConfigurationShadeToBase = [];
        $this->shadeOptionsForBaseArray = [];
        $this->shadeOptionsForBaseQtyArray = [];
        $this->stores = [1, 2];
        $this->mediaRoot = $this->setMediaRoot();
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


        if (isset($argv[2]) && $argv[2] == 'generatecombinations') {
            //php darWebShadeConfigurator.php UAT generatecombinations
            $this->createProcess();
        }
    }

    protected function isConfigrator($product) {
        //var_dump($product->getData('combination_line_two'),strpos($product->getData('combination_line_two'), '|'));
        if (strpos($product->getData('combination_line_two'), '|') === false) {
            return false;
        }
        return true;
    }

    protected function getSkuAndQty($skuAndQty) {
        if (strpos($skuAndQty, '*') !== false) {
            $skuAndQty = explode('*', $skuAndQty);
            return ['sku' => $skuAndQty[0], 'qty' => $skuAndQty[1]];
        }
        return ['sku' => $skuAndQty, 'qty' => 1];
    }

    protected function setMediaRoot() {
        $fileSystem = $this->objectManager->create('\Magento\Framework\Filesystem');
        $mediaPath = $fileSystem->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA)->getAbsolutePath();
        return $mediaPath;
    }

    protected function getMediaShadePath()
    {
        $fileSystem = $this->objectManager->create('\Magento\Framework\UrlInterface');
        $mediaPath = $fileSystem->getBaseUrl(['_type' => UrlInterface::URL_TYPE_MEDIA]) . 'shade_configurator';
        return $mediaPath;
    }

    protected function findPattern($shadeSku) {
        $returnBaseSkuCodes = [];
        //var_dump('shadeskyu->' . $shadeSku);

        $shadeSku = $this->getSkuAndQty($shadeSku)['sku'];

        foreach ($this->productShadeArray as $baseSku => $item) {
            if (strpos($item, $shadeSku . '|') !== false || strpos($item, '|' . $shadeSku) !== false || strpos($item, $shadeSku . '*') !== false) {

                $returnBaseSkuCodes[] = $baseSku;
            }
        }
        return $returnBaseSkuCodes;
    }

    /*

     * Function to search if a base has this certain Shade if yes it add to array
     *
     *
     *      */

    protected function prepareBaseForShade($products) {
        foreach ($products as $product) {
            if (!$this->isConfigrator($product)) {

                continue;
            }
            $productShadeArray = array();
            $productShadeQtyArray = array();
            $tempArray = explode('|', $product->getData('combination_line_two'));
            foreach ($tempArray as $singleItem) {
                $productShadeArray[] = $this->getSkuAndQty($singleItem)['sku'];
                $this->shadeOptionsForBaseQtyArray[$product->getSku()][$this->getSkuAndQty($singleItem)['sku']] = $this->getSkuAndQty($singleItem)['qty'];
            }

            $this->productShadeArray[$product->getSku()] = implode('|', $productShadeArray);
        }

        foreach ($products as $product) {
            if (!$this->isConfigrator($product)) {
                continue;
            }
            $shades = explode($this->delimiter, $product->getData('combination_line_two'));
            foreach ($shades as $itemShadeSku) {
                if (!isset($this->baseOptionsForShadeArray[$product->getSku()])) {
                    $this->baseOptionsForShadeArray[$product->getSku()][] = $product->getSku();
                }

                //$this->baseOptionsForShadeArray[$product->getSku()] += $this->findPattern($itemShadeSku);

                $this->baseOptionsForShadeArray[$product->getSku()] = array_unique(array_merge($this->baseOptionsForShadeArray[$product->getSku()], $this->findPattern($itemShadeSku)));
            }
        }


//        print_r($this->productShadeArray);
//        print_r($this->baseOptionsForShadeArray);
//        exit;
        return;
    }

    /*

     * Function to get all available shades for the Base
     *
     *
     *      */

    protected function prepareShadeForBase($products) {
        foreach ($this->baseOptionsForShadeArray as $baseSku => $items) {
            foreach ($items as $allBases) {
                //var_dump('abasses-'.$allBases);
                if (!isset($this->shadeOptionsForBaseArray[$baseSku])) {
                    $this->shadeOptionsForBaseArray[$baseSku] = explode(
                        $this->delimiter, $this->productShadeArray[$allBases]
                    );
                } else {
                    /* if ($baseSku == 'AIT4238') {
                      var_dump($baseSku);
                      var_dump($allBases);
                      var_dump(explode($this->delimiter, $this->productShadeArray[$allBases]));
                      } */
                    $this->shadeOptionsForBaseArray[$baseSku] = array_unique(
                        array_merge(
                            $this->shadeOptionsForBaseArray[$baseSku],
                            explode($this->delimiter, $this->productShadeArray[$allBases])
                        )
                    );

                    sort($this->shadeOptionsForBaseArray[$baseSku]);
//$this->shadeOptionsForBaseArray[$baseSku][] = explode($this->delimiter, $this->productShadeArray[$allBases]);
                }
            }
        }

//        print_r($this->shadeOptionsForBaseArray);
//
//        exit;
        return;
    }

    /*

     * function to check if the base and shade can be stored as combination
     *
     *      */

    protected function checkCanBeCombination($baseSku, $shadeSku) {
        if (file_exists($this->mediaRoot . 'shade_configurator/' . $baseSku . ',' . $shadeSku . '.jpg')) {
            return true;
        }
        return false;
    }

    /*

     * function to prepare array with Shades to associate with base configuration
     *
     *      */

    protected function prepareShadeAssociations() {

        foreach ($this->shadeOptionsForBaseArray as $base => $shades) {
            foreach ($shades as $shadeitem) {
                if ($this->checkCanBeCombination($base, $shadeitem)) {
                    $this->associateConfigurationShadeToBase[$shadeitem] = $base;
                }
            }
        }

//        var_dump('ds');
//        var_dump($this->associateConfigurationShadeToBase);
//        exit;
    }

    /*
     * function to generateCombinations
     *
     */

    protected function createProcess() {

        $logFilename = $this->pconfig->progConfig->localLogDir . '/shadeConfiguratorGenerator-' . date('Y_m_d_h_i_s');

        generalLib::setGeneralLogFile($logFilename . '-general.log', false);
        generalLib::setErrorLogFile($logFilename . '-error.log', true, false);

        $products = $this->objectManager->create('Magento\Catalog\Model\Product')
                ->getCollection()
                ->addAttributeToSelect('*')
                //->addAttributeToFilter('sku', ['eq' => 'AIT4238'])
                //->addAttributeToFilter('sku', ['eq' => 'SPB3640'])
                ->addAttributeToFilter('combination_line_two', ['neq' => 'NULL']);

        $this->prepareBaseForShade($products);

        $this->prepareShadeForBase($products);

        $this->prepareShadeAssociations();
        // var_dump($this->associateConfigurationShadeToBase);
//        var_dump($this->associateConfigurationShadeToBase['ACC864']);
//        exit;



        foreach ($products as $product) {
            if (!$this->isConfigrator($product)) {
                continue;
            }
            generalLib::mesg('Shade Configurator for product ' . $product->getSku());

            $combinations = $this->generateCombinations($product);

            if (empty($combinations)) {
                generalLib::mesg('Skipping. There are no combinations ');
                generalLib::mesg('----------------------------');
                $model = $this->objectManager->create('Rakdesign\Configurator\Model\Configurator')->load($product->getId(), 'product_id');
                if ($model->getId()) {
                    try {
                        $model->delete();
                        generalLib::mesg('Removing configuration------' . $model->getId());
                        generalLib::mesg('----------------------------');
                    } catch (\Exception $e) {
                        generalLib::error('Product Remove Configuration error -> ' . $product->getSku());
                        generalLib::error($e->getMessage());
                        generalLib::error('----------------------------');
                    }
                }
                continue;
            }

            $model = $this->objectManager->create('Rakdesign\Configurator\Model\Configurator')->load($product->getId(), 'product_id');

            if (!$model->getId()) {
                $model = $this->objectManager->create('Rakdesign\Configurator\Model\Configurator');
            }

            $model->setName('name');
            $model->setProductId($product->getId());
            $model->setCongifuration(json_encode($combinations));
            $model->setCreated(date('Y-m-d H:i:s'));
            $model->setModified(date('Y-m-d H:i:s'));
            try {
                $model->save();
                $this->links[] = '<a href="' . preg_replace('/\\?.*/', '', $product->setStoreId(1)->getProductUrl()) . '" target="_blank">Base->' . $product->getSku() . '</a>';
                generalLib::mesg('✓ Shade Configuration saved for -> ' . $product->getSku() . ' configuration id=>' . $model->getId() . '');
                generalLib::mesg('----------------------------');
            } catch (\Exception $e) {
                generalLib::error('Product config save error -> ' . $product->getSku());
                generalLib::error($e->getMessage());
                generalLib::error('----------------------------');
            }
        }




        // associate Shades to Base

        $actions = $this->objectManager->create('\Magento\Catalog\Model\ResourceModel\Product\Action');

        $skuCollection = array();

        foreach ($this->associateConfigurationShadeToBase as $shade => $base) {
            $skuCollection[] = $shade;
        }
        if (!empty($skuCollection)) {
            $shadeProducts = $this->objectManager->create('Magento\Catalog\Model\Product')
                    ->getCollection()
                    ->addAttributeToSelect('*')
                    ->addAttributeToFilter('sku', $skuCollection);

            foreach ($shadeProducts as $product) {
                if (isset($this->associateConfigurationShadeToBase[$product->getSku()])) {
                    try {
                        generalLib::mesg('Associate ShadeProduct -> ' . $product->getSku());
                        $actions->updateAttributes([$product->getId()], ['combination_line_one' => $this->associateConfigurationShadeToBase[$product->getSku()]], 0);
                        $this->links[] = '<a href="' . preg_replace('/\\?.*/', '', $product->setStoreId(1)->getProductUrl()) . '" target="_blank">Shade->' . $product->getSku() . '</a>';
                    } catch (Exception $e) {
                        generalLib::error($e->getMessage());
                        generalLib::error('Associate ShadeProduct -> ' . $product->getSku());
                    }
                }
            }
        }


        generalLib::mesg(implode('<br/>', $this->links));
        $cacheFlush=$this->flushCache();
        generalLib::mesg('Cache Info -> ' . $cacheFlush);
    }

    protected function generateCombinations($product) {
        $attributesArray = array();
        $hasRootImage = false; // to check if the combination has a Root image and the product can be displayed as a shade configurator page


        $attributesArray['combination_line_one'] = $this->prepareDataArray($this->baseOptionsForShadeArray[$product->getSku()], $product->getSku());
        $attributesArray['combination_line_two'] = $this->prepareDataArray($this->shadeOptionsForBaseArray[$product->getSku()], $product->getSku());

        $combinations = $this->combinations($attributesArray);
        $mediaShadePath = $this->getMediaShadePath();
        foreach ($combinations as $key => $item) {
            if (file_exists($this->mediaRoot . 'shade_configurator/' . $item['combination_line_one']['name'] . ',' . $item['combination_line_two']['name'] . '.jpg')) {
                $combinations[$key]['image'] = '/media/shade_configurator/' . $item['combination_line_one']['name'] . ',' . $item['combination_line_two']['name'] . '.jpg';
                if ($product->getSku() == $item['combination_line_one']['name']) {
                    $hasRootImage = true;
                }
            } else {
                generalLib::mesg('skip noimage ' . $item['combination_line_one']['name'] . ',' . $item['combination_line_two']['name'] . '.jpg');
                unset($combinations[$key]);
                continue;
            }

            if ($item['combination_line_one']['name']=='RIM4246' and $item['combination_line_two']['name']=='CAN1029') {
                unset($combinations[$key]);
                continue;
            }

            $baseSku=$item['combination_line_one']['name'];
            $shadeSku=$item['combination_line_two']['name'];
            if (isset($this->shadeOptionsForBaseQtyArray[$baseSku][$shadeSku])) {
                        $combinations[$key]['combination_line_two']['qty'] = (float) $this->shadeOptionsForBaseQtyArray[$baseSku][$shadeSku];
                        $combinations[$key]['combination_line_two']['rrp'] = (float) $combinations[$key]['combination_line_two']['qty'] * (float)$item['combination_line_two']['rrp'];
            }
        }

        if (!$hasRootImage) {
            generalLib::mesg('skip noimage no root image for ' . $product->getSku().'=>'.$product->getSku().',{shadeSku}.jpg');
            return [];
        }

        // if ($product->getSku() == 'SPB0140') {
        //  return [];
        //var_dump($attributesArray['combination_line_one']['ORR4233']); exit;
        //}
        return $combinations;
    }

    protected function prepareDataArray($dataArray, $productSku) {
        $returnArray = array();
        $stockState = $this->objectManager->get('\Magento\CatalogInventory\Api\StockStateInterface');
        foreach ($dataArray as $item) {

            if ($item == '') {
                continue;
            }
            $product = $this->objectManager->get('Magento\Catalog\Model\ProductFactory')->create()->loadByAttribute('sku', $item);


            if (!$product) {
                generalLib::mesg('skip generating combination for ' . $item . '. Shade does not exist');

                continue;
            }
            $qty = 1;
            /*if (isset($this->shadeOptionsForBaseQtyArray[$productSku][$item])) {
                $qty = (float) $this->shadeOptionsForBaseQtyArray[$productSku][$item];
            }*/
            $returnArray[$item]['id'] = $product->getId();
            $returnArray[$item]['name'] = $item;
            $returnArray[$item]['title'] = $product->getName();
            //$returnArray[$item]['cover'] = $product->getData('image'); //get base image
            $returnArray[$item]['cover'] = $product->getData('thumbnail'); //get thumbnail image
            $returnArray[$item]['price'] = $product->getPrice();
            $returnArray[$item]['rrp'] = (float) $product->getRrp() * $qty * 1.2;
            $returnArray[$item]['dimwidth'] = $product->getData('dimwidth');
            $returnArray[$item]['dimheight'] = $product->getData('dimheight');
            $returnArray[$item]['dimdepth'] = $product->getData('dimdepth');

            /* for filters */
            $returnArray[$item]['height'] = $product->getData('height');
            $returnArray[$item]['width'] = $product->getData('width');
            $returnArray[$item]['diameter'] = $product->getData('diameter');
            $returnArray[$item]['product_material'] = '';//$product->getAttributeText('product_material');
            $returnArray[$item]['features'] = ''; //$product->getAttributeText('features');
            $returnArray[$item]['finish'] = ''; //$product->getAttributeText('appearance');
            /* end for filters */

            $returnArray[$item]['buying_restriction_code'] = $product->getData('buying_restriction_code');
            $returnArray[$item]['xpallet'] = $product->getData('xpallet') == '0' ? false : true;



            $returnArray[$item]['dia'] = $product->getData('dia');
            $returnArray[$item]['measurements'] = $product->getData('measurements');
            $returnArray[$item]['shademeasurements'] = $product->getData('shademeasurements');

            $returnArray[$item]['material'] = $product->getData('material');

            $returnArray[$item]['qty'] = $qty;
            foreach ($this->stores as $store_id) {

                //$returnArray[$item]['store'][$store_id]=$product->getWebsiteIds();
                //$returnArray[$item]['stock_state'][$store_id] = $stockState->getStockQty($product->getId(), $store_id);

                $returnArray[$item]['store_status'][$store_id] = $product->getResource()->getAttributeRawValue($product->getId(), 'status', $store_id) == '1' ? true : false;
            }
        }

        return $returnArray;
    }

    protected function combinations($arrays) {
        $result = array(array());
        foreach ($arrays as $property => $property_values) {
            $tmp = array();
            foreach ($result as $result_item) {
                foreach ($property_values as $property_key => $property_value) {

                    $tmp[] = $result_item + array($property => $property_value);
                }
            }
            $result = $tmp;
        }
        return $result;
    }

    protected function isJson($string) {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }

    public function catchException(\Magento\Framework\App\Bootstrap $bootstrap, \Exception $exception) {
        return $exception;
    }

    protected function flushCache() {
        $cacheTypeList = $this->objectManager->get('\Magento\Framework\App\Cache\TypeListInterface');
        $cacheFrontendPool = $this->objectManager->get('\Magento\Framework\App\Cache\Frontend\Pool');
        $_types = [
            'config',
            'layout',
            'block_html',
            'collections',
            'reflection',
            'db_ddl',
            'eav',
            'config_integration',
            'config_integration_api',
            'full_page',
            'translate',
            'config_webservice',
        ];
        try {
            foreach ($_types as $type) {
                $cacheTypeList->cleanType($type);
            }
            foreach ($cacheFrontendPool as $cacheFrontend) {
                $cacheFrontend->getBackend()->clean();
            }
            return 'cache flushed';
        } catch (Exception $e) {
            return 'cache not flushed';
        }
    }

}

$app = $bootstrap->createApplication('CustomApp');

$bootstrap->run($app);
?>
