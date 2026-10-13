<?php
	// Allow unlimited memory
	ini_set('memory_limit', '-1');
	ini_set('auto_detect_line_endings', TRUE);

	header('Content-type: text/html; charset=utf-8');

	require_once dirname(__FILE__) . '/../html/app/bootstrap.php';

	$bootstrap = \Magento\Framework\App\Bootstrap::create(BP, $_SERVER);

	use \Magento\Framework\AppInterface as AppInterface;


require_once('class_generalFuncs.php');

class ProdNoImgsProcessor {
	public $objectManager;

	public function __construct( $objectManager ) {
		$this->objectManager = $objectManager;

		$this->objectManager->get('Magento\Framework\Registry')->register('isSecureArea', true);

		$this->objectManager->get('Magento\Framework\App\State')->setAreaCode(\Magento\Framework\App\Area::AREA_ADMINHTML);

		generalLib::setLineBrk("\n");
	}

	public function getProdSku() {

		$products = $this->objectManager->create('Magento\Catalog\Model\Product')
					->getCollection()
					//->addAttributeToFilter('sku', 'ECARG')
					//->addAttributeToSelect('*')
	    			;

		if ($products->getSize() > 0) {

			foreach ($products as $product) {
				//print_r($product->getData());
				//echo 'Hello World';
				echo $product->getId() . ',' . $product->getSku() . "\n";
			}
		}	
	}
}

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

		$CustOptProcessObject = new ProdNoImgsProcessor($this->objectManager);
		$CustOptProcessObject->getProdSku();
	}

	public function catchException(\Magento\Framework\App\Bootstrap $bootstrap, \Exception $exception) {
		generalLib::error($exception->getMessage());
		return $exception;
	}

}

$app = $bootstrap->createApplication('CustomApp');

$bootstrap->run($app);
?>
