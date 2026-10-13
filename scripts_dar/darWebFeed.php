<?php

/*
 * Name:		
 * Company: 		Rakdesign
 * Author:		Martin
 * Function:		
 */

ini_set('memory_limit', '3072M');

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
    protected $maparray;
    protected $upsell;
    protected $separatorReplace;
    protected $multiselectOptions;
    protected $multiselectOptionsArray;
    protected $attsTickUseDefaultValue;

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
        $this->attsTickUseDefaultValue = ["'total_watts'", "'minheight_filter'", "'maxheight_filter'","'rrp'","'rrp_vat'"];
        $this->separatorReplace = ['product_finish'];
        $this->multiselectOptions = ['product_material', 'appearance', 'lamplife', 'room', 'features', 'product_style', 'electrical_class', 'bulbinc'];
        //key=magentoAttribute value=webfeed Attribute
        $this->maparray = [
            //'type'???
            'gtin' => 'gtin_number',
            'ean' => 'ean_number',
            'product_finish' => 'product_finish',
            'shadeincluded' => 'shadeincluded',
            'shadematerial' => 'shadematerial',
            'minheight_filter' => 'Min_Height',
            'maxheight_filter' => 'Max_Height',
            'measurements' => 'measurements',
            'lumens' => 'Lumens',
            'lumens_second' => 'Lumens_Second',
            'lamp' => 'lamp',
            'recommendedlamp' => 'Low_Energy_Equiv',
            'lamp_second' => 'lamp_second',
            'bulbinc' => 'bulbinc', //DONE $this->multiselectOptions
            'bulbinc_second' => 'bulbinc_second',
            'iprating' => 'iprating',
            'symbols' => 'Icons',
            'recommendedlamp_second' => 'recommendedlamp_second',
            'basemeasurements' => 'basemeasurements',
            'shademeasurements' => 'shademeasurements',
            'product_material' => 'product_material', //DONE $this->multiselectOptions
            'appearance' => 'Appearance', ////DONE $this->multiselectOptions
            'nettweight' => 'nettweight',
            'shadereference' => 'shadereference',
            'efficiency' => 'efficiency',
            'lamplife' => 'lamplife', ////DONE $this->multiselectOptions
            'colourtemp' => 'colourtemp',
            'colourrendering' => 'colourrendering',
            'efficiency_second' => 'efficiency_second',
            'lamplife_second' => 'lamplife_second',
            'colourtemp_second' => 'colourtemp_second',
            'colourrendering_second' => 'colourrendering_second',
            'suitable' => 'suitable',
            'usefultoknow' => 'usefultoknow',
            'room' => 'room', //DONE $this->multiselectOptions
            'total_watts' => 'total_watts',
            'features' => 'features', //DONE $this->multiselectOptions
            'product_style' => 'product_style', //DONE $this->multiselectOptions
            'electrical_class' => 'electrical_accessories', //DONE $this->multiselectOptions
            //'motion_sensor' ??
            // solar_powered ??
            'cable' => 'cable',
            'fixing_plate_dimensions' => 'fixing_plate_dimensions',
            'upsell' => 'spares',
            'combination_line_two' => 'configurator' //shade configurator SHADE CODES
        ];
        $storeManager = $this->objectManager->get('\Magento\Store\Model\StoreManagerInterface');

        $storeManager->setCurrentStore(\Magento\Store\Model\Store::ADMIN_CODE);

        var_dump($storeManager->getStore()->getBaseUrl());

        if (isset($argv[2]) && $argv[2] == 'tick_default_value') {
            //php darWebFeed.php UAT tick_default_value
            $this->tickUseDefaultValue();
        }
        
        
        if (isset($argv[2]) && $argv[2] == 'getfeed') {
            //php darWebFeed.php LIV getfeed
            $this->getfeed();
        }
        if (isset($argv[2]) && $argv[2] == 'updatefeed') {
            //php darWebFeed.php LIV updatefeed

            foreach ($this->multiselectOptions as $multiselectAttrCode) {

                $this->setMultiselectOptions($multiselectAttrCode);
            }

            $this->updatefeed(isset($argv[3]) ? $argv[3] : false);
        }
    }

    /*
     * function to get multiselect option_id
     *      
     */

    protected function setMultiselectOptions($attr_name) {
        $resource = $this->objectManager->get('Magento\Framework\App\ResourceConnection');
        $connection = $resource->getConnection();

        $sql = 'select att.attribute_code,ova.option_id,ova.value FROM eav_attribute as att '
                . ' left join eav_attribute_option as opt on att.attribute_id=opt.attribute_id '
                . ' left join eav_attribute_option_value as ova on opt.option_id=ova.option_id and ova.store_id=0'
                . ' where att.attribute_code="' . $attr_name . '" ';

        $result = $connection->fetchAll($sql);


        if ($result) {
            foreach ($result as $item) {

                $this->multiselectOptionsArray[$attr_name][$item['value']] = $item['option_id'];
            }
        }

        return true;
    }

    protected function addUpsellProduct($product, $upsellSkus) {
        $productSku = $product->getSku();
        $linkData = $product->getProductLinks();
        $position = 1;
        foreach ($upsellSkus as $upsellSku) {
            $productLink = $this->objectManager->create('Magento\Catalog\Api\Data\ProductLinkInterface')
                    ->setSku($productSku)
                    ->setLinkedProductSku($upsellSku)
                    ->setPosition($position)
                    ->setLinkType('upsell');

            $linkData[] = $productLink;
            $position++;
        }
        return $linkData;
    }

    /*

     * function to tick the UseDefaultValue for certain attributes
     * 
     *      */
    protected function tickUseDefaultValue() {
      
        $resource = $this->objectManager->get('Magento\Framework\App\ResourceConnection');
        $connection = $resource->getConnection();
        $tableName = $resource->getTableName('eav_attribute'); //gives table name with prefix
        $sql = "Select attribute_id FROM " . $tableName . ' where entity_type_id=4 and attribute_code IN (' . implode(',', $this->attsTickUseDefaultValue) . ')';
       
        try {
            $result = $connection->fetchAll($sql);
        } catch (Exception $e) {
            $rc = false;
            generalLib::error($e->getMessage());
            generalLib::error($sql);
            return;
        }
        $attributes = array();
        if ($result) {
            foreach ($result as $item) {
                $attributes[] = $item['attribute_id'];
            }
        }
        if (!empty($attributes)) {
            $tableName = $resource->getTableName('catalog_product_entity_decimal'); //gives table name with prefix
            $sql = "Delete FROM " . $tableName . ' where store_id!=0 and attribute_id in(' . implode(',', $attributes) . ')';
            try {
                $result = $connection->query($sql);
            } catch (Exception $e) {
                $rc = false;
                generalLib::error($e->getMessage());
                generalLib::error($sql);
                return;
            }
        }

       return;
        /* try{
          generalLib::mesg('Removing values for other stores for option_type_id \'' . $customOptionValue->getOptionTypeId() . '\'');
          $result = $connection->query($sql);
          }catch(Exception $e) {
          $rc = false;
          generalLib::error($e->getMessage());
          generalLib::error($sql);
          } */
    }

    public function updatefeed($part=false) {
        set_time_limit(0);
        $this->tickUseDefaultValue();
        $string = file_get_contents($this->pconfig->progConfig->baseDir . "/scripts/json_feed/results.json");
        $parts=['current'=>1,'all'=>1];
        
        if ($part) {
            $tempParts = explode('_', $part);
            if (isset($tempParts[1]) && isset($tempParts[2]) && ((int) $tempParts[1] <= (int) $tempParts[2]) && (int) $tempParts[1]>0) {
                $parts = ['current' => (int) $tempParts[1]-1, 'all' => (int) $tempParts[2]];
            }
        }

        if ($this->isJson($string)) {
            $json = json_decode($string, true);
            $tempJsonArray=array_chunk($json,ceil(count($json) / $parts['all'])); //chunk into parts
            $json=$tempJsonArray[$parts['current']];
          
            $resultArray = array();
            $upsellArray = array();
            $skuCollection = array();
            
            foreach ($json as $key => $row) {
                if (!isset($row['SKU'])) {
                    continue;
                }
                /*if ($row['SKU'] != 'LA3756181-Q') {
                    continue;
                }*/
                $skuCollection[] = $row['SKU'];
                foreach ($row as $rowkey => $item) {
                    if (in_array($rowkey, $this->maparray)) {
                        $mappedKey = array_search($rowkey, $this->maparray);
                        //var_dump($mappedKey);


                        if ($mappedKey == 'upsell') {
                            if ($item != '') {
                                $upsells = explode('|', $item);
                                foreach ($upsells as $upsell) {
                                    $upsellArray[$row['SKU']][] = $upsell;
                                }
                            }
                            continue; //continue to prevent adding upsell as attribute
                        } elseif (in_array($mappedKey, $this->multiselectOptions)) { //for attribute type select and multiselect
                            $multiselect = explode('|', $item);
                            //var_dump($row['SKU'].'-----------------------------------------------------');
                            // array to get opiton IDS for multiselect
                            $multiselectValueOptionID = array();
                            foreach ($multiselect as $value) {
                                //var_dump($item,$value);
                                if (isset($this->multiselectOptionsArray[$mappedKey][$value])) {
                                    $multiselectValueOptionID[] = $this->multiselectOptionsArray[$mappedKey][$value];
                                }
                            }
                            //var_dump($rowkey);
                            // var_dump($multiselectValueOptionID);
                            $resultArray[$row['SKU']][$mappedKey] = implode(',', $multiselectValueOptionID);
                            continue;
                        }
                        if (in_array($mappedKey, $this->separatorReplace)) {
                            //replace separated text attributes from webfeed
                            $item = str_replace('|', ', ', $item);
                        }
                        $resultArray[$row['SKU']][$mappedKey] = str_replace('Ã', 'Ø', $item);
                    }
                }
            }


            $actions = $this->objectManager->create('\Magento\Catalog\Model\ResourceModel\Product\Action');

            // $skuCollection = 'ABA4750'; //for DEBUG

            $products = $this->objectManager->create('Magento\Catalog\Model\Product')
                    ->getCollection()
                    ->addAttributeToSelect('*')
                    ->addAttributeToFilter('sku', $skuCollection);
            //var_dump($this->pconfig->progConfig->localLogDir); exit;
            $partLog='';
            if($part){
                 $partLog='_'.$part;
            }
            $logFilename = $this->pconfig->progConfig->localLogDir . '/attributeupdatefeed'.$partLog.'-' . date('Y_m_d_h_i_s');
            //var_dump($logFilename); exit;
            generalLib::setGeneralLogFile($logFilename . '-general.log', false);
            generalLib::setErrorLogFile($logFilename . '-error.log', true, false);

            $this->objectManager->get('Magento\Framework\App\State')->setAreaCode(\Magento\Framework\App\Area::AREA_ADMINHTML);
            $count=count($products);
            $progress='';
            $progressKey=1;
            $skuProductCollection=array();
            if($part){
                generalLib::mesg('Part ' . $part);
            }
            foreach ($products as $product) {
                //var_dump($product->getName()); exit;
                $progress=$progressKey.'/'.$count.' ';
                $progressKey++;
                $skuProductCollection[]=$product->getSku();
                if (isset($upsellArray[$product->getSku()])) {
                    try {

                        generalLib::mesg('Product update upsells -> ' . $product->getSku());
                        $linkData = $this->addUpsellProduct($product, $upsellArray[$product->getSku()]);
                        $productInterface = $this->objectManager->create('Magento\Catalog\Api\ProductRepositoryInterface')->get($product->getSku());
                        $productInterface->setProductLinks($linkData)->save();
                    } catch (Exception $e) {
                        generalLib::error($progress.' '.$e->getMessage());
                        generalLib::error($progress.'Product upsells not updated -> ' . $product->getSku());
                    }
                }
                try {
                    generalLib::mesg($progress.'Product attribute update -> ' . $product->getSku());
                    $actions->updateAttributes([$product->getId()], $resultArray[$product->getSku()], 0);
                } catch (Exception $e) {
                    generalLib::error($progress.' '.$e->getMessage());
                    generalLib::error($progress.'Product not updated -> ' . $product->getSku());
                }


                //var_dump($status);
            }
        } else {
            generalLib::error('Json error');
            mail("marcin.skora@itdesk.eu", "read json error", 'read json error');
        }
        $resultArray = array_diff($skuCollection, $skuProductCollection);
        generalLib::mesg('Products from json not found in magento -> ' . implode(' , ', $resultArray));
        $cacheFlush=$this->flushCache();
        generalLib::mesg('Cache Info -> ' . $cacheFlush);
    }

    public function getfeed() {
        set_time_limit(18000000);
        $ch = curl_init();
        //var_dump($this->pconfig->progConfig->baseDir); exit;
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1800000);
        curl_setopt($ch, CURLOPT_TIMEOUT, 1800000); //timeout in seconds
        curl_setopt($ch, CURLOPT_URL, 'http://89.197.113.104:10001/server.php');
        $result = curl_exec($ch);

        $logFilename = $this->pconfig->progConfig->localLogDir . '/attribute-getfeed-' . date('Y-m-d-h_i_s');
        //var_dump($logFilename); exit;
        generalLib::setGeneralLogFile($logFilename . '.log', false);
        generalLib::setErrorLogFile($logFilename . '-error.log', true, false);
        if (curl_errno($ch)) {
            generalLib::error(curl_error($ch));
            throw new Exception(curl_error($ch));
        }
        $curlInfo = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlInfoFull = curl_getinfo($ch);
        curl_close($ch);

        if ((int) $curlInfo === 200) {
            ///mail("marcin.skora@itdesk.eu", "darWebFeed.php UAT getfeed DONE", 'done');
            generalLib::mesg('CURL status 200');
            $obj = json_decode($result);
            $fp = fopen($this->pconfig->progConfig->baseDir . '/scripts/json_feed/results.json', 'w');
            fwrite($fp, json_encode(json_decode($result)));
            fclose($fp);

            /* $fp1 = fopen($this->pconfig->progConfig->baseDir.'/scripts/json_feed/results' . date('Y-m-d-H-i-s') . '.json', 'w');
              fwrite($fp1, json_encode(json_decode($result)));
              fclose($fp1); */
        } else {
            mail("marcin.skora@itdesk.eu", "darWebFeed.php LIVE getfeed not working", json_encode($curlInfoFull));
            generalLib::error(json_encode($curlInfoFull));
        }
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
            'config_webservice'
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
