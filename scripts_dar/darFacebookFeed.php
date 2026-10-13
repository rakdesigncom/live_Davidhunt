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
    protected $maparray;
    protected $upsell;
    protected $separatorReplace;
    protected $multiselectOptions;
    protected $multiselectOptionsArray;

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

     
        $storeManager = $this->objectManager->get('\Magento\Store\Model\StoreManagerInterface');

        $storeManager->setCurrentStore(\Magento\Store\Model\Store::ADMIN_CODE);

        var_dump($storeManager->getStore()->getBaseUrl());
        if (isset($argv[2]) && $argv[2] == 'updateFeed') {
            //php darWebFeed.php UAT getfeed
            $this->updateFeed();
        }

    }

    protected function updateFeed(){
        $fbShopHelper=$this->objectManager->get('\Webkul\FacebookShop\Helper\Data');
        $this->objectManager->get('Magento\Framework\App\State')->setAreaCode(\Magento\Framework\App\Area::AREA_FRONTEND);
        try{
            $fbShopHelper->generateFbFeedCsv('Cron');
        } catch (Exception $e){
            var_dump($e->getMessage());
        }
        //var_dump('ad');
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
