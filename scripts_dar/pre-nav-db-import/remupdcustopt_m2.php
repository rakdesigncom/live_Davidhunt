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


class removeDarCustomOptionsProcessor {
	public $objectManager;

	public function __construct( $objectManager ) {
		$this->objectManager = $objectManager;

		$this->objectManager->get('Magento\Framework\Registry')->register('isSecureArea', true);

		$this->objectManager->get('Magento\Framework\App\State')->setAreaCode(\Magento\Framework\App\Area::AREA_ADMINHTML);

		generalLib::setLineBrk("\n");

		magentoProd::prodConfig($this);
	}


	public function removeCustomOptions() {

		$shadeParentCollection = $this->objectManager->create('Magento\Catalog\Model\Product')
					->getCollection()
					->addAttributeToFilter('type_id', 'simple')
					->addAttributeToFilter('sku', array('in', 'SP64', 'SP65', 'SP67', 'SP68', 'SP0350', 'SP0650'));
					//->addAttributeToFilter('shade_range_product', '1');
					//->addAttributeToFilter('shade_range_product', array('eq' => '1'));

		foreach($shadeParentCollection as $shadeParent) {

			$prodObj = new stdClass();
			$prodObj->magId = $shadeParent->getId();
		
			$product = magentoProd::getMagProduct($prodObj, 'sku', 'sku', 'magId', true);
				
			if ($product) {
				$productSku = $product->getSku();

				$rc = $this->chkAndDeleteDupCustomOpt($product, $productSku);
			} else {
				generalLib::warn('Product id \'' . $shadeParent->getId() . '\' not found');
				continue;
			}

			if ($rc) {
				//FIND SKUS BEGINNING WITH THE SAME SHADE SKU - THESE MAY BE CHILD SHADE PRODUCTS, WHICH MAY NEED UPDATING

				$shadeChildCollection = $this->objectManager->create('Magento\Catalog\Model\Product')
								->getCollection()
								->addAttributeToFilter('type_id', 'simple')
								->addAttributeToFilter('shade_range_basecode', array('eq' => $productSku));
				
				foreach($shadeChildCollection as $shadeChild) {
					$shadeSku = $shadeChild->getSku();
					
					if ($shadeSku != $productSku) {
						$prodObj = new stdClass();
						$prodObj->magId = $shadeChild->getId();
		
						$product = magentoProd::getMagProduct($prodObj, 'sku', 'sku', 'magId', true);
						
						if ($product) {
							//EXCLUDE OTHER PARENT SHADE RANGE PRODUCTS
							if ($product->getShadeRangeProduct() != '1') {
								generalLib::mesg('');
								generalLib::mesg('====');
								generalLib::mesg('processing shade child \'' . $shadeSku . '\'');

								$rc = $this->chkAndDeleteDupCustomOpt($product, $shadeSku);
							}
						}	
					}
				}
			}
		}

	}

	public function chkAndDeleteDupCustomOpt($product, $productSku) {
		$rc = true;

		$delCustomOptions = false;

		//$numLinerColourCustOpts = 0;

		foreach ($product->getOptions() as $customOption) {
			//if ($customOption->getData('title') == 'Liner Colour') {
				//$numLinerColourCustOpts++;

				//if ($numLinerColourCustOpts > 1) {
					//generalLib::mesg('Deleting duplicate Liner Colour custom option for sku \'' . $productSku . '\'');
					generalLib::mesg('Deleting ' . $customOption->getData('title') . ' custom option for sku \'' . $productSku . '\'');
					$customOption->delete();
				//}
			//}
		}

		//if ($numLinerColourCustOpts > 1) {
		if ($delCustomOptions) {
			$rc = $this->saveCustomOptProd($product, $productSku, false);
		}

		return $rc;
	}


	//###########################
	public function getProductForPartnum($partNum, $reportErrors) {
		$prodObj = new stdClass();
		$prodObj->sku = $partNum;
	
		$product = magentoProd::getMagProduct($prodObj, 'sku', 'sku', 'magId', $reportErrors);

		return $product;
	}


	//###########################
	public function saveCustomOptProd($product, $productSku, $reloadProd) {
		$rc = true;

		generalLib::mesg('Saving product sku \'' . $productSku . '\'');
				
		try {
			$product->save();

			if ($reloadProd) {
				$prodKey = new stdClass();
				$prodKey->magId = $product->getId();

				$product = magentoProd::getMagProduct($prodKey, 'sku', 'sku', 'magId', true);
			}
		} 
		
		catch(Exception $e) {
			$rc = false;
			generalLib::error($e->getMessage());
			generalLib::error('Product sku \'' . $productSku . '\' not saved');
		}

		return $rc;
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

		$remCustOptProcessObject = new removeDarCustomOptionsProcessor($this->objectManager);
		$remCustOptProcessObject->removeCustomOptions();
	}

	public function catchException(\Magento\Framework\App\Bootstrap $bootstrap, \Exception $exception) {
		return $exception;
	}

}

$app = $bootstrap->createApplication('CustomApp');

$bootstrap->run($app);
?>
