<?php
#DELETE ALL PRODUCTS
ini_set('memory_limit', '-1');

$progConfig = new stdClass();

require_once dirname(__FILE__) . '/../html/app/bootstrap.php';

$bootstrap = \Magento\Framework\App\Bootstrap::create(BP, $_SERVER);

use \Magento\Framework\AppInterface as AppInterface;

require_once('class_generalFuncs.php');

class updProdProcessor {

	public $objectManager;

	//###########################
	public function __construct($objectManager) {

		$this->objectManager = $objectManager;

		$this->objectManager->get('Magento\Framework\Registry')->register('isSecureArea', true);

		$this->objectManager->get('Magento\Framework\App\State')->setAreaCode(\Magento\Framework\App\Area::AREA_ADMINHTML);
	}

	//###########################
	public function updProductBaseImage() {

		$collection = $this->objectManager->create('Magento\Catalog\Model\Product')
		                ->getCollection()
		                ->addAttributeToFilter('type_id', 'simple')
		                ->addAttributeToFilter('sku', array('like' => '%/P'))
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
					->addAttributeToFilter('entity_id', array('to' => array($prodTo)))
					->addAttributeToFilter('sku', array('like' => '%/P'))
					//->addAttributeToFilter('sku', 'TUC0746/P')
					//->addAttributeToFilter('sku', 'ACE2045/11GU/P')
					//->addAttributeToFilter('sku', '44400003680/P')
					;
					
			foreach ($collection as $productColl) {
				$promoSku = $productColl->getSku();

				generalLib::mesg("\n=====");
				generalLib::mesg('Processing promo sku \'' . $promoSku . '\'');

				//echo 'promoSku = ' . $promoSku . "\n";

				$promoProduct = $this->objectManager->create('Magento\Catalog\Model\Product')->load( $productColl->getId() );

				$promoSkuImages = $promoProduct->getMediaGalleryImages();

				$promoSkuBaseImage = $promoProduct->getImage();

				//ALREADY GOT A BASE IMAGE

				//echo 'promoSkuBaseImage = ' . $promoSkuBaseImage . "\n";

				if ( ( !empty( $promoSkuBaseImage ) ) && ( $promoSkuBaseImage != 'no_selection' ) ) {
					generalLib::mesg('Promo sku \'' . $promoSku . '\' has a base image - skipping');
					continue;
				}

				//echo 'cnt = ' . count( $promoSkuImages) . "\n";

				if ( count( $promoSkuImages ) > 0 ) {

					$nonPromoSku = substr($promoSku, 0, -2);

					//echo 'nonPromoSku = ' . $nonPromoSku . "\n";

					$nonPromoProduct = $this->objectManager->create('Magento\Catalog\Model\Product')->loadByAttribute('sku', $nonPromoSku);

					if ($nonPromoProduct) {

						$nonPromoProduct = $this->objectManager->create('Magento\Catalog\Model\Product')->load($nonPromoProduct->getId());

						$nonPromoSkuImages = $nonPromoProduct->getMediaGalleryImages();

						$nonPromoSkuBaseImage = $nonPromoProduct->getImage();

						//echo 'nonPromoSkuBaseImage = ' . $nonPromoSkuBaseImage . "\n";

						$promoSkuBaseImage = str_replace( $nonPromoSku, $nonPromoSku . '-P', $nonPromoSkuBaseImage);

						//echo 'promoSkuBaseImage = ' . $promoSkuBaseImage . "\n";

						$updProd = false;

						foreach ($promoSkuImages as $promoSkuImage) {

							$promoImgFile = $promoSkuImage->getFile();

							//echo 'promoImgFile = ' . $promoImgFile . "\n";

							if ($promoImgFile == $promoSkuBaseImage) {
								//echo 'base promoImgFile = ' . $promoImgFile . "\n";
								generalLib::mesg('Setting base, small and thumbnail image to \'' . $promoImgFile . '\'');

								$promoProduct->setImage( $promoSkuBaseImage );

								$promoProduct->setSmallImage( $promoSkuBaseImage );

								$promoProduct->setThumbnail( $promoSkuBaseImage );

								$updProd = true;
								break;
							}

						}

						if ($updProd) {

							try {
								generalLib::mesg('Saving product sku \'' . $promoSku . '\'');
								$promoProduct->save();
							}
									
							catch(Exception $e) {
								generalLib::error($e->getMessage());
								generalLib::error('Product sku \'' . $promoSku . '\' not updated');
							}

						} else {
							generalLib::mesg('No corresponding promo image found for \'' . $promoSkuBaseImage . '\'');	
						}

						/*

						foreach ($nonPromoSkuImages as $nonPromoSkuImage) {

							$nonPromoImgFile = $nonPromoSkuImage->getFile();

							echo 'nonPromoImgFile = ' . $nonPromoImgFile . "\n";

							if ($nonPromoImgFile == $nonPromoSkuBaseImage) {

								echo 'base nonPromoImgFile = ' . $nonPromoImgFile . "\n";
							}

						}

						*/

					} else {
						generalLib::mesg('No corresponding non promotion product found for promo sku \'' . $promoSku . '\' - skipping');
					}

				} else {
					generalLib::mesg('No promo images to update for promo sku \'' . $promoSku . '\' - skipping');
				}

				//die();
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

		generalLib::mesg('End of Line');
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

		$processObject = new updProdProcessor($this->objectManager);
		$processObject->updProductBaseImage();
	}

	public function catchException(\Magento\Framework\App\Bootstrap $bootstrap, \Exception $exception) {
		return $exception;
	}

}

$app = $bootstrap->createApplication('CustomApp');

$bootstrap->run($app);
?>
