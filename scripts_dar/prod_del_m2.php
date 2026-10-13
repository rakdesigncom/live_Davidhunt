<?php
#DELETE ALL PRODUCTS
ini_set('memory_limit', '-1');

$progConfig = new stdClass();

require_once dirname(__FILE__) . '/../html/app/bootstrap.php';

$bootstrap = \Magento\Framework\App\Bootstrap::create(BP, $_SERVER);

use \Magento\Framework\AppInterface as AppInterface;

require_once('class_generalFuncs.php');

class delProdProcessor {

	public $objectManager;

	//###########################
	public function __construct($objectManager) {

		$this->objectManager = $objectManager;

		$this->objectManager->get('Magento\Framework\Registry')->register('isSecureArea', true);

		$this->objectManager->get('Magento\Framework\App\State')->setAreaCode(\Magento\Framework\App\Area::AREA_ADMINHTML);
	}

	//###########################
	public function del_products() {

		$collection = $this->objectManager->create('Magento\Catalog\Model\Product')
		                ->getCollection()
		                ->addAttributeToFilter('type_id', 'simple')
		                ->setOrder('entity_id','DESC')
		                ->setPageSize(1)
		                ->setCurPage(1);

		foreach ($collection as $product) {
		        $prodMax = $product->getId();
		}

		if (!is_numeric($prodMax)) {
		        generalLib::error('Maximum product number is not numeric');
		}

		$batchSize = 1000;

		$prodFrom = 1;
		$prodTo = $prodFrom + $batchSize;

		while($prodTo <= $prodMax) {
			echo 'prodFrom = ' . $prodFrom . "\n";
			echo 'prodTo = ' . $prodTo . "\n";

			$collection = $this->objectManager->create('Magento\Catalog\Model\Product')
					->getCollection()
		 			->addAttributeToFilter('entity_id', array('from' => array($prodFrom)))
					->addAttributeToFilter('entity_id', array('to' => array($prodTo)));
					
			foreach ($collection as $productColl) {
				$productSku = $productColl->getSku();

				$product = $this->objectManager->create('Magento\Catalog\Model\Product')->load( $productColl->getId() );

				try {
					generalLib::mesg('Deleting product sku \'' . $productSku . '\'');
					$product->delete();
				}
						
				catch(Exception $e) {
					generalLib::error($e->getMessage());
					generalLib::error('Product sku \'' . $productSku . '\' not deleted');
				}

			}

			unset($product);
			unset($productSku);

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

		$processObject = new delProdProcessor($this->objectManager);
		$processObject->del_products();
	}

	public function catchException(\Magento\Framework\App\Bootstrap $bootstrap, \Exception $exception) {
		return $exception;
	}

}

$app = $bootstrap->createApplication('CustomApp');

$bootstrap->run($app);
?>

