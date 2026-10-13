<?php
ini_set('memory_limit', '-1');
error_reporting(E_ALL & E_STRICT);
ini_set('display_errors', '1');
ini_set('log_errors', '0');
ini_set('error_log', './');

date_default_timezone_set('Europe/London');

header('Content-type: text/html; charset=utf-8');

require_once dirname(__FILE__) . '/../html/app/bootstrap.php';

$bootstrap = \Magento\Framework\App\Bootstrap::create(BP, $_SERVER);

use \Magento\Framework\AppInterface as AppInterface;

$progConfig = new stdClass();

$progConfig->configType = 'dar';

require_once($progConfig->configType . 'NavConfig.php');

$progConfigObj = new progConfig();

$progConfig = $progConfigObj->progConfig;

require_once($progConfig->scriptLibDir . '/class_magentoImport.php');
require_once($progConfig->scriptLibDir . '/class_magentoExport.php');
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

		$darProcessObject = new gdprProcessor( $this->objectManager );
		$darProcessObject->maskB2cGdprOrders();
	}

	public function catchException(\Magento\Framework\App\Bootstrap $bootstrap, \Exception $exception) {
		generalLib::error($exception->getMessage());
		return $exception;
	}

}

$app = $bootstrap->createApplication('CustomApp');

$bootstrap->run($app);

generalLib::term(0);


class gdprProcessor {

	public $objectManager;
	public $procConfig;

	//###########################
	public function __construct($objectManager) {

		$this->objectManager = $objectManager;

		$this->objectManager->get('Magento\Framework\Registry')->register('isSecureArea', true);

		$this->objectManager->get('Magento\Framework\App\State')->setAreaCode(\Magento\Framework\App\Area::AREA_ADMINHTML);

		$this->procConfig = new stdClass();
		
		$progConfigObj = new progConfig();

		$this->procConfig = $progConfigObj->progConfig;

		$this->procConfig->numFirstCharsUnmasked = 2;

		magentoImport::importConfig($this);

		magentoGeneral::genConfig( $this );

		magentoExport::exportConfig( $this );

		generalLib::setLineBrk("\n");

		if (!is_dir($this->procConfig->localLogDir)) {

			if (!mkdir($this->procConfig->localLogDir)) {
				generalLib::term(1, 'Cannot create directory ' . $this->procConfig->localLogDir);
			}

		}

		$logFilename = $this->procConfig->localLogDir . '/dar_gdpr' . '-' . date('Ymd_His', magentoGeneral::getLocalTimeStamp());

		generalLib::setGeneralLogFile($logFilename . '-general.log', false);
		generalLib::setErrorLogFile($logFilename . '-error.log', true, false);
		generalLib::setWarnLogFile($logFilename  . '-warn.log', true, false);

		$this->procConfig->shipAddrMagAttrs = array('FirstName' => 'firstname', 'LastName' => 'lastname', 'City' => 'city',
								'Region' => 'region', 'Postcode' => 'postcode', 'Telephone' => 'telephone', 'Email' => 'email');

		$this->procConfig->orderMagAttrs = array('CustomerEmail' => 'customer_email', 'OrderExportStatus' => 'order_export_status');

	}


	//###########################
	public function maskB2cGdprOrders() {

		$dateTimeUTC4MonthsAgo = date('Y-m-d H:i:s', strtotime('- 4 months'));

		generalLib::mesg('Processing orders up to ' . $dateTimeUTC4MonthsAgo);


		$mergedB2cOrders = $this->objectManager->create('\Magento\Sales\Model\Order')->getCollection()->addFieldToFilter('entity_id',-1);


		$b2cOrders = $this->objectManager->create('\Magento\Sales\Model\Order')->getCollection()
						//->addAttributeToFilter('increment_id', '200000221')
						->addAttributeToFilter('order_type', 'b2c')
						->addAttributeToFilter('order_export_status', array('nlike' => '%_gdpr'))
						->addAttributeToFilter('created_at', array('to' => $dateTimeUTC4MonthsAgo))
						//->addAttributeToFilter('increment_id', array('in' => array('2000087680','2000087679','2000087617','2000087436','2000087305','2000087304')))
						->setOrder( 'increment_id', 'ASC' )
						;

		magentoExport::mergeOrders($b2cOrders, $mergedB2cOrders);


		$b2cOrders = $this->objectManager->create('\Magento\Sales\Model\Order')->getCollection()
						->addAttributeToFilter('order_type', 'b2c')
						->addAttributeToFilter('order_export_status', array('null' => true))
						->addAttributeToFilter('created_at', array('to' => $dateTimeUTC4MonthsAgo))
						//->addAttributeToFilter('increment_id', array('in' => array('2000087680','2000087679','2000087617','2000087436','2000087305','2000087304')))
						->setOrder( 'increment_id', 'ASC' )
						;

		magentoExport::mergeOrders($b2cOrders, $mergedB2cOrders);


		foreach ($mergedB2cOrders as $mergedB2cOrder) {
			$rc = true;

			generalLib::mesg('');
			generalLib::mesg('====');

			$orderIncId = $mergedB2cOrder->getIncrementId();

			//echo 'order inc id = ' . $orderIncId . "\n";
			//continue;

			if ($rc) {

				$b2cOrder = $this->objectManager->create('\Magento\Sales\Model\Order')->loadByIncrementId( $orderIncId );

				//echo 'created at = ' . $b2cOrder->getCreatedAt() . "\n";
				//generalLib::mesg('customer email = ' . $b2cOrder->getCustomerEmail() );


				$shippingAddr = $b2cOrder->getShippingAddress();

				$shippingAddressId = $b2cOrder->getShippingAddress()->getId();

				$shippingAddress = $this->objectManager->create('\Magento\Sales\Model\Order\AddressRepository')->get( $shippingAddressId );

				generalLib::mesg('shipping address: ' . print_r($shippingAddress->getData(), true) );

				$shippingAddrRec = new stdClass();

				$shippingAddrRec->FirstName = $this->maskField( $shippingAddress->getFirstname(), $this->procConfig->numFirstCharsUnmasked );

				$shippingAddrRec->LastName = $this->maskField( $shippingAddress->getLastname(), $this->procConfig->numFirstCharsUnmasked );

				$shippingAddrRec->City = $this->maskField( $shippingAddress->getCity(), $this->procConfig->numFirstCharsUnmasked );

				$shippingAddrRec->Region = $this->maskField( $shippingAddress->getRegion(), $this->procConfig->numFirstCharsUnmasked );

				$shippingAddrRec->Postcode = $this->maskField( $shippingAddress->getPostcode(), $this->procConfig->numFirstCharsUnmasked );

				$shippingAddrRec->Telephone = $this->maskField( $shippingAddress->getTelephone(), $this->procConfig->numFirstCharsUnmasked );

				//MAGENTO2 DOES NOT LET YOU CHANGE THIS - STATES INVALID FORMAT
				//$shippingAddrRec->Email = $this->maskField( $shippingAddress->getEmail(), $this->procConfig->numFirstCharsUnmasked );


				$updAttrs = magentoImport::processMagAttrs($shippingAddress, $shippingAddrRec, $this->procConfig->shipAddrMagAttrs);
								

				$oldStreet = $shippingAddress->getStreet();

				//print_r($oldStreet);

				$streetCnt = count($oldStreet);

				if ($streetCnt > 0 ) {

					$newStreet[0] = $this->maskField( $oldStreet[0], $this->procConfig->numFirstCharsUnmasked );

					if ($streetCnt > 1) {
						$newStreet[1] = $this->maskField( $oldStreet[1], $this->procConfig->numFirstCharsUnmasked );
					}

					generalLib::mesg('Setting street to' . print_r($newStreet, true) );

					$shippingAddress->setStreet( $newStreet );
				}

				//print_r($shippingAddress->getData());

				try {
					generalLib::mesg('saving shipping address order id \'' . $orderIncId . '\'');
					$shippingAddress->save();
				}
						
				catch(Exception $e) {
					generalLib::mesg($e->getMessage());
					$rc = false;
				}

				if ($rc) {

					$b2cOrder = $this->objectManager->create('\Magento\Sales\Model\Order')->loadByIncrementId( $orderIncId );
					
					$orderRec = new stdClass();

					//generalLib::mesg('customer email = ' . $b2cOrder->getCustomerEmail() );

					//$orderRec->CustomerEmail = $this->maskField( $b2cOrder->getCustomerEmail(), $this->procConfig->numFirstCharsUnmasked );

					$orderRec->OrderExportStatus = $b2cOrder->getOrderExportStatus();

					if (strpos($orderRec->OrderExportStatus, '_gdpr') === false) {
						$orderRec->OrderExportStatus .= '_gdpr';
					}

					$updAttrs = magentoImport::processMagAttrs($b2cOrder, $orderRec, $this->procConfig->orderMagAttrs);

					if ( $updAttrs ) {

						try {
							generalLib::mesg('saving Mageno order id \'' . $orderIncId . '\'');
							$b2cOrder->save();
						}
								
						catch(Exception $e) {
							generalLib::mesg($e->getMessage());
							$rc = false;
						}
					}

				}

				//die();

			}
		}
	}


	//###########################
	public function maskField( $str, $numUnMasked ) {

		$strLen = strlen( $str );

		//echo $str . ' len1 = ' . $strLen . "\n";

		if ($strLen > $numUnMasked) {
			$maskedStr = substr($str, 0, $numUnMasked) . str_repeat('*', ( $strLen - $numUnMasked ) );
		} else {
			$maskedStr = $str;
		}

		//echo $maskedStr . ' len2 = ' . strlen($maskedStr) . "\n";

		return $maskedStr;
	}


}

?>
