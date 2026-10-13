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

require_once($progConfig->configType . 'Config.php');

$progConfigObj = new progConfig();

$progConfig = $progConfigObj->progConfig;

require_once($progConfig->scriptLibDir . '/class_magentoProd.php');
require_once($progConfig->scriptLibDir . '/class_generalFuncs.php');


class updProdStatusProcessor {
	public $objectManager;

	public function __construct( $objectManager ) {
		$this->objectManager = $objectManager;

		$this->objectManager->get('Magento\Framework\Registry')->register('isSecureArea', true);

		$this->objectManager->get('Magento\Framework\App\State')->setAreaCode(\Magento\Framework\App\Area::AREA_ADMINHTML);

		generalLib::setLineBrk("\n");

		magentoProd::prodConfig($this);
	}


	public function updProdStatus() {

		$storeIds = array('1','2');

		$magProdId = '6640';

		foreach($storeIds as $storeId) {
			echo "store id = " . $storeId . "\n";

			//$product = $this->objectManager->create('Magento\Catalog\Model\Product')->load($magProdId);

			$attr = $this->objectManager->create('\Magento\Catalog\Model\ResourceModel\Product\Action');

			$prodsUpd = array($magProdId);

			if ($storeId == '1') {
				$attrsUpd = array('status' => \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_DISABLED);
			} else {
				$attrsUpd = array('status' => \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED);
			}

			$attr->updateAttributes($prodsUpd, $attrsUpd, $storeId);

			$prodObj = new stdClass();
			$prodObj->Magento_Product_ID = $magProdId;

			$storeProduct = magentoProd::getMagStoreProduct($prodObj, $storeId, 'dataid', 'sku', 'Magento_Product_ID', true);

			echo 'new status = ' . $storeProduct->getStatus() . "\n";

			//$product = Mage::getModel('catalog/product')->setStoreId($storeId)->load($magProdId);
			/*
			try {
			$entityManager = $this->objectManager->create('Magento\Framework\EntityManager\EntityManager');
			$product = $this->objectManager->create('\Magento\Catalog\Model\ProductRepository')->getById($magProdId, true, $storeId, true);

			$saveProduct = $this->objectManager->create('\Magento\Catalog\Model\ProductFactory');
			$saveProduct->setStoreId( $storeId );
			$saveProduct->setStatus(\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_DISABLED);
			$entityManager->save($saveProduct);
			} catch	(Exception $ex) {
				generalLib::error($ex->getMessage());
			}

			/*
			try {
			$prodCollection = $this->objectManager->create('Magento\Catalog\Model\Product')
					->getCollection()
					->addAttributeToFilter('type_id', 'simple')
					->addFieldToFilter('store_id', $storeId)
					->addAttributeToFilter('sku', '001AST001');
					//->addAttributeToFilter('shade_range_product', array('eq' => '1'));
			}

			
			catch	(Exception $ex) {
				generalLib::error($ex->getMessage());
			}

			foreach($prodCollection as $prod) {
				echo 'product id = ' . $prod->getId() . "\n";
				//$product = $this->_productRepository->getById(1,true, 2);	
			}
			*/
		}
	}
}


class CustomApp implements AppInterface {

	protected $objectManager;

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

		//$ProcessDarObject = new processDar($this->objectManager);
		//$ProcessDarObject->processDar();

		$storeManager = $this->objectManager->get('\Magento\Store\Model\StoreManagerInterface');

		$storeManager->setCurrentStore(\Magento\Store\Model\Store::ADMIN_CODE);
		//$storeManager->setCurrentStore('1');

		//$store = $storeManager->getStore();
		//$storeId = $store->getStoreId();
		//echo 'storeId: '.$storeId. "\n";
		//die();

		$updProdStatusObject = new updProdStatusProcessor($this->objectManager);
		$updProdStatusObject->updProdStatus();
	}

	public function catchException(\Magento\Framework\App\Bootstrap $bootstrap, \Exception $exception) {
		return $exception;
	}

}

$app = $bootstrap->createApplication('CustomApp');

$bootstrap->run($app);
?>
