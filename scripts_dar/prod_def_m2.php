<?php
#DELETE ALL PRODUCTS
ini_set('memory_limit', '-1');

$progConfig = new stdClass();

require_once dirname(__FILE__) . '/../html/app/bootstrap.php';

$bootstrap = \Magento\Framework\App\Bootstrap::create(BP, $_SERVER);

use \Magento\Framework\AppInterface as AppInterface;

require_once('class_magentoImport.php');

require_once('class_magentoProd.php');

require_once('class_generalFuncs.php');

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

		$processObject = new defProdProcessor($this->objectManager);
		$processObject->upd_products_def_flag();
	}

	public function catchException(\Magento\Framework\App\Bootstrap $bootstrap, \Exception $exception) {
		return $exception;
	}

}

$app = $bootstrap->createApplication('CustomApp');

$bootstrap->run($app);


class defProdProcessor {

	public $objectManager;

	public $procConfig;

	//###########################
	public function __construct($objectManager) {

		$this->objectManager = $objectManager;

		$this->registry = $this->objectManager->get('Magento\Framework\Registry');

		$this->state = $this->objectManager->get('Magento\Framework\App\State');

		$this->registry->register('isSecureArea', true);

		$this->state->setAreaCode('adminhtml');

		magentoProd::prodConfig($this);

		magentoImport::importConfig($this);

		$this->procConfig = new stdClass();

		$this->procConfig->websiteDets = magentoImport::getWebSiteDets();
		//print_r($this->procConfig->websiteDets);

		$this->procConfig->storeDets = magentoImport::getStoreDets();
		//print_r($this->procConfig->storeDets);

		generalLib::setLineBrk("\n");
	}

	//###########################
	public function upd_products_def_flag() {

		$collection = $this->objectManager->create('Magento\Catalog\Model\Product')
		                ->getCollection()
		                //->addAttributeToFilter('type_id', 'simple')
		                ->setOrder('entity_id','DESC')
		                ->setPageSize(1)
		                ->setCurPage(1);

		foreach ($collection as $prodColl) {
		        $prodMax = $prodColl->getId();
		}

		if (!is_numeric($prodMax)) {
		        generalLib::error('Maximum product number is not numeric');
		}

		$batchSize = 100;

		$prodFrom = 1;
		$prodTo = $prodFrom + $batchSize;

		//echo 'prodMax = ' . $prodMax . "\n";

		while($prodTo <= $prodMax) {
			//echo 'prodFrom = ' . $prodFrom . "\n";
			//echo 'prodTo = ' . $prodTo . "\n";

			$collection = $this->objectManager->create('Magento\Catalog\Model\Product')
					->getCollection()
		 			->addAttributeToFilter('entity_id', array('from' => array($prodFrom)))
					->addAttributeToFilter('entity_id', array('to' => array($prodTo)))
					->addAttributeToFilter('type_id', 'simple')
					//->addAttributeToFilter('entity_id', '76')
					;
					
			foreach ($collection as $productColl) {

				//print_r($productColl->getData());
				$productSku = $productColl->getSku();

				$prodRec = new stdClass();
				$prodRec->entity_id = $productColl->getEntityId();
				$prodRec->sku = $productColl->getSku();

				//print_r($productColl->getData());

				//generalLib::mesg('Checking sku \'' . $productSku . '\'');

				$product = magentoProd::getMagProduct($prodRec, 'sku', 'sku', 'entity_id', false);

				if (!$product) {
					generalLib::error('Cannot load product sku \'' . $productSku . '\' - skipping');
					continue;
				} else {
					$prodWebSiteIds = $product->getWebsiteIds();

					//print_r($prodWebSiteIds);
				}

				$savedProdDefaultValue = false;

				foreach($prodWebSiteIds as $prodWebSiteId) {

					if ( $prodWebSiteId == 0 ) {
						continue;
					} else {
						$storeId = $this->procConfig->websiteDets[ $prodWebSiteId ]->defaultStoreId;
					}

					$storeProduct = magentoProd::getMagStoreProduct($prodRec, $storeId, 'sku', 'sku', 'entity_id', true);

					if ($storeProduct) {

						$isOverridden = false;

						$updDefProd = false;

						//$this->checkProdDefaultValue($storeProduct, $magStoreId, 'tax_class_id', $updDefProd);

						//IF ONE OF THE PRICE FIELDS ARE OVERRIDdEN YOU HAVE TO SET THEM ALL, OTHERWISE MAGENTO UNSETS
						//THE DEFAULT FLAGS ON THE PRICES FIELDS YOU DIDN'T UPDATE - BUGGY
						$this->checkProdDefaultValueOverridden($product, $storeId, 'rrp', $isOverridden);

						$this->checkProdDefaultValueOverridden($product, $storeId, 'srp', $isOverridden);

						$this->checkProdDefaultValueOverridden($product, $storeId, 'price', $isOverridden);

						$this->checkProdDefaultValue($storeProduct, $storeId, 'rrp', $isOverridden, $updDefProd);

						$this->checkProdDefaultValue($storeProduct, $storeId, 'srp', $isOverridden, $updDefProd);

						$this->checkProdDefaultValue($storeProduct, $storeId, 'price', $isOverridden, $updDefProd);

						//$this->checkProdDefaultValue($storeProduct, $magStoreId, 'special_from_date', $updDefProd);

						if ($updDefProd) {
							
							$rc = true;

							try {
								generalLib::mesg('Saving product sku \'' . $productSku . '\'');
								$storeProduct->save();
							}
						
							catch(Exception $e) {
								generalLib::error($e->getMessage());
								generalLib::error('Product sku \'' . $productSku . '\' not saved');
								$rc = false;
							}

							if ($rc && ( !$savedProdDefaultValue ) ) {
								$savedProdDefaultValue = true;
							}

						}

					}

				}

				if ($savedProdDefaultValue) {

					$rc = $this->chkProdCustOptions( $product->getId() );
					//die();

				}

				//die();
			}

			//unset($product);
			//unset($productSku);

			if ($prodTo == $prodMax) {
				break;
			}

			$prodFrom = $prodTo + 1;

			if ($prodFrom > $prodMax) {
				$prodFrom = $prodMax;
			}

			$prodTo = $prodFrom + $batchSize;

			if ($prodTo > $prodMax) {
				$prodTo = $prodMax;
			}

			unset($collection);
		}
	}


	//###########################
	public function checkProdDefaultValueOverridden($product, $storeId, $prodField, &$isOverridden) {

		$scopeOverriddenValue = $this->objectManager->create('\Magento\Catalog\Model\Attribute\ScopeOverriddenValue');

		$overridden = $scopeOverriddenValue->containsValue(
        					\Magento\Catalog\Api\Data\ProductInterface::class,
        					$product,           //Product object
        					$prodField,     	//Attribute Name
        					$storeId            //Store view Id
    					);

		if ( $overridden ) {

			if (!$isOverridden) {
			
				$isOverridden = true;

			}

		}
		
	}


	//###########################
	public function checkProdDefaultValue(&$product, $storeId, $prodField, $isOverridden, &$updDefProd) {

		if ( $isOverridden ) {

			if ( !$updDefProd ) {
				generalLib::mesg('');
				generalLib::mesg('=======');
			}

			generalLib::mesg('Setting '. $prodField . ' default value flag for store id \'' . $storeId . '\' ('. $this->procConfig->storeDets[ $storeId ]->storeName . ')');
			$product->setData($prodField, false);

			$updDefProd = true;
		}

	}


	//###########################
	public function chkProdCustOptions( $prodId ) {

		$rc = true;

		$prodObj = new stdClass();
		$prodObj->magId = $prodId;

		$product = magentoProd::getMagProduct($prodObj, 'sku', 'sku', 'magId', true);

		if ($product) {

			$gotProdOptions = false;

			foreach ($product->getOptions() as $customOption) {
				//echo "gotOptions\n";
				$gotProdOptions = true;
				break;
			}

			$hasProdOptions = $product->getHasOptions();

			//echo 'hasProdOptions = ' . $hasProdOptions . "\n";

			if ($hasProdOptions == '0' && $gotProdOptions) {

				$product->setHasOptions(true);
				$product->setCanSaveCustomOptions(true);

				generalLib::mesg('=====');
				generalLib::mesg('Setting custom options flag');

				try {
					generalLib::mesg('Saving product sku \'' . $product->getSku() . '\'');
					$product->save();
					//die();
				}

				catch(Exception $e) {
					$rc = false;
					generalLib::error($e->getMessage());
					generalLib::error('Product sku \'' . $product->getSku() . '\' not saved');
				}
			}

		}

		return $rc;
	}

}

?>
