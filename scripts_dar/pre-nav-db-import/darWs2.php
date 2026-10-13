<?php

// display all warning and errors
error_reporting(E_ALL & E_STRICT);
ini_set('display_errors', '1');
ini_set('log_errors', '0');
ini_set('error_log', './');

header('Content-type: text/html; charset=utf-8');

require_once dirname(__FILE__) . '/../html/app/bootstrap.php';

$bootstrap = \Magento\Framework\App\Bootstrap::create(BP, $_SERVER);

use \Magento\Framework\AppInterface as AppInterface;

$progConfig = new stdClass();

$progConfig->configType = 'dar';

require_once($progConfig->configType . 'NavConfig.php');

$progConfigObj = new progConfig();

$progConfig = $progConfigObj->progConfig;

require_once($progConfig->scriptDir . '/class_darWsProcessor2.php');
require_once($progConfig->scriptDir . '/class_darWsBespokeConfProcessor.php');
require_once($progConfig->scriptLibDir . '/class_NTLMStream.php');
require_once($progConfig->scriptLibDir . '/class_NTLMSoapClient.php');
require_once($progConfig->scriptLibDir . '/class_wsFuncs.php');
require_once($progConfig->scriptDir . '/darWsConfig.php');
require_once($progConfig->scriptLibDir . '/class_magentoCat.php');
require_once($progConfig->scriptLibDir . '/class_magentoImport.php');
require_once($progConfig->scriptLibDir . '/class_magentoGeneral.php');
require_once($progConfig->scriptLibDir . '/class_magentoCache.php');
require_once($progConfig->scriptLibDir . '/class_magentoExport.php');
require_once($progConfig->scriptLibDir . '/class_magentoProd.php');
require_once($progConfig->scriptLibDir . '/class_magentoCust.php');
require_once($progConfig->scriptLibDir . '/class_magentoAttrImport.php');
require_once($progConfig->scriptLibDir . '/class_magentoGroupProducts.php');
require_once($progConfig->scriptLibDir . '/class_magentoCustomOpts.php');
require_once($progConfig->scriptLibDir . '/class_magentoCustomPrices.php');
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

		$darProcessObject = new darWsProcessor($this->objectManager);
		$darProcessObject->processAction();
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
