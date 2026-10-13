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
					;
					
			foreach ($collection as $productColl) {

				$prodSku = $productColl->getSku();

				$prodObj = new stdClass();

				$prodObj->magId = $productColl->getId();

				$product = magentoProd::getMagProduct($prodObj, 'sku', 'sku', 'magId', true);

				$baseImage = $product->getImage();

				if (!empty($baseImage)) {
					generalLib::mesg('skipping sku \'' . $prodSku . '\' - has base image');
					continue;
				}

				//print_r($productColl->getData());

				//$product = $this->productRepository->getById( $productColl->getId() );
				$mediaGalleryEntriesTemp = [];

				$i = 1;

				$setBaseImg = false;

				foreach ($product->getMediaGalleryEntries() as $item) {

					//print_r($item->getData());

					$fileBaseName = basename( $item->getFile() );	
					//echo 'fileBaseName = ' . $fileBaseName . "\n";
					$fileNameArr = explode('_', $fileBaseName);
					//print_r($fileNameArr);
				
					if ( ( count($fileNameArr) > 1 ) && ( $fileNameArr[1] == '0' || $fileNameArr[1] == '0.jpg') ) {
	
				        $item->setTypes(['image', 'small_image', 'thumbnail']);

				        generalLib::mesg('Setting base image \'' . $item->getFile() . '\'');
						$setBaseImg = true;

				    }

				    $mediaGalleryEntriesTemp[] = $item;
				    $i++;
				}

				if ($i == 1) {
					generalLib::mesg('skipping sku \'' . $prodSku . '\' - no images found');
					continue;
				}

				if (!$setBaseImg) {
					generalLib::mesg('skipping sku \'' . $prodSku . '\' - no primary image found');
					continue;
				}


				$product->setMediaGalleryEntries($mediaGalleryEntriesTemp);

				//die();
				try {
						generalLib::mesg('Saving product sku \'' . $prodSku . '\'');
						$productRepository= $this->objectManager->create('Magento\Catalog\Api\ProductRepositoryInterface');
						$productRepository->save($product);
				}
				
				catch(Exception $e) {
					$rc = false;
					generalLib::error($e->getMessage());
					generalLib::error('Product sku \'' . $$prodSku . '\' not saved');
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
