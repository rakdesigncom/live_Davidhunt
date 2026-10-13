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

require_once($progConfig->configType . 'Config.php');

$progConfigObj = new progConfig();

$progConfig = $progConfigObj->progConfig;

require_once($progConfig->scriptDir . '/class_darImgProcessor.php');
require_once($progConfig->scriptLibDir . '/class_downloadFileLib.php');
require_once($progConfig->scriptLibDir . '/class_magentoImgImport.php');
require_once($progConfig->scriptLibDir . '/class_magentoImport.php');
require_once($progConfig->scriptLibDir . '/class_magentoGeneral.php');
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
   
		//$dirList = $this->objectManager->get('\Magento\Framework\App\Filesystem\DirectoryList');
		//$mediaDirectory = $dirList->getPath('media');
		//echo 'mediaDirectory = ' . $mediaDirectory . "\n";

		 //$config = $this->objectManager->get('Magento\Catalog\Model\Product\Media\Config');
		 //$mediaDirectory = $config->getBaseTmpMediaPath();
		//echo 'mediaDirectory = ' . $mediaDirectory . "\n";
		//die();
		//$fs = $this->objectManager->get('\Magento\Framework\Filesystem');
		//echo \Magento\Framework\App\Filesystem\DirectoryList::MEDIA . "\n";
		//die();
		//$mediaDirectory = $fs->getDirectoryWrite(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA);
		//echo 'mediaDirectory = ' . $mediaDirectory . "\n";
		//die();

		$darImg = new darImgProcessor($this->objectManager);
		$darImg->processDownloadedImgFiles();
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
