<?php
#DELETE ALL PRODUCTS
ini_set('memory_limit', '-1');

$progConfig = new stdClass();

require_once dirname(__FILE__) . '/../html/app/bootstrap.php';

$bootstrap = \Magento\Framework\App\Bootstrap::create(BP, $_SERVER);

use \Magento\Framework\AppInterface as AppInterface;

require_once('class_generalFuncs.php');
require_once('class_magentoProd.php');

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

		$processObject = new prodSkuProcessor($this->objectManager);
		$processObject->chgProdSkus();
	}

	public function catchException(\Magento\Framework\App\Bootstrap $bootstrap, \Exception $exception) {
		generalLib::error($exception->getMessage());
		return $exception;
	}

}

$app = $bootstrap->createApplication('CustomApp');

$bootstrap->run($app);


class prodSkuProcessor {

	public $objectManager;

	//###########################
	public function __construct($objectManager) {

		$this->objectManager = $objectManager;

		$this->objectManager->get('Magento\Framework\Registry')->register('isSecureArea', true);

		$this->objectManager->get('Magento\Framework\App\State')->setAreaCode(\Magento\Framework\App\Area::AREA_ADMINHTML);

		magentoProd::prodConfig($this);
	}

	//###########################
	public function chgProdSkus() {

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
			echo 'prodFrom = ' . $prodFrom . "\n";
			echo 'prodTo = ' . $prodTo . "\n";

			$collection = $this->objectManager->create('Magento\Catalog\Model\Product')
					->getCollection()
		 			->addAttributeToFilter('entity_id', array('from' => array($prodFrom)))
					->addAttributeToFilter('entity_id', array('to' => array($prodTo)))
					->addAttributeToFilter('sku', array('like' => '%/%'))
					;
					
			foreach ($collection as $productColl) {

				//print_r($productColl->getData());

				$prodObj = new stdClass();
				$prodObj->magId = $productColl->getId();

				$product = magentoProd::getMagProduct($prodObj, 'sku', 'sku', 'magId', true);

				if ($product) {

					$oldProdSku = $product->getSku();

					$newProdSku = str_replace('/', '-', $oldProdSku);

					generalLib::mesg('');
					generalLib::mesg('======');	
					generalLib::mesg('Changing skus from \'' . $oldProdSku . '\' to \'' . $newProdSku . '\'');

					$product->setSku( $newProdSku );

					try {
						generalLib::mesg('Saving product sku \'' . $newProdSku . '\'');
						$product->save();
						//die();
					}

					catch(Exception $e) {
						$rc = false;
						generalLib::error($e->getMessage());
						generalLib::error('Product sku \'' . $newProdSku . '\' not saved');
					}

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

}

?>
