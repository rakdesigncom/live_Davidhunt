<?php

/*
 * Name:			xxx.php
 * Company: 		Star Digital
 * Author:			John Little
 * Function:		XXX Processor
 */

ini_set('memory_limit', '-1');

error_reporting(E_ALL);
ini_set('display_errors', '1');

date_default_timezone_set('Europe/London');

header('Content-type: text/html; charset=utf-8');

require_once dirname(__FILE__) . '/../html/app/bootstrap.php';

$bootstrap = \Magento\Framework\App\Bootstrap::create(BP, $_SERVER);

use \Magento\Framework\AppInterface as AppInterface;


$progConfig = new stdClass();

$customer = isset($argv[1]) ? $argv[1] : 'UNKNOWN';

if ($customer  == 'UNKNOWN') {
	echo "Please specify customer\n";
	exit(1);
}

require_once('magXlsx' . $customer . 'Config.php');


$progConfigObj = new progConfig();

$progConfig = $progConfigObj->progConfig;

/** Include path **/
ini_set('include_path', ini_get('include_path') . ':' . $progConfig->scriptLibDir . '/PHPExcel-1.8.1/Classes/');

//echo ini_get('include_path');
//die();

require_once($progConfig->scriptDir . '/class_magXlsxProcessor.php');
require_once($progConfig->scriptDir . '/class_magXlsxExportProcessor.php');

$customImportProcessorFile = $progConfig->scriptDir . '/class_magXlsx' . $progConfig->customer . 'CustomProcessor.php';

if ( is_readable ( $customImportProcessorFile ) ) {
	require_once($customImportProcessorFile);
}

$customExportProcessorFile = $progConfig->scriptDir . '/class_magXlsxExport' . $progConfig->customer . 'CustomProcessor.php';

if ( is_readable ( $customExportProcessorFile ) ) {
	require_once($customExportProcessorFile);
}

require_once($progConfig->scriptLibDir . '/class_downloadFileLib.php');
require_once($progConfig->scriptLibDir . '/class_magentoImport.php');
require_once($progConfig->scriptLibDir . '/class_magentoCust.php');
require_once($progConfig->scriptLibDir . '/class_magentoProd.php');
require_once($progConfig->scriptLibDir . '/class_magentoCat.php');
//require_once($progConfig->scriptLibDir . '/class_magentoCatBrands.php');
require_once($progConfig->scriptLibDir . '/class_magentoAttrImport.php');
//require_once($progConfig->scriptLibDir . '/class_magentoConfigurableProducts.php');
require_once($progConfig->scriptLibDir . '/class_magentoGroupProducts.php');
require_once($progConfig->scriptLibDir . '/class_magentoCustomOpts.php');
require_once($progConfig->scriptLibDir . '/class_magentoImgImport.php');
//require_once($progConfig->scriptLibDir . '/class_magentoGeneral.php');
require_once($progConfig->scriptLibDir . '/PHPMailer-master/PHPMailerAutoload.php');
require_once($progConfig->scriptLibDir . '/PHPExcel-1.8.1/Classes/PHPExcel/IOFactory.php');
require_once($progConfig->scriptLibDir . '/class_excelLib.php');

//require_once($progConfig->scriptLibDir . '/class_magentoIndex.php');
require_once($progConfig->scriptLibDir . '/class_generalDateFuncs.php');
require_once($progConfig->scriptLibDir . '/class_generalFuncs.php');

class CustomApp implements AppInterface {

	protected $objectManager;

	public function __construct(
		\Magento\Framework\ObjectManagerInterface $objectManager,
		\Magento\Framework\App\Response\Http $response
	) {
		$this->objectManager = $objectManager;
		$this->_response = $response;
	}

	public function launch() {
		$this->run();
		return $this->_response;
	}

	public function run() {

		//$ProcessDarObject = new processDar($this->objectManager);
		//$ProcessDarObject->processDar();

		$storeManager = $this->objectManager->get('\Magento\Store\Model\StoreManagerInterface');

		$storeManager->setCurrentStore(\Magento\Store\Model\Store::ADMIN_CODE);
		//$storeManager->setCurrentStore('1');

		//$store = $storeManager->getStore();
		//$storeId = $store->getStoreId();
		//echo 'storeId: '.$storeId. "\n";
		//die();

		$magXlsxProcessObject = new magXlsxProcessor($this->objectManager);
		$magXlsxProcessObject->runProcessor();
	}

	public function catchException(\Magento\Framework\App\Bootstrap $bootstrap, \Exception $exception) {
		generalLib::error($exception->getMessage());
		return $exception;
	}

}

$app = $bootstrap->createApplication('CustomApp');

$bootstrap->run($app);

generalLib::term(0);
?>
