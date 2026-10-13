<?php
class magXlsxProcessor {
	public $procConfig;
	public $objectManager;
	public $registry;
	public $state;
	public $downloadedXlsx;


	//###########################	
	public function __construct($objectManager = false) {
		global $argv;

		$this->objectManager = $objectManager;

		if ( $this->objectManager ) {

			$registry = $this->objectManager->create('Magento\Framework\Registry');

			$registry->register('isSecureArea', true);

			$state = $this->objectManager->get('Magento\Framework\App\State');

			$state->setAreaCode('adminhtml');
			//$state->setAreaCode('admin');

			$this->scopeConfig = $this->objectManager->create('Magento\Framework\App\Config\ScopeConfigInterface');
		}


		$this->procConfig = new stdClass();
		
		$progConfigObj = new progConfig();

		$this->procConfig = $progConfigObj->progConfig;

		$this->procConfig->action = isset($argv[3]) ? $argv[3] : 'ALL';

		$this->procConfig->feedType = isset($argv[4]) ? $argv[4] : 'ALL';

		$this->procConfig->fileName = isset($argv[5]) ? $argv[5] : 'ALL';

		
		generalLib::setLineBrk("\n");
			
		if (!is_dir($this->procConfig->localLogDir)) {
			if (!mkdir($this->procConfig->localLogDir)) {
				generalLib::term(1, 'Cannot create directory ' . $this->procConfig->localLogDir);
			}
		}

		$logFilename = $this->procConfig->localLogDir . '/mag_' . $this->procConfig->feedType . '-' . date('Ymd_His', time());
		//$logFilename = $this->procConfig->localLogDir . '/mag_' . $this->procConfig->feedType . '-' . date('Ymd_His', magentoGeneral::getLocalTimeStamp());

		generalLib::setGeneralLogFile($logFilename . '-general.log', false);
		generalLib::setErrorLogFile($logFilename . '-error.log', true, false);
		generalLib::setWarnLogFile($logFilename  . '-warn.log', true, false);
		
		/*
		if ( isset($this->procConfig->errorMailSubject) && isset($this->procConfig->errorMailFrom) && isset($this->procConfig->errorMailTo) ) {
			generalLib::setErrorLogEmailDetails($this->procConfig->errorMailSubject, $this->procConfig->errorMailFrom, $this->procConfig->errorMailTo);

		}

		if ( isset($this->procConfig->warnMailSubject) && isset($this->procConfig->warnMailFrom) && isset($this->procConfig->warnMailTo) ) {
			generalLib::setWarnLogEmailDetails($this->procConfig->warnMailSubject, $this->procConfig->warnMailFrom, $this->procConfig->warnMailTo);
		}
		*/

		$this->dloadFeedConfig();

		$this->dloadConfig();

		if ($this->procConfig->brandsActive) {

			if ( ( $rootCatPath = magentoCat::setCatPath('root', $this->procConfig->rootCatID) ) != false) {
				$this->procConfig->rootStoreCatId = $this->procConfig->rootCatID;
				$this->procConfig->rootStoreCatPath = $rootCatPath;
			}

			if ( ( $brandsCatPath = magentoCat::setCatPath('brands', $this->procConfig->brandsBaseCatId) ) != false) {
				$this->procConfig->brandsBaseCatId = $this->procConfig->brandsBaseCatId;
				$this->procConfig->brandsCatPath = $brandsCatPath;
			}

			if ( ( $brandsTmpCatPath = magentoCat::setCatPath('brands temp', $this->procConfig->brandsTmpCatId) ) != false) {
				$this->procConfig->brandsTmpCatId = $this->procConfig->brandsTmpCatId;
				$this->procConfig->brandsTmpCatPath = $brandsTmpCatPath;
			}

			magentoCatBrands::setBrandVars($this->procConfig->rootStoreCatPath, $this->procConfig->brandsCatPath, $this->procConfig->brandsTmpCatPath, $this->procConfig->brandsProdAttr, false);
		}

		if ( !$this->objectManager ) {

			$this->procConfig->prodEntityTypeId = Mage::getModel('eav/entity')->setType('catalog_product')->getTypeId();

		} else {

			$this->procConfig->prodEntityTypeId = $this->objectManager->create('\Magento\Eav\Model\Entity\Type')->loadByCode('catalog_product')->getId();

		}

		
		if ( $this->objectManager ) {
			
			magentoProd::prodConfig($this);

			magentoImport::importConfig($this);

			magentoGroupProducts::grpProdConfig($this);
		}

		$this->procConfig->storeDets = magentoImport::getStoreDets();


		$this->procConfig->storeCodeDets = magentoImport::getStoreCodeDets();

		//print_r($this->procConfig->storeDets);

		$this->procConfig->websiteCodeDets = magentoImport::getWebSiteCodeDets();
		//print_r($this->procConfig->websiteCodeDets);
		//die();

		$this->procConfig->storeCodeDets = magentoImport::getStoreCodeDets();

		$this->procConfig->dataTypes = array('DA' => 'Date', 'DD' => 'Drop Down', 'MD' => 'Multi-Select', 'PR' => 'Price', 'TA' => 'Text Area', 'TX' => 'Text', 'YN' => 'Yes/No');

		$this->procConfig->dataTypeAttrMappings = array('DA' => 'DA', 'DD' => 'DR', 'MD' => 'MD', 'PR' => 'PR', 'TA' => 'TA', 'TX' => 'TX', 'YN' => 'BO');

		$this->procConfig->customAttrTypeMappings = array('DA' => 'D', 'PR' => 'T', 'TA' => 'T', 'TX' => 'T', 'YN' => 'B');

		$this->procConfig->dataTypeCustomOptMappings = array('DA' => 'date', 'DD' => 'drop_down', 'MD' => 'multiple', 'TX' => 'field', 'TA' => 'area');


		$this->procConfig->standardProdColumns = array('sku', 'store', 'attribute_set', 'product_type', 'parent_sku', 'product_name', 'description', 'short_description', 
														'meta_title', 'meta_description', 'manufacturer', 'weight', 'taxable', 'manage_stock', 'back_orders', 'price', 
														'special_price', 'special_from_date', 'special_to_date', 'action_type', 'enabled');

		$this->procConfig->prodNameMagAttr = array('product_name' => 'name');

		$this->procConfig->prodMagAttrs = array('sku' => 'sku', 'short_description' => 'short_description', 'description' => 'description', 'meta_title' => 'meta_title', 'meta_description' => 'meta_description',
												'price' => 'price', 'weight' => 'weight');


		$this->procConfig->specPriceMagAttr = array('special_price' => 'special_price');


		$this->procConfig->standardCustColumns = array('website', 'external_customer_id', 'group', 'prefix', 'first_name', 'middle_name',
														'last_name', 'suffix', 'email_address', 'date_of_birth', 'gender');

		$this->procConfig->custMagAttrs = array('external_customer_id' => 'cust_ext_id', 'prefix' => 'prefix', 'first_name' => 'firstname',
													'middle_name' => 'middlename', 'last_name' => 'lastname', 'suffix' => 'suffix', 
													'email_address' => 'email', 'date_of_birth' => 'dob');


		$this->procConfig->standardCustAddrColumns = array('external_customer_id', 'external_customer_address_id', 'prefix', 'first_name', 'middle_name', 'last_name', 'suffix', 
														'company', 'street_address_1', 'street_address_2', 'city', 'state_province', 'zip_postal_code', 'country_code', 'telephone', 
														'fax', 'vat_number', 'default_billing_address', 'default_shipping_address');

		$this->procConfig->custAddrMagAttrs = array('external_customer_id' => 'cust_ext_id', 'external_customer_address_id' => 'cust_ext_addr_id', 'prefix' => 'prefix', 
													'first_name' => 'firstname', 'middle_name' => 'middlename', 'last_name' => 'lastname', 'suffix' => 'suffix', 
													'company' => 'company', 'street_address_1' => 'street_1', 'street_address_2' => 'street_2', 'city' => 'city', 
													'state_province' => 'region', 'zip_postal_code' => 'postcode', 'country_code' => 'country_id', 'telephone' => 'telephone', 
													'fax' => 'fax', 'vat_number' => 'vat_id');


		$this->procConfig->standardCustOptColumns = array('sku', 'row_price_type', 'option_required', 'option_type', 'row_max_characters', 'option_sort_order',
															'row_title', 'row_price', 'row_sku', 'row_sort_order');

		magentoAttrImport::attrConfig( $this );

		$this->procConfig->attrSets = magentoAttrImport::getAttrSets();

		//$this->procConfig->currDateTStamp = strtotime( date('d-m-Y', time() ) );

		magentoImgImport::setProcObj( $this );

		magentoImgImport::setImageConfig($this->procConfig->imageBaseDir . '/' . $this->procConfig->mainImagesDir, 
							$this->procConfig->imageMagRelDir, false);

		if ($this->objectManager) {

			$this->procConfig->mageVisibleBoth =  Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH;
			$this->procConfig->mageNotVisible = Magento\Catalog\Model\Product\Visibility::VISIBILITY_NOT_VISIBLE;

			$this->procConfig->mageEnabled = \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED;

			$this->procConfig->mageDisabled = \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_DISABLED;

			magentoCustomOpts::customOptsConfig( $this );

		} else {

			$this->procConfig->mageVisibleBoth = Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH;

			$this->procConfig->mageNotVisible = Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE;

			$this->procConfig->mageEnabled = Mage_Catalog_Model_Product_Status::STATUS_ENABLED;

			$this->procConfig->mageDisabled = Mage_Catalog_Model_Product_Status::STATUS_DISABLED;

		}

		$this->procConfig->gotCustOptSku = false;
	}


	//###########################
	private function dloadFeedConfig() {
		$dloadConfig = new stdClass();

		$dloadConfig->localXlsxInboxDir = $this->procConfig->localXlsxInboxDir;

		if (empty($dloadConfig->localXlsxInboxDir)) {
			generalLib::term(1,'Local Server XLSX Inbox Dir must be set in the Magento XLSX configuration');
		}


		$dloadConfig->localXlsxInboxProcDir = $this->procConfig->localXlsxInboxProcDir;

		if (empty($dloadConfig->localXlsxInboxProcDir)) {
			generalLib::term(1,'Local Server XLSX Inbox Processed Dir must be set in the Magento XLSX configuration');
		}


		if ($this->procConfig->environment == 'TST' || $this->procConfig->environment == 'UAT') {
			$dloadConfig->setDownloadedLocalFilesToProcessed = false;
		} else {
			$dloadConfig->setDownloadedLocalFilesToProcessed = true;
		}

		downloadFileLib::setDloadConfig($dloadConfig);

		downloadFileLib::setLocalProcessedDirs( array('xlsx'), array('Inbox') );
	}


	//###########################
	public function dloadConfig() {
		$this->procConfig->downloadDets = array();

		$dloadObj = new stdClass();
		$dloadObj->type = 'xlsx';
		$dloadObj->suffix = 'xlsx';
		//$dloadObj->remoteOutBoxDir = downloadFileLib::getDloadConfigVar('remoteCsvOutboxDir');
		$dloadObj->localInBoxDir = downloadFileLib::getDloadConfigVar('localXlsxInboxDir');

		$this->procConfig->downloadDets[] = $dloadObj;
	}


	//###########################
	public function setDownloadDets() {
		return $this->procConfig->downloadDets;
	}


	//###########################
	public function runProcessor() {

		$gotAction = false;

		//print_r($this->procConfig);

		if ($this->procConfig->action == 'IMPORT' || $this->procConfig->action == 'ALL') {

			$this->downloadAndProcessXlsxFiles( $this->procConfig->fileName );
			$gotAction = true;
		}

		if ($this->procConfig->action == 'EXPORT' || $this->procConfig->action == 'ALL') {

			$this->exportXlsxFile( $this->procConfig->feedType );
			$gotAction = true;
		}

		if (!$gotAction) {
			generalLib::error('No action(s) specified');
		}

	}


	//###########################	
	public function downloadAndProcessXlsxFiles($matchingFilenames) {
		
		$feedLkFile = '/tmp/' . $this->procConfig->feedType . '.lck';
				
		if (!generalLib::createProcLckFile($feedLkFile)) {		
			generalLib::warn('Cannot process ' . $this->procConfig->feedType . ' feed - ' . $this->procConfig->feedType . ' import already in progress');
			return;
		}

		if (method_exists('magXlsxCustomProcessor', 'setCustomConfig')) {
			magXlsxCustomProcessor::setCustomConfig($this, $this->procConfig->feedType);
		}
		
		$this->procConfig->downloadDets[0]->matchingFilenames = $matchingFilenames;

		//print_r($this->procConfig->downloadDets);

		downloadFileLib::processDownloadedFiles($this, $this->procConfig->downloadDets);
		
				
		generalLib::deleteProcLckFile($feedLkFile);	
		
		//if ($this->procConfig->environment == 'LIV') {				
			//magentoImport::reindexMagento($this->procConfig->idxList);
		//}
	}


	//###########################
	public function processDownloadedXlsxFile($fullDownloadedXlsxFilename, $csvFileFP) {
		$rc = true;

		$this->downloadedXlsx = new excelLib();


		if ( $this->downloadedXlsx->loadExcelSpreadSheet($fullDownloadedXlsxFilename) ) {

			$excelImportPathInfo = pathinfo($fullDownloadedXlsxFilename);

			if ( $this->procConfig->feedType == 'ALL') {

				$sheetNames = $this->downloadedXlsx->getExcelSheetNames();

				foreach($sheetNames as $sheetName) {
					$rc = $this->processXlsxSheet( $excelImportPathInfo, $sheetName );
				}

			} else {

				$rc = $this->processXlsxSheet( $excelImportPathInfo, $this->procConfig->feedType );

			}

		} else {
			$rc = false;
		}

		return $rc;
	}


	//###########################
	public function processXlsxSheet($excelImportPathInfo, $sheetName) {
		$rc = true;
		$this->procConfig->customAttrs = array();
		$this->procConfig->sheetHdrMapping = array();


		$processFunc = 'process' . str_replace(' ', '', $sheetName) . 'Sheet';
		echo 'processFunc = ' . $processFunc . "\n";
		
		if (method_exists($this, $processFunc)) {
			generalLib::mesg('====');

			generalLib::mesg('Processing ' . $sheetName . ' sheet for \'' . $excelImportPathInfo['basename'] . '\':-');

			if ( !$this->downloadedXlsx->getExcelSheet( $sheetName ) ) {
				$rc = false;
				return $rc;
			}

			if (method_exists('magXlsxCustomProcessor', 'setCustomConfig')) {
				magXlsxCustomProcessor::setCustomConfig($this, $sheetName);
			}

			//die();

			$rowNum = 1;

			//$outputFilename = $xlsxFileNameParts['dirname'] . '/upd_' . $xlsxFileNameParts['basename'];

			while ( $this->downloadedXlsx->gotExcelRows($rowNum) ) {
				
				$rowData = $this->downloadedXlsx->readExcelSheetRow($rowNum);
				//print_r($rowData);
				//die();

				generalLib::mesg('Processing row ' . $rowNum . ':');

				if ($rowNum == 1) {
					$this->processColTitleRow($sheetName, $rowData);
					
					//die();

					//print_r($this->procConfig->sheetHdrMapping);
					//print_r($this->procConfig->sheetHdrMapping);
					//print_r($this->procConfig->prodMagAttrs);
				} else {
					//print_r($rowData);
					//$model = substr($dataLine[0], 0, strpos($dataLine[0], '_')) ;
					//echo 'model = ' . $model . "\n";

					/*	
					if ($model != 'R02') {
						continue;
					}
					*/

					/*
					if ($rowNum != 3) {
						$rowNum++;
						continue;
					}
					*/



					$rowDataObj = $this->convArrToObj($rowData, $this->procConfig->sheetHdrMapping);

					//print_r($rowDataObj);
					$rc = $this->$processFunc($rowDataObj);

					/*
					if ($rc) {

						if ( isset($this->procConfig->actionTypeColRef) ) {
							$this->updActionType($this->procConfig->actionTypeColRef, $rowNum, $rowData[ $this->procConfig->actionTypeColNum ]);
						}

					}
					*/

					//die();
				}

				/*
				if ( isset($this->procConfig->actionTypeColRef) ) {

					if ( ( $rowNum % 10 ) == 0) {
						$this->downloadedXlsx->saveSpreadSheet($outputFilename);
					}

				}
				*/

				$rowNum++;

				/*
				if ($rowNum >= 4) {
					die();
				}
				*/
				//echo 'rowNum=' . $rowNum . "\n";
			}

			//ONLU WORKS FOR MAGENTO 1 AT PRESENT
			if (!$this->objectManager) {

				if ($sheetName == 'Products' || $sheetName == 'Inventory') {
					magentoConfigurableProducts::checkConfigProdsStockStatus();
				}

			}

		} else {
			generalLib::error('Cannot process. No ' . $processFunc . ' function for xlsx type ' . $sheetName);
			$rc = false;
		}
		
		return $rc;
	}


	//###########################
	private function processColTitleRow($feedType, $titleDataLine) {
		$rc = true;
		$customOptSort = 1; //CUSTOM OPTION SORT ORDER IS DETERMINED BY THE ORDER OF COLUMNS IN THE SPREADSHEET
		//print_r($titleDataLine);
		
		$numCols = count($titleDataLine);
		//echo 'numCols = ' . $numCols . "\n";

		switch($feedType) {
			case 'Customers':
						$standardCols = 'standardCustColumns';
						break;

			case 'Customer Addresses':
						$standardCols = 'standardCustAddrColumns';
						break;

			case 'Products':
						$standardCols = 'standardProdColumns';
						break;

			case 'Custom Options':
						$standardCols = 'standardCustOptColumns';
						break;
		}


		//echo 'numCols = ' . $numCols . "\n";

		for($i=0; $i < $numCols; $i++) {

			$dataLabel = str_replace( array(' ', '/', '(', ')'), array('_', '_', '', ''), $titleDataLine[$i]);

			if ( empty($dataLabel) ) {
				generalLib::error('Blank Label for column ' . $i . ' - Aborting');
				generalLib::term(1);
			}

			//echo 'label = ' . $dataLabel . "\n";

			if ($feedType == 'Products' || $feedType == 'Customers' || $feedType == 'Customer Addresses') {
				$gotCustomField = false;
				$gotCustomOpt = false;
				$gotConfigAttr = false;

				if ( ( $tildePos = strpos($dataLabel, '~') ) !== false) {
					$gotCustomField = true;

					if ( ($tildePos + 2) > strlen($dataLabel)) {
						$customAttrType = 'TX';

						$dataLabel = str_replace('~', '', $dataLabel);
					} else {
						$customAttrType = substr($dataLabel, $tildePos + 1, 2);

						//echo 'customProdAttrType = ' . $customProdAttrType . "\n";

						if ( !isset($this->procConfig->dataTypes[ $customAttrType ]) ) {
							generalLib::error('Undefined data type \'' . $customAttrType . '\' for column label \'' . $dataLabel . '\'');
						}

						$dataLabel = str_replace('~' . $customAttrType, '', $dataLabel);
					}
				}


				if ($feedType == 'Products') {

					if ( magentoImport::strEndsWith( $dataLabel, '-CO') ) {
						$gotCustomOpt = true;
						$dataLabel = preg_replace('/-CO$/', '', $dataLabel);
					}


					if (!$gotCustomOpt) {

						if ( magentoImport::strEndsWith( $dataLabel, '-C') ) {
							$gotConfigAttr = true;
							$dataLabel = preg_replace('/-C$/', '', $dataLabel);
						} 

					}

				}
				

				$dataLabel = strtolower($dataLabel);

				if (!in_array($dataLabel, $this->procConfig->$standardCols) ) {

					if (!$gotCustomField && !$gotConfigAttr && !$gotCustomOpt) {
						$gotCustomField = true;
						$customAttrType = 'TX';
					}
					
				} else {

					//SET THE MANUFACTURER AS BEING A DROP DOWN CUSTOM ATTRIBUTE, SO IT WILL BE PROCESSED CORRECTLY
					if ($dataLabel == 'manufacturer') {
						$gotCustomField = true;
						$customAttrType = 'DD';
					}
					
				}

				if ($gotCustomField || $gotConfigAttr || $gotCustomOpt) {
					$customAttr = new stdClass();

					if ($feedType == 'Products') {

						$customAttr->configProdAttr = $gotConfigAttr;
						$customAttr->isCustomOption = $gotCustomOpt;

						if ($customAttr->isCustomOption) {
							$customAttr->customOptTitle = ucwords( str_replace('_', ' ', $dataLabel) );
						}

					}

					if ($gotCustomField) {
						$customAttr->attrType = $customAttrType;
					}


					$this->procConfig->customAttrs[ $feedType ][ $dataLabel ] = $customAttr;
				}
			} else {
				
				$dataLabel = strtolower($dataLabel);

			}

			$this->procConfig->sheetHdrMapping[] = $dataLabel;

			if ($dataLabel == 'action_type') {
				$this->procConfig->actionTypeColRef = $this->getExcelColumnName($i);
				$this->procConfig->actionTypeColNum = $i;
			}

			if (!$rc) {
				break;
			}
		}

		
		//print_r($this->procConfig->customAttrs);
		//die();

		//print_r($this->procConfig->sheetHdrMapping);

		if ($feedType == 'Products') {
			$rc = $this->processCustomProdAttrs();
		}
		//die();

		if (!$rc) {
			generalLib::term($rc);

		}
	}


	//###########################
	private function processCustomProdAttrs() {
		$rc = true;
		$attrRec = new stdClass();

		//$attrRec->set_name = 'Default'; //DEFAULT ATTRIBUTE SET
		//$attrRec->grp_name = 'General';

		//print_r($this->procConfig->customAttrs);
		//die();

		foreach($this->procConfig->customAttrs['Products'] as $dataLabel => $customProdAttr) {

			if ($customProdAttr->isCustomOption) {
				continue;
			}

			//echo 'dataLabel = ' . $dataLabel . "\n";
			//print_r($customProdAttr);

			if ($this->objectManager) {

				$customProdAttr->prodAttrId = $this->objectManager->create('\Magento\Catalog\Model\ResourceModel\Eav\Attribute')->loadByCode($this->procConfig->prodEntityTypeId, $dataLabel)->getId();

			} else {

				$customProdAttr->prodAttrId = Mage::getModel('catalog/resource_eav_attribute')->loadByCode($this->procConfig->prodEntityTypeId, $dataLabel)->getId();
			}

			//echo 'prodAttrId = =' . $customProdAttr->prodAttrId . "\n";
			//echo 'dataLabel = =' . $dataLabel . "\n";
			//die();

			if (!$customProdAttr->prodAttrId) {
				$attrRec->attr_scope = 'G';
				$attrRec->attr_code = $dataLabel;
				$attrRec->attr_label = ucwords ( str_replace('_', ' ', $dataLabel) );

				$attrRec->attr_type = $this->procConfig->dataTypeAttrMappings[ $customProdAttr->attrType ];

				//print_r($attrRec);

				$retArr = magentoAttrImport::processAttribute('U', $attrRec);

				//print_r($retArr);
				$rc = $retArr['rc'];

				if ($rc) {
					$customProdAttr->prodAttrId = $retArr['attrId'];
				} else {
					break;
				}
			}

		}

		//die();
		return $rc;
	}


	//###########################
	private function getExcelColumnName($num) {
 		$numeric = ($num - 1) % 26;
		$letter = chr(65 + $numeric);
		$num2 = intval(($num - 1) / 26);

		if ($num2 > 0) {
			return getExcelColumnName($num2) . $letter;
		} else {
			return $letter;
		}
	}


	//###########################
	private function updActionType($cellRef, $rowNum, $actionType) {

		if ($actionType == 'U') {
			$updActionType = '-';
		} else {
			$updActionType = 'd';
		}

		$this->downloadedXlsx->updExcelCell($cellRef, $rowNum, $updActionType);
	}


	//###########################
	private function convArrToObj($arr, $arrMapping) {
		$csvObj = new stdClass();

		//print_r($arr);
		//print_r($arrMapping);

		foreach ($arr as $key => $val) {

			if (isset($arrMapping[$key])) {
				//echo 'key = ' . $key . "\n";
				//echo 'val = ' . $val . "\n";
				//echo 'value = ' . $arrMapping[$key] . "\n";

        		$csvObj->{ $arrMapping[$key] } = $val;
        	}

		}

		//print_r($csvObj);
		//die();

		return $csvObj;
	}
	

	//###########################
	private function processCustomersSheet($customerXslxRec) {
		$rc = true;

		generalLib::mesg('');
		generalLib::mesg('====');

		print_r($customerXslxRec);

		$customer = magentoCust::getMagCustomer($customerXslxRec, 'external_customer_id', 'cust_ext_id', 'mag_cust_id', false);

		$custExtId = $customerXslxRec->external_customer_id;

		if (!empty( $customerXslxRec->store ) ) {

			if ( isset( $this->procConfig->storeCodeDets[ $customerXslxRec->store ] ) ) {

				$storeId = $this->procConfig->storeCodeDets[ $customerXslxRec->store ]->storeId;

				$websiteId = $this->procConfig->storeCodeDets[ $customerXslxRec->store ]->websiteId;

			} else {
				generalLib::warn('Store code \'' . $customerXslxRec->store . '\' does not exist for external customer id \'' . $custExtId . '\' - skipping');
				$rc = false;
				return $rc;
			}

		} else {

			$websiteCode = 'base';

			$storeId = $this->procConfig->websiteCodeDets[ $websiteCode ]->defaultStoreId;

			$websiteId = $this->procConfig->websiteCodeDets[ $websiteCode ]->websiteId;

		}

		if ($actionType == 'D') {

			$rc = magentoCust::processCustDeletion($storeId, $custExtId, $customer);

			return $rc;
		}


		$updCust = false;
	
		if (!$customer) {
			$customer = Mage::getModel('customer/customer');
			$customer->setStore(Mage::getModel('core/store')->load( $storeId ));
			$customer->setWebsiteId( $websiteId );
			//$customer->setCreatedAt($custRec->Date_Created);
			
			generalLib::mesg('Creating customer \'' . $custExtId . '\' (' . $customerXslxRec->email_address . ') for store id \'' . $storeId . '\'');
		} else {			
			generalLib::mesg('Updating customer \'' . $custExtId . '\' (' . $customerXslxRec->email_address . ') for store id \'' . $storeId . '\'');
		}


		$customerGrp = Mage::getModel('customer/group')->load($customerXslxRec->group, 'customer_group_code');

		$group_id = $customerGrp->getCustomerGroupId();

		if (is_numeric($group_id)) {
			$customerXslxRec->group_id = $group_id;
		} else {
			$customerXslxRec->group_id = '';
		}

		$custGroupId = $customer->getGroupId();

		if ($custGroupId != $customerXslxRec->group_id) {

			if (!$updCust) {
				$updCust = true;
			}
						
			if ( is_numeric( $customerXslxRec->group_id ) ) {
				$customer->setGroupId( $customerXslxRec->group_id );
				generalLib::mesg('Setting group id to ' . $customerXslxRec->group_id . ' (' . $customerXslxRec->group . ')');
			} else {

				if ( !empty( $customerXslxRec->group ) ) {
					$customerGrp = Mage::getModel('customer/group');
					$customerGrp->setCustomerGroupCode($customerXslxRec->group);
					$customerGrp->setTaxClassId(3);					//RETAIL CUSTOMER
					$customerGrp->setAclGlobalRight($this->procConfig->defaultCustGrpAclRight);
					
					try {
						$customerGrp->save();
						generalLib::mesg('Adding customer group ' .  $customerXslxRec->group);
						
						$groupId = $customerGrp->getCustomerGroupId();

						generalLib::mesg('Setting group id to ' . $groupId . ' (' . $customerXslxRec->group . ')');

						$customer->setGroupId( $groupId );				
					} catch(Exception $e){
						generalLib::error($e->getMessage());		
					}
				}

			}			
		}

		
		$updAttrs = magentoImport::processMagAttrs($customer, $customerXslxRec, $this->procConfig->custMagAttrs);
					
		if ($updAttrs && (!$updCust)) {
			$updCust = true;
		}


		//PROCESS CUSTOM CUSTOMER ATTRIBUTES
		foreach($this->procConfig->customAttrs['Customers'] as $customCustAttrCode => $customCustAttr) {

			if ( !property_exists( (object) $customerXslxRec, $customCustAttrCode) ) {
				continue;
			}							

			$updCust = $this->processCustomAttr($customCustAttrCode, $customCustAttr, $customerXslxRec, 'customer_customer', $customer, $updCust);

			//echo 'attrType = ' . $customProdAttr->attrType . "\n";
		}
		

		if ($updCust) {
			try {
				generalLib::mesg('Saving customer');
				$customer->save();									
			} catch (Exception $e) {
				generalLib::error($e->getMessage());					
				$rc = false;
			}
		} else {
			generalLib::mesg('No changes to apply');
		}

		//die();

		return $rc;
	}


	//###########################
	private function processCustomerAddressesSheet($customerAddrXslxRec) {
		$rc = true;

		/*
		if ($customerAddrXslxRec->external_customer_address_id != '97439') {
				return;
		}
		*/

		generalLib::mesg('');
		generalLib::mesg('====');

		print_r($customerAddrXslxRec);

		$customer = magentoCust::getMagCustomer($customerAddrXslxRec, 'external_customer_id', 'cust_ext_id', 'mag_cust_id', true);

		if (!$customer) {
			$rc = false;
			return $rc;
		}


		$custAddrSettings = new stdClass();

		$defaultBilling = strtolower( $customerAddrXslxRec->default_billing_address );
		$defaultShipping = strtolower( $customerAddrXslxRec->default_shipping_address );

		if ($defaultBilling == 'y' || $defaultShipping == 'y') {
			$custAddrSettings->defaultAddr = true;
		} else {
			$custAddrSettings->defaultAddr = false;
		}

		if ($defaultBilling == 'y' && $defaultShipping == 'y') {

			$custAddrSettings->addrType = magentoCust::BILLING_AND_SHIPPING;

		} elseif ($defaultBilling == 'y') {

			$custAddrSettings->addrType = magentoCust::BILLING;

		} elseif ($defaultShipping == 'y') {

			$custAddrSettings->addrType = magentoCust::SHIPPING;

		} else {

			$custAddrSettings->addrType = magentoCust::SHIPPING;

		}

		$custAddrSettings->defaultCountryId = 'GB';
		$custAddrSettings->customAddrId = $customerAddrXslxRec->external_customer_address_id;
		$custAddrSettings->customAddrIdName = 'cust_ext_addr_id';

		print_r($custAddrSettings);

		//PROCESS CUSTOM CUSTOMER ATTRIBUTES
		foreach($this->procConfig->customAttrs['Customer Addresses'] as $customCustAttrCode => $customCustAttr) {

			if ( !property_exists( (object) $customerAddrXslxRec, $customCustAttrCode) ) {
				continue;
			}

			$this->procConfig->custAddrMagAttrs[ $customCustAttrCode ] = $customCustAttrCode;			

			//$updCust = $this->processCustomAttr($customCustAttrCode, $customCustAttr, $customerXslxRec, 'customer_customer', $customer, $updCust);

			//echo 'attrType = ' . $customProdAttr->attrType . "\n";
		}

		//print_r($customerAddrXslxRec);

		$rc = magentoCust::processCustomerAddressNew($actionType, $customer, $customerAddrXslxRec, $this->procConfig->custAddrMagAttrs, $custAddrSettings);

		//die();

		return $rc;
	}


	//###########################
	private function processProductsSheet($productXslxRec) {
		$rc = true;

		$productXslxRec->sku = trim($productXslxRec->sku);

		//if ( $productXslxRec->sku != '001ANT49') {
		//if ( $productXslxRec->sku != '001ANZ001') {
		//if ( $productXslxRec->parent_sku != 'ANITA-A') {
		//if ( $productXslxRec->sku != 'ES-13' && $productXslxRec->sku != 'ES13-320') {
		//if ( $productXslxRec->sku != 'ES13-320') {
		//if ( $productXslxRec->sku != 'L30/18-C') {
		//if ( $productXslxRec->sku != 'APRIL-A' ) {
		//if ( $productXslxRec->sku != 'A0181S' ) {
		//if ( ( strpos($productXslxRec->sku, 'LHPB') === false ) ) {
		//if ( $productXslxRec->sku != 'ISTDJ' && $productXslxRec->parent_sku != 'ISTDJ') {
			//echo 'skip sku = ' . $productXslxRec->sku . "\n";
			//return;
		//}

		generalLib::mesg('');
		generalLib::mesg('====');

		//print_r($productArr);
		//print_r($this->procConfig->sheetHdrMapping);
		//die();

		print_r($productXslxRec);
		//die();

		$product = magentoProd::getMagProduct($productXslxRec, 'sku', 'sku', 'magId', false);
	
		if ($product) {
			$productId = $product->getId();
			$productSku = $product->getSku();
		} else {
			$productId = '';
			$productSku = $productXslxRec->sku;
		}

		$updProd = false;

		if ( strtolower( $productXslxRec->product_type ) == 'simple') {

			$productXslxRec->product_type = 'S';
		}

		if (!$product) {
			generalLib::mesg('Creating product for sku : \'' . $productSku . '\'');


			if ($this->objectManager) {

				$product = $this->objectManager->create('Magento\Catalog\Model\Product');

			} else {

				$product = new Mage_Catalog_Model_Product();

			}

			if ( empty( $productXslxRec->attribute_set ) ) {
				
				$productXslxRec->attribute_set = 'Default';

			}

			if (isset ( $this->procConfig->attrSets[ $productXslxRec->attribute_set ] ) ) {

				$product->setAttributeSetId( $this->procConfig->attrSets[ $productXslxRec->attribute_set ] );
				generalLib::warn('Setting attribute set to \'' . $this->procConfig->attrSets[ $productXslxRec->attribute_set ] . '\' (' . $productXslxRec->attribute_set . ')');

			} else {

				generalLib::warn('Invalid attribute set \'' . $productXslxRec->attribute_set . '\' for SKU \'' . $productXslxRec->sku . '\' - skipping');
				$rc = false;

			}

			if ($rc) {

				switch($productXslxRec->product_type) {
					case 'C': 	
								$prodType = 'configurable';
								break;

					case 'G':	
								$prodType = 'grouped';
								break;

					case 'S':
								$prodType = 'simple';
								break;

					default:	generalLib::warn('Invalid product type \'' . $productXslxRec->product_type . '\' for SKU \'' . $productXslxRec->sku . '\' - skipping');
								$rc = false;
				}
			}

			if (!$rc) {
				return $rc;
			}

			$product->setTypeId($prodType);
			$product->setCreatedAt(strtotime('now'));

			if ($productXslxRec->product_type == 'C' || $productXslxRec->product_type == 'G') {

				$product->setStockData( array(
						'manage_stock' => 1,
						'use_config_manage_stock' => 1,
						'is_in_stock' => 1
						) );

			} else {

				if ($this->procConfig->environment == 'LIV') {
					$qty = 0;
					$isInStock = 0;
				} else {
					//FOR TESTING
					$qty = 10;
					$isInStock = 1;
				}

				$product->setStockData( array(
						'manage_stock' => 1,
						'use_config_manage_stock' => 1,
						'is_in_stock' => $isInStock,
						'qty' => $qty
						) );
			}
			
			$newProd = true;
		} else {
			generalLib::mesg('Updating product with SKU: ' . $productSku . ' product ID ' . $productId);
			$newProd = false;
		}


		//DEFINE CONFIGURABLE PRODUCT SELECTABLE ATTRIBUTES
		if ($productXslxRec->product_type == 'C') {

			if ($newProd) {
				$configProdAttrIds = array();
			}

			foreach($this->procConfig->customAttrs['Products'] as $customProdAttrCode => $customProdAttr) {

				if ($customProdAttr->isCustomOption) {
					continue;
				}

				if ( property_exists( (object) $productXslxRec, $customProdAttrCode) ) {
					$lcCustomProdAttrCode = strtolower($productXslxRec->$customProdAttrCode);

					if ($newProd) {
						
						if ( $lcCustomProdAttrCode == 'y' || $lcCustomProdAttrCode == 'y-p') {
							generalLib::mesg('Adding customisable attribute \'' . $customProdAttrCode . '\' to the configurable product');
							$configProdAttrIds[] = $customProdAttr->prodAttrId;
						}

					}

					if ( $lcCustomProdAttrCode == 'y-p' ) {
						$configProdPriceChangeAttrObj = new stdClass();
						$configProdPriceChangeAttrObj->attrId = $customProdAttr->prodAttrId;
						$configProdPriceChangeAttrObj->attrCode = $customProdAttrCode;

						$this->procConfig->configProdPriceChangeAttrs[ $productSku ][] = $configProdPriceChangeAttrObj;
					}
				}

			}

			//die();

			if ($newProd) {

				//print_r($configProdAttrIds);

				if ( count($configProdAttrIds) > 0) {
					$product->getTypeInstance()->setUsedProductAttributeIds( $configProdAttrIds );
					$cnfAttrData = $product->getTypeInstance()->getConfigurableAttributesAsArray();

					//print_r($cnfAttrData);

					$attrPos = 1;

					foreach($cnfAttrData as &$cnfAttr) {
						$cnfAttr['position'] = $attrPos;
						$attrPos++;
					}

					//print_r($cnfAttrData);

					//die();

					$product->setCanSaveConfigurableAttributes(true);
					$product->setConfigurableAttributesData( $cnfAttrData );
				} else {
					generalLib::error('No selectable attributes defined for configurable product \'' . $productXslxRec->sku . '\' - skipping');
					$rc = false;
					return $rc;
				}

			}
			//die();

			//print_r($this->procConfig->configProdPriceChangeAttrs);
			//die();
		}


		$oldVisibility = $product->getVisibility();

		if ( $productXslxRec->product_type == 'S' && ( !empty($productXslxRec->parent_sku) ) ) {
			//SET PRODUCTS LINKED TO A PARENT PRODUCT ( GROUPED OR CONFIGURABLE PRODUCT ) TO NOT BE VISIBLE

			if ($oldVisibility != $this->procConfig->mageNotVisible) {
				$product->setVisibility($this->procConfig->mageNotVisible);

				generalLib::mesg('Setting product to not be visible');
				$updProd = true;
			}

		} else {

			if ($oldVisibility != $this->procConfig->mageVisibleBoth) {
				$product->setVisibility($this->procConfig->mageVisibleBoth);

				generalLib::mesg('Setting product to be visible for catalog and search');
				$updProd = true;
			}

		}

		if (method_exists('magXlsxCustomProcessor', 'processCustomProductLogic')) {

			$rc = magXlsxCustomProcessor::processCustomProductLogic($product, $productXslxRec, $updProd);

			if (!$rc) {
				return $rc;
			}
        }

        //die();
		$prodAttrRec = new stdClass();

		//print_r($this->procConfig->customAttrs);
		//die();

		if ($rc) {
			$rc = $this->setCommonProductAttrs($product, $productXslxRec, $updProd, 'U');
		}

		/*
		if ( in_array('0', $productXslxRec->storeIds )) {

			$this->processStoreCustomProdAttrs($productXslxRec, $product, $updProd);
		}
		*/

		//PROCESS CUSTOM PRODUCT ATTRIBUTES
		/*
		foreach($this->procConfig->customAttrs['Products'] as $customProdAttrCode => $customProdAttr) {

			//echo 'customProdAttrCode = ' . $customProdAttrCode . "\n";
			//print_r($customProdAttr);

			//DO NOT SAVE ANY SELECTABLE ATTRIBUTES FOR A CONFIGURABLE PRODUCT WHERE THEY ARE USED, AS THEY WILL BE DEFINED FROM THE ASSOCIATED SIMPLE PRODUCTS INSTEAD
			if ( $productXslxRec->product_type == 'C') {

				if ( ( $customProdAttr->configProdAttr && ( strtolower($productXslxRec->$customProdAttrCode) == 'y' || strtolower($productXslxRec->$customProdAttrCode) == 'y-p') )) {
					continue;
				}
			}

			if ( !property_exists( (object) $productXslxRec, $customProdAttrCode) || $customProdAttr->isCustomOption) {
				continue;
			}							

			$updProd = $this->processCustomAttr($customProdAttrCode, $customProdAttr, $productXslxRec, 'catalog_product', $product, $updProd);

			//echo 'attrType = ' . $customProdAttr->attrType . "\n";
		}
		*/


		//die();

		//echo 'befcomm updProd = ' . $updProd . "\n";

		

		/*
		if ( $productXslxRec->gotUnsizedProduct ) {
			self::setMagImage($product, $productXslxRec, $updProd);
		}
		*/

		//echo 'updProd = ' . $updProd . "\n";
		//echo 'rc = ' . $rc . "\n";
		//print_r($product->getData());

		if ($updProd && $rc) {
			try {
				generalLib::mesg('saving product sku \'' . $productSku . '\'');
				$product->save();
			}
			
			catch(Exception $e) {
				generalLib::error($e->getMessage());
				generalLib::error('product sku \'' . $productSku . '\' not saved');
				$rc = false;
			}
		} else {

			if (!$updProd) {
				generalLib::mesg('No product changes to apply');
			}

		}

		$productId = $product->getEntityId();

		if ($productXslxRec->product_type == 'C') {
			magentoConfigurableProducts::addConfigProgToStockCheck($productId);
		}


		if ($newProd) {
			//MUST RELOAD A NEW PRODUCT IN ORDER TO ADD CUSTOM OPTIONS TO IT
			//$product = Mage::getModel('catalog/product')->load($productId);

			$productXslxRec->magId = $productId;

			$product = magentoProd::getMagProduct($productXslxRec, 'sku', 'sku', 'magId', false);
		}


		if ($rc) {

			if ( property_exists( (object) $productXslxRec, 'manage_stock') && property_exists( (object) $productXslxRec, 'back_orders') ) {

				$this->setStockFlags($product, $productXslxRec);
			}

		}


		if ($rc) {
			if (method_exists('magXlsxCustomProcessor', 'processCustomProductLogicPostSave')) {

				$rc = magXlsxCustomProcessor::processCustomProductLogicPostSave($product, $productXslxRec);

				//self::$numScopedProdData = self::$procObj->$postProcessFunc( $prodScopeRec );
	        }
	    }

	    if ($rc) {
	    		
			$productXslxRec->magId = $productId;

			$product = magentoProd::getMagProduct($productXslxRec, 'sku', 'sku', 'magId', false);

			//$product = Mage::getModel('catalog/product')->load($productId);

			$customOptIdx = 1;

	    	foreach($this->procConfig->customAttrs['Products'] as $customProdAttrCode => $customProdAttr) {

	    		if (!$customProdAttr->isCustomOption) {
					continue;
				}

				if (!empty($productXslxRec->$customProdAttrCode) ) {
					$str = '{' . $productXslxRec->$customProdAttrCode . '}';

					$customOptValObj = json_decode($str);

					//print_r($customOptValObj);

					if ( !is_null($customOptValObj) ) {
						$customOptionArr = array();
						$customOptionArr['title'] = $customProdAttr->customOptTitle;
						$customOptionArr['type'] = $this->procConfig->dataTypeCustomOptMappings[ $customProdAttr->attrType ];

						$customOptionArr['is_require'] = '0';
						$customOptionArr['sort_order'] = $customOptIdx;

						foreach($customOptValObj as $customValIdx => $customOptVal) {
							$customOptionValueArr = array();

							if (isset($customOptVal->title)) {
								$customOptionValueArr['title'] = $customOptVal->title;
							}

							if (isset($customOptVal->price)) {
								$customOptionValueArr['price'] = $customOptVal->price;
							} else {
								$customOptionValueArr['price'] = '0.00';
							}

							$customOptionValueArr['price_type'] = 'fixed';
							$customOptionValueArr['sku'] = '';
							$customOptionValueArr['sort_order'] = $customValIdx;

							//print_r($customOptionValueArr);

							$updProd = magentoCustomOpts::processCustomOption('U', $product, 'title', $customOptionArr, 'title', $customOptionValueArr);

							if ($updProd) {
								$rc = magentoCustomOpts::saveCustomOptProd($product, $product->getSku(), true);
							}
							
						}

						$customOptIdx++;
					}

		    		//print_r($customProdAttrCode);
		    		//print_r($customProdAttr);
		    		//print_r($productXslxRec->$customProdAttrCode);
				}
			}
	    }

		if ($rc && $productXslxRec->product_type == 'S' && ( !empty($productXslxRec->parent_sku) ) ) {

			$parentProduct = magentoProd::getMagProduct($productXslxRec, 'parent_sku', 'sku', 'parentMagId', true);

			if ($parentProduct) {
				$gotParentProduct = false;

				$parentProdSku = $parentProduct->getSku();

				if ( $parentProduct->getTypeId() == 'configurable' ) {
					$gotValidParentProduct = true;

	 				$rc = magentoConfigurableProducts::processConfigProductForSingleProduct( $parentProduct, 'sku', $productXslxRec->sku, 'mag_id', 
													array( $product->getId() ), 'U', false);

	 				if ($rc) {
	 					$rc = magentoConfigurableProducts::setCnfProdWebSitesFromAssocProds($this->procConfig->storeDets, $parentProdSku);
	 		 		}

		 		 	//print_r($this->procConfig->configProdPriceChangeAttrs);

		 			if ($rc && isset( $this->procConfig->configProdPriceChangeAttrs[ $parentProdSku ] ) ) {

						foreach($this->procConfig->configProdPriceChangeAttrs[ $parentProdSku ] as $configProdPriceChangeAttrObj) {

							//echo "got super prod attr\n";
							//print_r($this->procConfig->{ $configProdPriceChangeAttrObj->attrCode . 'DropDownOpts' });
							//echo 'attr code = ' . $configProdPriceChangeAttrObj->attrCode . "\n";
							//echo 'attr id = ' . $configProdPriceChangeAttrObj->attrId . "\n";
							//echo 'attr code value = ' . $productXslxRec->{ $configProdPriceChangeAttrObj->attrCode } . "\n";
							//echo 'attr value id = ' . $this->procConfig->{ $configProdPriceChangeAttrObj->attrCode . 'DropDownOpts'}[ strtolower( $productXslxRec->{ $configProdPriceChangeAttrObj->attrCode } ) ] . "\n";

		 					$rc = magentoConfigurableProducts::checkConfigSuperProdAttrPrice($parentProduct, $configProdPriceChangeAttrObj->attrId,
		 												$this->procConfig->{ $configProdPriceChangeAttrObj->attrCode . 'DropDownOpts'}[ strtolower( $productXslxRec->{ $configProdPriceChangeAttrObj->attrCode } ) ],
		 												'0', $product->getPrice(), true);

		 					if (!$rc) {
								break;
							}
			 			}
			 		}
			 	}

			 	if ( $parentProduct->getTypeId() == 'grouped' ) {
					$gotValidParentProduct = true;

					$rc = magentoGroupProducts::processGroupedProductForSingleProduct($parentProduct, 'sku', $productXslxRec->sku, 'mag_id', 
											array( $product->getId() ), 'U', false);

					if ($rc) {
						$rc = magentoGroupProducts::setGrpProdWebSitesFromAssocProds($this->procConfig->storeDets, $parentProdSku );
					}

				}

			 	if (!$gotValidParentProduct) {
			 		generalLib::error('Parent sku \'' . $parentProdSku . '\' is not a configurable or grouped product');
			 	}

			} else {
		 		generalLib::error('Cannot find parent product for parent sku \'' . $productXslxRec->parent_sku . '\' to process associated products');
		 	}

		}


		if ($this->procConfig->brandsActive) {

			if ($rc && $updProd) {
				if ($product) {

					if ($productId == '') {
						$product = magentoProd::getMagProduct($productXslxRec, 'sku', 'sku', 'magId', true);
					}

					if ($product) {
						$rc = magentoCatBrands::processProdBrandCategories($product->getId(), $product);
					}
				} else {
					generalLib::warn('Cannot find product sku\'' . $productSku . '\' to update brands'); 
				}
			} else {
				if (!$updProd) {
					generalLib::mesg('No manufacturer changes to apply');
				}
			}

		}


		//APPLY CUSTOM PRODUCT ATTRIBUTES TO EACH STORE CODE AS GIVEN IN THE 'Store' COLUMN
		if ( $rc ) {

			$productXslxRec->Magento_Product_ID = $productId;

			foreach($productXslxRec->storeIds as $storeId) {

				$storeProduct = magentoProd::getMagStoreProduct($productXslxRec, $storeId, 'sku', 'sku', 'Magento_Product_ID', true);

				if (!$storeProduct) {
					generalLib::error('Cannot find product for store id \'' . $magStoreId . '\' and product id \'' . $productId) . '\'';
					$rc = false;
					return $rc;
				}

				$this->processStoreCustomProdAttrs($productSku, $productXslxRec, $storeProduct, $storeId);
			}
		}

		//die();
		return $rc;
	}


	//###########################
	private function processStoreCustomProdAttrs($productSku, $productXslxRec, $product, $storeId) {

		$updProd = false;

		foreach($this->procConfig->customAttrs['Products'] as $customProdAttrCode => $customProdAttr) {

			//echo 'customProdAttrCode = ' . $customProdAttrCode . "\n";
			//print_r($customProdAttr);

			//DO NOT SAVE ANY SELECTABLE ATTRIBUTES FOR A CONFIGURABLE PRODUCT WHERE THEY ARE USED, AS THEY WILL BE DEFINED FROM THE ASSOCIATED SIMPLE PRODUCTS INSTEAD
			if ( $productXslxRec->product_type == 'C') {

				if ( ( $customProdAttr->configProdAttr && ( strtolower($productXslxRec->$customProdAttrCode) == 'y' || strtolower($productXslxRec->$customProdAttrCode) == 'y-p') )) {
					continue;
				}
			}

			if ( !property_exists( (object) $productXslxRec, $customProdAttrCode) || $customProdAttr->isCustomOption) {
				continue;
			}							

			$updProd = $this->processCustomAttr($customProdAttrCode, $customProdAttr, $productXslxRec, 'catalog_product', $product, $updProd);

			//echo 'attrType = ' . $customProdAttr->attrType . "\n";
		}

		if ($updProd) {

			try {
				generalLib::mesg('saving product sku \'' . $productSku . '\' for store id \'' . $storeId . '\' (' . $this->procConfig->storeDets[ $storeId ]->storeCode . ')' );
				$product->save();
			}
			
			catch(Exception $e) {
				generalLib::error($e->getMessage());
				generalLib::error('product sku \'' . $productSku . ' for store id \'' . $storeId . '\' (' . $this->procConfig->storeDets[ $storeId ]->storeCode . ') not saved');
				$rc = false;
			}
		} else {

			if (!$updProd) {
				generalLib::mesg('No product changes to apply');
			}

		}

	}


	//###########################
	private function processCustomAttr($customAttrCode, $customAttr, $xlsxRec, $mageModel, $mageRec, &$updRec) {

		//echo 'customAttrCode = ' . $customAttrCode . "\n";	
		//echo 'customAttrcode value = ' . $xlsxRec->$customAttrCode . "\n";
		//print_r($customAttr);

		//NOTE: DROP DOWN AND MULTI-SELECT OPTIONS ARE COMPARED IN LOWER CASE - NOT CASE SENSITIVE
		switch($customAttr->attrType) {
			case 'DD':
						if ( !isset($this->procConfig->{ $customAttrCode . 'DropDownOpts'}) ) {
							//$this->procConfig->{ $customAttrCode . 'DropDownOpts' } = magentoImport::setAttrOptions($mageModel, $customAttrCode, false);
							$this->procConfig->{ $customAttrCode . 'DropDownOpts' } = magentoImport::setAttrOptions($mageModel, $customAttrCode);

							//print_r( $this->procConfig->{ $customAttrCode . 'DropDownOpts' } );
						}

						//die();

						//$updAttr = magentoimport::chkDropdownAttr($mageRec, $customAttrCode, $xlsxRec->$customAttrCode, $this->procConfig->{ $customAttrCode . 'DropDownOpts' }, false);
						$updAttr = magentoimport::chkDropdownAttr($mageRec, $customAttrCode, $xlsxRec->$customAttrCode, $this->procConfig->{ $customAttrCode . 'DropDownOpts' });

						if ($updAttr) {
							$updRec = true;
						}

						break;
						
			case 'MD':
						if ( !isset($this->procConfig->{ $customAttrCode . 'MultiSelectOpts'}) ) {
							//$this->procConfig->{ $customAttrCode . 'MultiSelectOpts' } = magentoImport::setAttrOptions($mageModel, $customAttrCode, false);
							$this->procConfig->{ $customAttrCode . 'MultiSelectOpts' } = magentoImport::setAttrOptions($mageModel, $customAttrCode);


							//print_r($this->procConfig->{ $customAttrCode . 'MultiSelectOpts' });
						}

						$prodAttrValArr = explode(',', $xlsxRec->$customAttrCode);

						//echo "prodAttrValArr: \n";
						//print_r($prodAttrValArr);

						$newMdValueArr = array();

						foreach ($prodAttrValArr as $prodAttrVal) {
							//echo 'prodAttrVal = ' . $prodAttrVal . "\n";

							//magentoimport::chkMultiSelectAttr($mageRec, $customAttrCode, $prodAttrVal, $this->procConfig->{ $customAttrCode . 'MultiSelectOpts' }, $newMdValueArr, false);
							magentoimport::chkMultiSelectAttr($mageRec, $customAttrCode, $prodAttrVal, $this->procConfig->{ $customAttrCode . 'MultiSelectOpts' }, $newMdValueArr);
							//echo "After Upd:\n";
						}

						if ( magentoimport::chkMultiSelectAttrVals($mageRec, $customAttrCode, $newMdValueArr, $this->procConfig->{ $customAttrCode . 'MultiSelectOpts' }) ) {
							$updRec = true;
						}

						//die();
						break;

				default:	
						//echo "xlsxRec:\n";
						//print_r($xlsxRec);

						$updAttr = magentoImport::processCustomAttrs($this, $mageRec, $xlsxRec, 
								array($customAttrCode => $this->procConfig->customAttrTypeMappings[ $customAttr->attrType ]) );


						if ($updAttr) {
							$updRec = true;
						}

						break;
		}

		return $updRec;
	}

	//###########################
	private function setCommonProductAttrs($product, $productXslxRec, &$updProd, $actionType) {
		$rc = true;
		$productId = $product->getId();
		$productSku = $productXslxRec->sku;

		//echo 'productId = ' . $productId . "\n";

		//MAY USE PRODUCT TYPE AND ACTIONTYPE TO SELECTIVELY UPDATE THE CONFIGURABLE PRODUCT - MORE THOUGHT REQUIRED
		
		if ( property_exists( (object) $productXslxRec, 'product_name') ) {

			if (magentoImport::processMagAttrs($product, $productXslxRec, $this->procConfig->prodNameMagAttr)) {
				$oldUrlKey = $product->getUrlKey();
				$newUrlKey = $product->getName() . '-' . $productSku;

				if ($oldUrlKey != $newUrlKey) {
					$product->setUrlKey( $newUrlKey );
					generalLib::mesg('Re-creating URL key');

					$product->setData('url_key_create_redirect', false);
					generalLib::mesg('URL create redirect is disabled');
				}

				$updProd = true;
			}

		}


		//print_r($productXslxRec);

		if (!$rc) {
			generalLib::warn('Aborting import for sku \'' . $productSku . '\'');
			return $rc;
		}

		if ( ( !isset($productXslxRec->description) || empty($productXslxRec->description) ) && isset($productXslxRec->product_name) && !empty($productXslxRec->product_name) ) {
			$productXslxRec->description = $productXslxRec->product_name;
		}

		if (isset($productXslxRec->description)) {
			$productXslxRec->description = str_replace( array("\r\n", "\n", "\r"), '<br />', $productXslxRec->description );
		}	

		if ( ( !isset($productXslxRec->short_description) || empty($productXslxRec->short_description) ) && isset($productXslxRec->description) && !empty($productXslxRec->description) ) {
			$productXslxRec->short_description = $productXslxRec->description;
		}

		if (isset($productXslxRec->short_description)) {
			$productXslxRec->short_description = str_replace( array("\r\n", "\n", "\r"), '<br />', $productXslxRec->short_description );
		}	


		//$dropDownOpts = magentoImport::setAttrOptions('catalog_product', $prodAttrRec->attr_code);


		if ( ( !isset($productXslxRec->meta_title) || empty($productXslxRec->meta_title) ) && isset($productXslxRec->product_name) && !empty($productXslxRec->product_name) ) {
			$productXslxRec->meta_title = htmlspecialchars_decode($productXslxRec->product_name);
		}


		if ( ( !isset($productXslxRec->meta_description) || empty($productXslxRec->meta_description) ) && isset($productXslxRec->short_description) && !empty($productXslxRec->short_description) ) {
			$productXslxRec->meta_description = htmlspecialchars_decode($productXslxRec->short_description);
		}


		if ( isset($productXslxRec->meta_description) ) {
			if (strlen($productXslxRec->meta_description) > 255) {
				$productXslxRec->meta_description = substr($productXslxRec->meta_description, 0, 255);
			}
		}


		if ( $productXslxRec->product_type == 'S') {

			if ( property_exists( (object) $productXslxRec, 'weight') ) {

				if ( !is_numeric ( $productXslxRec->weight ) ) {
					$productXslxRec->weight = '0';
				}

			}

		} else {

			if ( property_exists( (object) $productXslxRec, 'weight') ) {

				if ( isset( $productXslxRec->weight ) ) {
					unset($productXslxRec->weight);
				}

			}
		}

		if ( property_exists( (object) $productXslxRec, 'price') ) {

			if ( ( !isset($productXslxRec->price) || empty($productXslxRec->price) ) && $product->getPrice() == '' ) {
				$productXslxRec->price = '0';
				generalLib::warn('Price is zero for product sku \'' . $productXslxRec->sku . '\'');
			}

		}

		if (magentoImport::processMagAttrs($product, $productXslxRec, $this->procConfig->prodMagAttrs)) {
			$updProd = true;
		}

		if ( property_exists( (object) $productXslxRec, 'taxable') ) {

			$oldTaxClassId = $product->getTaxClassId();	

			if ( ( strtolower($productXslxRec->taxable) ) == 'y') {
				$newTaxClassId = '2';	//TAXABLE GOODS
			} else {
				$newTaxClassId = '0';	//NONE
			}

			if ($oldTaxClassId != $newTaxClassId) {	
				$product->setTaxClassId( $newTaxClassId );

				generalLib::mesg('Setting tax class id to \'' . $newTaxClassId . '\' (' . $productXslxRec->taxable . ')');
				$updProd = true;
			}
		}

		$oldProdWebSiteIds = $product->getWebsiteIds();
		
		$newProdWebSiteIds = array();

		$productXslxRec->storeIds = array();

		if ( empty($productXslxRec->store) ) {
			$productXslxRec->store = $this->procConfig->storeDets[ '1' ]->storeCode;
		}

		/*
		COMMENTING OUT STORE ASSIGNMENT CODE AS CONTROLLED VIA EXCHANGE STORE DATA TABLE, WHICH INCLUDES MORE STORES THAN WHAT ARE REFERENCED IN THE 
		SPREADSHEET. ENABLING THIS CODE WOULD CAUSE PRODUCTS TO BE REMOVED FROM STORES STATED IN THE STORE DATA TABLE, BUT NOT IN THE SPREADSHEET
		*/
		if (isset( $productXslxRec->store ) ) {
			$storeCodes = explode(',', $productXslxRec->store);

			$storeIds = array();

			foreach($storeCodes as $storeCode) {

				$storeCode = trim($storeCode);

				if ( isset( $this->procConfig->storeCodeDets[ $storeCode ] ) ) {
					$productXslxRec->storeIds[] = $this->procConfig->storeCodeDets[ $storeCode ]->storeId;
				} else {
					generalLib::warn('No store id for store code \'' . $storeCode . '\'');
				}
			}

			//print_r($storeIds);

			/*
			foreach($productXslxRec->storeIds as $storeId) {
				
				//DO NOT BOTHER ADDING THE DEFAULT STORE AS A WEBSITE
				if ( $storeId != '0' ) {

					if ( isset( $this->procConfig->storeDets[ $storeId ] ) ) { 

						$websiteId = $this->procConfig->storeDets[ $storeId ]->websiteId;
				
						//echo 'websiteId = ' . $websiteId . "\n";

						if (!in_array($websiteId, $newProdWebSiteIds)) {
							$newProdWebSiteIds[] = $websiteId;				
						}
					}

				}

			}
			*/

		}

		/*
		if (!sort($oldProdWebSiteIds, SORT_NUMERIC)) {
			generalLib::error('Cannot sort old product sku \'' . $productSku . '\' website ids');
		}

		if (!sort($newProdWebSiteIds, SORT_NUMERIC)) {
			generalLib::error('Cannot sort new product sku \'' . $productSku . '\' website ids');
		}

		$addedWebsiteIds = array_diff($newProdWebSiteIds, $oldProdWebSiteIds);
		//echo 'addedWebsiteId:' . "\n";
		//print_r($addedWebsiteIds);
		
		$deletedWebsiteIds = array_diff($oldProdWebSiteIds, $newProdWebSiteIds);
		//echo 'deletedWebsiteIds:' . "\n";
		//print_r($deletedWebsiteIds);
		
		if ( (count($addedWebsiteIds) > 0) || (count($deletedWebsiteIds) > 0) ) {

			foreach($addedWebsiteIds as $addedWebsiteId) {
				generalLib::mesg('Adding website id ' . '\'' . $addedWebsiteId . '\' to product sku \'' . $productSku . '\'');
			}
			
			foreach($deletedWebsiteIds as $deletedWebsiteId) {
				generalLib::mesg('Deleting website id ' . '\'' . $deletedWebsiteId . '\' from group product \'' . $productSku . '\'');
			}
								
			$product->setWebsiteIds($newProdWebSiteIds);

			$updProd = true;
		}
		*/


		if ( property_exists( (object) $productXslxRec, 'enabled') ) {

			$oldStatus = $product->getStatus();

			if ($productXslxRec->enabled == '') {
				$productXslxRec->enabled = 'Y';
			}

			if ( strtolower($productXslxRec->enabled) == 'y') {

				if ($oldStatus != $this->procConfig->mageEnabled) {
					$product->setStatus( $this->procConfig->mageEnabled );

					generalLib::mesg('Setting product sku \'' . $productSku . '\' to enabled');
					$updProd = true;
				}

			} else {

				if ($oldStatus != $this->procConfig->mageDisabled ) {
					$product->setStatus( $this->procConfig->mageDisabled );

					generalLib::mesg('Setting product sku \'' . $productSku . '\' to disabled');
					$updProd = true;
				}

			}

		}

		if ( property_exists( (object) $productXslxRec, 'special_price') || property_exists( (object) $productXslxRec, 'discount_perc') ) {

			$updAttrs = $this->processSpecialPrice($product, 'sku', $productXslxRec);

			if ( $updAttrs ) {
				$updProd = true;
			}
		}

		return $rc;
	}


	//###########################
	private function processSpecialPricesSheet($spcPrcXslxRec) {
		$rc = true;

		generalLib::mesg('');
		generalLib::mesg('====');

		print_r($spcPrcXslxRec);

		$prodRef = key( (Array)$spcPrcXslxRec );			

		$product = magentoProd::getMagProduct($spcPrcXslxRec, $prodRef, $prodRef, 'magId', false);
	
		if ($product) {
			$updProd = $this->processSpecialPrice($product, $prodRef, $spcPrcXslxRec);
		} else {
			generalLib::error('Cannot find product for ' . $prodRef . ' \'' . $spcPrcXslxRec->$prodRef . '\' - skipping');
		}

		if ($updProd) {
			try {
				generalLib::mesg('saving product with ' . $prodRef . ' \'' . $spcPrcXslxRec->$prodRef . '\'');
				$product->save();
			}
			
			catch(Exception $e) {
				generalLib::error($e->getMessage());
				generalLib::error('product with ' . $prodRef . ' \'' . $spcPrcXslxRec->$prodRef . '\' not saved');
				$rc = false;
			}
		}

		//die();
		return $rc;
	}


	//###########################
	private function processSpecialPrice($product, $prodRef, $xlsxRec) {
		$applySpecPrice = true;
		$gotSpecialPrice = false;
		$gotDiscountPerc = false;


		$prodSpecialFromDate = $product->getSpecialFromDate();

		if ( !empty($prodSpecialFromDate) ) {
			$prodSpecialFromDateTs = strtotime( $prodSpecialFromDate );
			$prodSpecialFromDate = date('d-m-Y', strtotime( $prodSpecialFromDate ) );
		}


		$prodSpecialToDate = $product->getSpecialToDate();

		if ( !empty($prodSpecialToDate) ) {
			$prodSpecialToDate = date('d-m-Y', strtotime( $prodSpecialToDate ) );
		}


		if ( property_exists( (object) $xlsxRec, 'special_price') ) {

			if ($xlsxRec->special_price != '' && !is_numeric( $xlsxRec->special_price ) ) {

				generalLib::error('Special price \'' . $xlsxRec->special_price . '\' is not numeric for ' . $prodRef . ' \'' . $xlsxRec->$prodRef . '\' - skipping');

			} else {

				$gotSpecialPrice = true;

			}

		}

		
		if ( property_exists( (object) $xlsxRec, 'discount_perc') ) {

			if ($xlsxRec->discount_perc != '' && !is_numeric( $xlsxRec->discount_perc ) ) {

				generalLib::error('Discount \'' . $xlsxRec->discount_perc . '\' is not numeric for ' . $prodRef . ' \'' . $xlsxRec->$prodRef . '\' - skipping');
				$applySpecPrice = false;

			} elseif ($xlsxRec->discount_perc != '' && ( $xlsxRec->discount_perc < 0 || $xlsxRec->discount_perc > 100 ) ) {

				generalLib::error('Discount percentage not between 0 and 100 for ' . $prodRef . ' \'' . $xlsxRec->$prodRef . '\' - skipping');
				$applySpecPrice = false;

			} else {

				if ($xlsxRec->discount_perc != '') {
					$gotDiscountPerc = true;
				}

			}

		}


		if ( ( $gotSpecialPrice && $xlsxRec->special_price != '') && $gotDiscountPerc ) {

			generalLib::error('Both discount and special price supplied for ' . $prodRef . ' \'' . $xlsxRec->$prodRef . '\' - skipping');
			$applySpecPrice = false;

		} elseif ( ( !$gotSpecialPrice ) && ( !$gotDiscountPerc ) ) {

			generalLib::error('Discount and special price not supplied for ' . $prodRef . ' \'' . $xlsxRec->$prodRef . '\' - skipping');
			$applySpecPrice = false;

		}


		$gotFromDate = false;
		$fromDateOk = true;

		if ( !empty( $xlsxRec->special_from_date ) ) {
			$gotFromDate = true;
			$fromDateOk = generalDateFuncs::checkADate( $xlsxRec->special_from_date );

			if (!$fromDateOk) {
				$applySpecPrice = false;
			}
		} else {
			generalLib::error('Special price from date is empty for ' . $prodRef . ' \'' . $xlsxRec->$prodRef . '\' - skipping');
			$applySpecPrice = false;
		}


		$gotToDate = false;
		$toDateOk = true;

		if ( !empty( $xlsxRec->special_to_date ) ) {
			$gotToDate = true;
			$toDateOk = generalDateFuncs::checkADate( $xlsxRec->special_to_date );

			if ($toDateOk) {

				if ( !generalDateFuncs::checkDateRange( $xlsxRec->special_from_date, $xlsxRec->special_to_date, false) ) {
					generalLib::error('Special price end date \'' . $xlsxRec->special_to_date . '\' is before the special price from date \'' . $xlsxRec->special_from_date . '\' for ' . $prodRef . ' \'' . $xlsxRec->$prodRef . '\' - skipping');
					$applySpecPrice = false;
				}

				/*
				if ( !generalDateFuncs::checkDateRange( date('d-m-Y'), $xlsxRec->special_to_date, false) ) {
					generalLib::error('Special price end date \'' . $xlsxRec->special_to_date . '\' has expired for ' . $prodRef . ' \'' . $xlsxRec->$prodRef . '\' - skipping');
					$applySpecPrice = false;
				}
				*/

			} else {
				$applySpecPrice = false;
			}

		} else {
			generalLib::error('Special price to date is empty for ' . $prodRef . ' \'' . $xlsxRec->$prodRef . '\' - skipping');
			$applySpecPrice = false;
		}


		if ($applySpecPrice) {
			if ($gotDiscountPerc) {

				$prodPrice = $product->getPrice();

				$newSpecialPrice = $prodPrice * ( ( 100 - $xlsxRec->discount_perc ) / 100 );

				$newSpecialPrice = round($newSpecialPrice, 2);

				$xlsxRec->special_price = $newSpecialPrice;
			} else {

				if ( $xlsxRec->special_price == '0' ) {
					$xlsxRec->special_price = '';
				}

				if ( $xlsxRec->special_price != '' ) {

					if ( $xlsxRec->special_price >= $product->getPrice() ) {
						generalLib::error('Special price \'' . $xlsxRec->special_price . '\' is greater than the standard price \'' . $product->getPrice() . '\' for ' . $prodRef . ' \'' . $xlsxRec->$prodRef . '\' - skipping');
						$applySpecPrice = false;
					}

				} else {
					 $xlsxRec->special_from_date = '';
					 $xlsxRec->special_to_date = '';
				}
			}


			$updAttr = magentoImport::processMagAttrs($product, $xlsxRec, $this->procConfig->specPriceMagAttr);

			if ($updAttr) {

				if ($gotDiscountPerc) {
					generalLib::mesg($xlsxRec->discount_perc . '% discounted price on \'' . $prodPrice . '\'');
				}

				$updProd = true;
			}

			if ($xlsxRec->special_from_date != $prodSpecialFromDate) {
				generalLib::mesg('Setting special from date to \'' . $xlsxRec->special_from_date . '\'');

				if ($xlsxRec->special_from_date != '') {
					$ts = strtotime( $xlsxRec->special_from_date );
					$xlsxRec->special_from_date = date('Y-m-d', $ts);
				}
	
				$product->setSpecialFromDate( $xlsxRec->special_from_date );	

				if ($xlsxRec->special_from_date != '') {
					$product->setSpecialFromDateIsFormated(true);
				} else {
					$product->setSpecialFromDateIsFormated(false);
				}

				$updProd = true;
			}

			if ($xlsxRec->special_to_date != $prodSpecialToDate) {
				generalLib::mesg('Setting special to date to \'' . $xlsxRec->special_to_date . '\'');

				if ($xlsxRec->special_to_date != '') {
					$ts = strtotime( $xlsxRec->special_to_date );
					$xlsxRec->special_to_date = date('Y-m-d', $ts);
				}

				$product->setSpecialToDate( $xlsxRec->special_to_date );

				if ($xlsxRec->special_to_date != '') {
					$product->setSpecialToDateIsFormated(true);
				} else {
					$product->setSpecialToDateIsFormated(false);
				}

				$updProd = true;
			}

		}

		/*
		//THIS CODE WAS PROVDING A DEFAULT FROM DATE OF TODAY - KEPT FOR NOW
		if ($applySpecPrice) {

			if ($applySpecPrice && $fromDateOk) {

				if (!$gotFromDate) {				
					//A BLANK START DATE RESULTS IN MAGENTO RESETTING IT TODAY'S DATE, NEED TO ALLOW FOR THIS OTHERWISE YOU WILL KEEPT RE-PROCESSING THE SAME
					//BLANK DATE

					if ( ( !empty($prodSpecialFromDate) ) && ( !generalDateFuncs::checkDateRange( $prodSpecialFromDate, date('d-m-Y'), false) ) )  {

						$xlsxRec->special_from_date = date('d-m-Y');

					} else {

						if (!empty($prodSpecialFromDate)) {
							$xlsxRec->special_from_date = $prodSpecialFromDate;
						} else {
							$xlsxRec->special_from_date = date('d-m-Y');
						}

					}

				}

			}
		}
		*/

		return $updProd;
	}


	//###########################
	private function processCategoriesSheet($catXslxRec) {
		$rc = true;

		/*
		//if ( $catXslxRec->sku != 'LHPB' ) {
		if ( ( strpos($catXslxRec->sku, 'LHPB') === false ) ) {
			//echo 'skip sku = ' . $productXslxRec->sku . "\n";
			return;
		}
		*/

		generalLib::mesg('');
		generalLib::mesg('====');

		print_r($catXslxRec);

		$product = magentoProd::getMagProduct($catXslxRec, 'sku', 'sku', 'magId', false);
		
		if ($product) {
			$productId = $product->getId();
			$productSku = $product->getSku();
		} else {
			generalLib::warn('Product sku \'' . $catXslxRec->sku . '\' not found - skipping category');
			return $rc;
		}


		$gotCatColumn = true;
	    $numCatCols = 0;
	    $catPathNum = 1;

	    while ($gotCatColumn) {

	    	if (property_exists( (object) $catXslxRec , 'category_path_' . $catPathNum)) {
	    		$numCatCols++;
	    		$catPathNum++;
	    	} else {
	    		$gotCatColumn = false;
	    	}

	    }

	    //echo 'numCatCols = ' . $numCatCols ."\n";	    
		//die();

		$newCatIds = array();

		//echo 'categoryNames = ' . $categoryNames . "\n";
	    //echo 'parent Cat = ' . $parentCatId . "\n";
	    //print_r($categoryNamesArr);

	    if ($this->procConfig->gotTopLevelCat) {
	    	$topLevel = true;
	    } else {
	    	$topLevel = false;
	    }

	    for($catPathNum = 1; $catPathNum <= $numCatCols; $catPathNum++) {

	    	if (!isset( $catXslxRec->{ 'category_path_' . $catPathNum } )) {
	    		continue;
	    	}

	    	$categoryNamesArr = explode('>', $catXslxRec->{ 'category_path_' . $catPathNum });

	    	//echo "cat names:\n";
	    	//print_r($categoryNamesArr);

	    	$parentCatId = $this->procConfig->rootCatID;

		    foreach($categoryNamesArr AS $categoryName) {
		    	//echo 'categoryName = ' . $categoryName . "\n";
		    	$categoryName = trim($categoryName);
		    	
		    	if ( $this->objectManager ) {

		    		$_parentCategory = $this->objectManager->create('\Magento\Catalog\Model\Category')->load($parentCatId);

		    	} else {

		    		$_parentCategory = Mage::getModel('catalog/category')->load($parentCatId);

		    	}

		    	

				//echo 'parentPath = ' . $_parentCategory->getPath() . "\n";
				//echo 'topLevel = ' . $topLevel . "\n";

			//die();
		    	if ($topLevel) {
		    		$topLevel = false;

		    		if ( $this->objectManager ) {

		    			$_parentCategory = $this->objectManager->create('\Magento\Catalog\Model\Category')->load($parentCatId);

		    		} else {

		    			$_parentCategory = Mage::getModel('catalog/category')->load($parentCatId);

		    		}
		    			
		    		if ($_parentCategory->getName() == $categoryName) {
		    		 	$newCatIds[] = $_parentCategory->getId();
		    		 	continue;
		    		 } else {
		    		 	break;
		    		 }
		    	}

		        $categoryExists = false;

		        $_childCategory = $_parentCategory->getChildren();
		        
		        //echo "child category:\n";
		        //print_r($_childCategory);

		        foreach( explode(',',$_childCategory) as $_childCategory_id ) {

		        	if ( $this->objectManager ) {

		            	$_category = $this->objectManager->create('Magento\Catalog\Model\Category')->load($_childCategory_id);
		            
		            } else {

		            	$_category = Mage::getModel('catalog/category')->load($_childCategory_id);

		            }

		            //echo 'mag catname  = ' . $_category->getName() . "\n";
		            //echo 'catname  = ' . $categoryName . "\n";

		            if ($_category->getName() === $categoryName) {
		            	//echo 'match mag catname  = ' . $_category->getName() . "\n";
		            	//echo 'match parent cat id = ' . $_category->getId() . "\n";

		                $categoryExists = true;
		                $parentCatId = $_category->getId();
		                $newCatIds[] = $parentCatId;
		                break;
		            }
		        }
		        
			//echo 'categoryExists = ' . $categoryExists . "\n";
			//echo 'parentCatId = ' . $parentCatId . "\n";
			//print_r($_parentCategory->getData());

		        if (!$categoryExists) {

		        	if ( $this->objectManager ) {

		        		$_catChild = $this->objectManager->create('Magento\Catalog\Model\Category');

			        } else {

			        	$_catChild = new Mage_Catalog_Model_Category();

					}

		        	$_catChild->setName( $categoryName );
		            $_catChild->setIncludeInMenu(1);
		            $_catChild->setIsActive(1);
					$_catChild->setIsAnchor(1);
		           //$_catChild->setAvailableSortBy('position');
		            $_catChild->setDefaultSortBy('position');
		            //$_catChild->setPageLayout('two_columns_left');
		            $_catChild->setPath($_parentCategory->getPath());

					//print_r($_parentCategory->getData());

		            //echo 'parent cat path = ' . $_parentCategory->getPath() . "\n";
		        
		            try {
		                generalLib::mesg('Creating category : ' . $categoryName);
		                $_catChild->save();
		                $parentCatId = $_catChild->getId();
		            } catch(Exception $e){
		            	generalLib::error('Cannot create category : ' . $categoryName);
		                generalLib::error( $e->getMessage() );
		                $rc = false;
		            }

		            if ($rc) {
		            	$newCatIds[] = $_catChild->getId();
		            }
		        }

		        if (!$rc) {
		        	break;
		        }

		    }

		    if (!$rc) {
	        	break;
	        }

		}
	   
	   	if ($rc) {
	   		$oldCatIds = $product->getCategoryIds();
	   		$magCatIds = array();

	   		if ($this->procConfig->brandsActive) {
	   			$brandCatIds = magentoCatBrands::getBrandCatIds($product);

				//echo 'brandCatIds = ' . print_r($brandCatIds, true) . "\n";

				
				
				//echo 'oldCategoryIds = ' . print_r($oldCategoryIds, true) . "\n";
				//PRESERVE ALL PREVIOUSLY SAVED BRAND CATEGORY IDS
				$magCatIds = array_intersect($brandCatIds, $oldCatIds);
			}

			$updCat = false;

	   		foreach($newCatIds as $newCatId) {

				if (!$updCat) {

					if (!in_array($newCatId, $oldCatIds)) {
						$updCat = true;
					}

				}

				$magCatIds[] = $newCatId;
			}

			foreach($oldCatIds as $oldCatId) {

				if (!in_array($oldCatId, $magCatIds)) {
					$updCat = true;
				}

			}

			//echo "magCatIds:\n";

			//print_r($magCatIds);

	   		if ($updCat) {
	   			$product->setCategoryIds( $magCatIds );
	   			generalLib::mesg('Setting category ids to \'' . implode(',', $magCatIds) . '\'');

				try {
					generalLib::mesg('saving product sku \'' . $productSku . '\'');
					$product->save();
				}
				
				catch(Exception $e) {
					generalLib::error($e->getMessage());
					generalLib::error('product sku \'' . $productSku . '\' not saved');
					$rc = false;
				}

				//die();
			} else {
				generalLib::mesg('No category updates for product sku \'' . $productSku . '\'');
			}
	   	}

	   	//die();

	    return $rc;
	}


	//###########################
	private function processCustomOptionsSheet($customOptXslxRec) {
		$rc = true;

		$custOptError = false;

		print_r($customOptXslxRec);

		generalLib::mesg('');
		generalLib::mesg('====');

		/*
		if ($customOptXslxRec->sku == 'P-CS1-2400') {
			die();
		}
		*/

		if ( isset($customOptXslxRec->sku) ) {

			$this->procConfig->gotCustOpt = false;

			$this->procConfig->custOptProduct = magentoProd::getMagProduct($customOptXslxRec, 'sku', 'sku', 'magId', false);

			if ($this->procConfig->custOptProduct) {

				$this->procConfig->gotCustOptSku = true;

				$this->procConfig->custOptNum = -1;

			} else {

				generalLib::warn('Product sku \'' . $customOptXslxRec->sku . '\' not found - skipping sku');
				$this->procConfig->gotCustOptSku = false;

			}

		}

		if ( !$this->procConfig->gotCustOptSku ) {

			return $rc;

		}

		$productSku = $this->procConfig->custOptProduct->getSku();

		$custOptWarn = false;

		if ( isset($customOptXslxRec->sku) ) {

			if ( empty( $customOptXslxRec->option_type ) ) {

				generalLib::warn('No custom option type defined');
				$custOptWarn = true;

			} else {

				if ($customOptXslxRec->option_type != 'field') {

					if ( empty($customOptXslxRec->row_title) ) {

						generalLib::warn('No row title defined');
						$custOptWarn = true;

					}

				}

			}


			if ( empty( $customOptXslxRec->option_title ) ) {

				generalLib::warn('No custom option title defined');
				$custOptWarn = true;

			}

			if ( $custOptWarn ) {

				$custOptWrnMesg .= ' for Product sku \'' . $productSku . '\' - skipping';
				generalLib::warn( $custOptWrnMesg );
			}

		}

		if ( $custOptWarn ) {

			return $rc;

		}


		if ( ( !empty( $customOptXslxRec->option_type ) ) && ( !empty( $customOptXslxRec->option_title ) ) ) {

			$this->procConfig->customOptionArr = array();

			$this->procConfig->customOptionArr['title'] = $customOptXslxRec->option_title;

			$this->procConfig->customOptionArr['type'] = $customOptXslxRec->option_type;

			$this->procConfig->customOptionArr['is_require'] = strtolower( $customOptXslxRec->option_required ) == 'y' ? '1':'0';

			$this->procConfig->custOptNum++;

			if ( empty( $customOptXslxRec->option_sort_order ) || !is_numeric( $customOptXslxRec->option_sort_order ) ) {

				$this->procConfig->customOptionArr['sort_order'] = $this->procConfig->custOptNum;

			} else {

				$this->procConfig->customOptionArr['sort_order'] = $customOptXslxRec->option_sort_order;
	
			}

			$this->procConfig->gotCustOpt = true;

			$this->procConfig->custOptRowNum = 0;
		}


		if ( !$this->procConfig->gotCustOpt ) {

			generalLib::warn('No custom option defined for Product sku \'' . $productSku . '\' - skipping');
			$rc = false;

			return $rc;

		}


		$customOptionValueArr = array();

		//DO NOT SET ROW TITLE OR ROW SORT ORDER FOR FIELD CUSTOM OPTIONS
		if ($customOptXslxRec->option_type != 'field') {

			$customOptionValueArr['title'] = $customOptXslxRec->row_title;

			if ( empty( $customOptXslxRec->row_sort_order ) || !is_numeric( $customOptXslxRec->row_sort_order ) ) {

				$customOptionValueArr['sort_order'] = $this->procConfig->custOptRowNum;

			} else {
				
				$customOptionValueArr['sort_order'] = $customOptXslxRec->row_sort_order;

			}

		}

		if ( empty( $customOptXslxRec->row_price ) || !is_numeric( $customOptXslxRec->row_price ) ) {

			$customOptionValueArr['price'] = '0.00';

		} else {

			$customOptionValueArr['price'] = $customOptXslxRec->row_price;

		}

		if ( empty( $customOptXslxRec->option_sort_order ) ) {

			$customOptionValueArr['price_type'] = 'fixed';

		} else {

			$customOptionValueArr['price_type'] = $customOptXslxRec->row_price_type;

		}

		$customOptionValueArr['sku'] = $customOptXslxRec->row_sku;

		//FOR FIELD CUSTOM OPTIONS THERE IS NO CORRESPONDING VALUE RECORD. ALL VALUES ARE STORED IN THE CUSTOM OPTION ITSELF
		if ($customOptXslxRec->option_type == 'field') {
			$this->procConfig->customOptionArr = array_merge($this->procConfig->customOptionArr, $customOptionValueArr);

			$customOptionValueArr = array();
		}

		//print_r($this->procConfig->customOptionArr);

		//print_r($customOptionValueArr);

		$updProd = magentoCustomOpts::processCustomOption('U', $this->procConfig->custOptProduct, 'title', $this->procConfig->customOptionArr, 'title', $customOptionValueArr);

		if ($updProd) {

			$rc = magentoCustomOpts::saveCustomOptProd($this->procConfig->custOptProduct, $this->procConfig->custOptProduct->getSku(), true);

		}

		$this->procConfig->custOptRowNum++;

		//die();

		return $rc;
	}


	//###########################
	private function processGroupPricesSheet($groupPriceXslxRec) {
		$rc = true;

		generalLib::mesg('');
		generalLib::mesg('====');

		print_r($groupPriceXslxRec);

		$product = magentoProd::getMagProduct($groupPriceXslxRec, 'sku', 'sku', 'magId', false);

		if ($product) {
			$productId = $product->getId();
			$productSku = $product->getSku();
		} else {
			generalLib::warn('Product sku \'' . $groupPriceXslxRec->sku . '\' not found - skipping group price');
			return $rc;
		}

		if (empty( $groupPriceXslxRec->website ) ) {
			$groupPriceXslxRec->websiteId = '0';		//ALL WEBSITES
		} else {

			if ( isset( $this->procConfig->websiteCodeDets[ $groupPriceXslxRec->website ] ) ) {
				$tierPriceXslxRec->websiteId = $this->procConfig->websiteCodeDets[ $groupPriceXslxRec->website ]->websiteId;
			} else {
				generalLib::warn('Undefined website \'' . $groupPriceXslxRec->website . '\' - skipping group price  for sku \'' . $groupPriceXslxRec->sku . '\'');
				return $rc;
			}

		}

		$discountData = new stdClass();
		$discountData->itemNo = $groupPriceXslxRec->sku;
		$discountData->custNo = $groupPriceXslxRec->customer_group;
		$discountData->groupPrice = $groupPriceXslxRec->price;
		
		//print_r($discountData);

		$retArr = magentoCustomPrices::processMagentoGroupPrice('U', $product, $discountData);

		$rc = $retArr['rc'];

		if ($retArr['updProd'] && $rc) {
		
			try {

				$retArr['product']->save();
				generalLib::mesg('Product sku \'' . $productSku . '\' saved');
		  	} 

		  	catch(Exception $e){
				generalLib::error('Cannot save product sku \'' . $productSku . '\'');
				generalLib::error($e->getMessage());
				$rc = false;
		  	}

		} else {
			generalLib::mesg('No magento product update to apply');
		}

		return $rc;
	}


	//###########################
	private function processTierPricesSheet($tierPriceXslxRec) {
		$rc = true;

		/*
		if ($tierPriceXslxRec->sku != '100209') {

			//echo 'skip sku = ' . $productXslxRec->sku . "\n";
			return;
		}
		*/

		generalLib::mesg('');
		generalLib::mesg('====');

		//print_r($productArr);
		//print_r($this->procConfig->sheetHdrMapping);
		//die();

		print_r($tierPriceXslxRec);

		$product = magentoProd::getMagProduct($tierPriceXslxRec, 'sku', 'sku', 'magId', false);
		
		if ($product) {
			$productId = $product->getId();
			$productSku = $product->getSku();
		} else {
			generalLib::warn('Product sku \'' . $tierPriceXslxRec->sku . '\' not found - skipping tier price');
			return $rc;
		}

		if (empty( $tierPriceXslxRec->website ) ) {
			$tierPriceXslxRec->websiteId = '0';		//ALL WEBSITES
		} else {

			if ( isset( $this->procConfig->websiteCodeDets[ $tierPriceXslxRec->website ] ) ) {
				$tierPriceXslxRec->websiteId = $this->procConfig->websiteCodeDets[ $tierPriceXslxRec->website ]->websiteId;
			} else {
				generalLib::warn('Undefined website \'' . $tierPriceXslxRec->website . '\' - skipping tier price  for sku \'' . $tierPriceXslxRec->sku . '\'');
				return $rc;
			}

		}

		$discountData = new stdClass();
		$discountData->itemNo = $tierPriceXslxRec->sku;
		$discountData->custNo = $tierPriceXslxRec->customer_group;
		$discountData->tierQty = $tierPriceXslxRec->quantity;
		$discountData->tierPrice = $tierPriceXslxRec->price;
		
		//print_r($discountData);

		$retArr = magentoCustomPrices::processMagentoTierPrice('U', $product, $discountData);

		$rc = $retArr['rc'];

		if ($retArr['updProd'] && $rc) {
		
			try {

				$retArr['product']->save();
				generalLib::mesg('Product sku \'' . $productSku . '\' saved');
		  	} 

		  	catch(Exception $e){
				generalLib::error('Cannot save product sku \'' . $productSku . '\'');
				generalLib::error($e->getMessage());
				$rc = false;
		  	}

		} else {
			generalLib::mesg('No magento product update to apply');
		}

		return $rc;
	}


	//###########################
	private function processRelationsSheet($relXlsxRec) {

		generalLib::mesg('');
		generalLib::mesg('====');

		print_r($relXlsxRec);

		$product = magentoProd::getMagProduct($relXlsxRec, 'master_sku', 'sku', 'magId', false);
		
		if ($product) {
			$productId = $product->getId();
			$productSku = $product->getSku();
		} else {
			generalLib::warn('Product sku \'' . $relXlsxRec->master_sku . '\' not found - skipping relation');
			return $rc;
		}

		$productSku = $product->getSku();
		
		$linkedProductId = Mage::getModel('catalog/product')->getIdBySku($relXlsxRec->related_sku);
		
		if ($linkedProductId) {
			$updProd = magentoProd::processLinkedProduct('U', $product, $relXlsxRec->relation_type, 'sku', 'magId', $linkedProductId);
		} else {
			generalLib::warn('Cannot find related product for part number \'' . $relXlsxRec->related_sku . '\'');
		}
		
		if ($updProd) {				
			try {
				generalLib::mesg('Saving product sku \'' . $productSku . '\'');
				$product->save();
			}
			
			catch(Exception $e) {
				generalLib::error($e->getMessage());
				generalLib::error('Product sku \'' . $prodSku . '\' not saved');
				$rc = false;
			}
		} else {
			generalLib::mesg('No related part updates to apply');
		}

		return $rc;

	}


	//###########################
	private function processImagesSheet($imgXlsxRec) {
		$rc = true;

		/*
		if ($imgXlsxRec->sku != '100209') {
			return;
		}
		*/

		generalLib::mesg('');
		generalLib::mesg('====');

		print_r($imgXlsxRec);

	    if ( property_exists( (object) $imgXlsxRec, 'hero_image') ) {
			$imgXlsxRec->image_1 = $imgXlsxRec->hero_image;
		}

		$product = magentoProd::getMagProduct($imgXlsxRec, 'sku', 'sku', 'magId', false);


		if ( property_exists( (object) $imgXlsxRec, 'store') ) {

			if ($imgXlsxRec->store != '') {

				if (isset( $this->procConfig->storeCodeDets[ $imgXlsxRec->store ] ) ) {

					$imgXlsxRec->magId = $product->getId();
					
					//echo 'store id = ' . $this->procConfig->storeCodeDets[ $imgXlsxRec->store ]->storeId . "\n";

					$product = magentoProd::getMagStoreProduct($imgXlsxRec, $this->procConfig->storeCodeDets[ $imgXlsxRec->store ]->storeId, 'sku', 'sku', 'magId', false);

				} else {

					generalLib::warn('Undefined store code \'' . $imgXlsxRec->store . '\' - skipping image');
					return $rc;

				}

			}

		}

		
		if ($product) {
			
			$productId = $product->getId();
			$productSku = $product->getSku();

		} else {

			generalLib::warn('Product sku \'' . $imgXlsxRec->sku . '\' not found - skipping image');
			return $rc;

		}

		$updProd = false;
		$gotImgColumn = true;
	    $numImgCols = 0;
	    $imgNum = 1;

	    while ($gotImgColumn) {

	    	if (property_exists( (object) $imgXlsxRec, 'image_' . $imgNum)) {
	    		$numImgCols++;
	    		$imgNum++;
	    	} else {
	    		$gotImgColumn = false;
	    	}

	    }

	    echo 'numImgCols = ' . $numImgCols ."\n";

		for($imgNum = 1; $imgNum <= $numImgCols; $imgNum++) {

	    	if (!isset( $imgXlsxRec->{ 'image_' . $imgNum } )) {
	    		continue;
	    	}    

			$imgObjs = array();
			$imgObj = new stdClass();
			$imgObj->imgName = $imgXlsxRec->{ 'image_' . $imgNum };

			$imgPathInfo = pathinfo($imgObj->imgName, PATHINFO_EXTENSION);

			//echo 'imgpathInfo = ' . $imgPathInfo . "x\n";

			if ( empty( $imgPathInfo ) ) {
				$imgObj->imgName .= '.jpg';
			}

			$imgObj->imgNameCaseSensitive = false;
			$imgObj->excludeImg = false;
			$imgObj->position = $imgNum - 1;

			if ($imgNum == 1) {
				$imgObj->imgType = array('thumbnail', 'small_image', 'image');
			} else {
				$imgObj->imgType = '';
			}

			$imgObj->imgAllowUpdate = true;

			$imgObjs[] = $imgObj;
			print_r($imgObjs);
		
			$updImg = magentoImgImport::processProductImages($product, $imgObjs);

			if ($updImg == '1') {
				$updProd = true;
			}
		}

		if ($updProd) {
			try {
				generalLib::mesg('saving product sku \'' . $productSku . '\'');
				$product->save();
			}
			
			catch(Exception $e) {
				generalLib::error($e->getMessage());
				generalLib::error('product sku \'' . $productSku . '\' not saved');
				$rc = false;
			}
		} else {
			generalLib::mesg('No image updates for product sku \'' . $productSku . '\'');
		}

		//die();
		return $rc;
	}


	//###########################
	private function setStockFlags($product, $prodXlsxRec) {
		$rc = true;

		//$product = magentoProd::getMagProduct($prodRec, 'sku', 'sku', 'magId', false);
		
		if (!$product) {
			generalLib::warn('Product sku \'' . '\' not found - skipping stock flags');
			$rc = false;
			return $rc;
		}

		$productSku = $product->getSku();
		
		if ( $this->objectManager ) {

			//$stockItem = $this->objectManager->create('Magento\CatalogInventory\Api\StockRegistryInterface')->getStockItem( $product->getId() );
			$stockItem = $this->objectManager->create('Magento\CatalogInventory\Api\StockRegistryInterface')->getStockItemBySku( $productSku);

		} else {

			$stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product);

		}

		$updStock = false;

		if ( strtolower($prodXlsxRec->manage_stock) == 'y') {
                $prodXlsxRec->manage_stock = '1';
            } else {
				$prodXlsxRec->manage_stock = '0';
        }

		//$manageStock = $stockItem->getUseConfigManageStock();
		$manageStock = $stockItem->getManageStock();

		if ( $this->objectManager ) {

			$configManageStock = $this->scopeConfig->getValue('cataloginventory/item_options/manage_stock', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
			
		} else {

			$configManageStock = Mage::getStoreConfig('cataloginventory/item_options/manage_stock');

		}

		//echo 'managestock = ' . $manageStock . "\n";
		//echo 'prod Managestock = ' . $prodXlsxRec->manage_stock . "\n";
		//echo 'config Managestock = ' . $configManageStock . "\n";

		if ($manageStock != $prodXlsxRec->manage_stock) {
			$stockItem->setData('use_config_manage_stock', $prodXlsxRec->manage_stock);

			if ($configManageStock == '1' && $prodXlsxRec->manage_stock == '0') {
                    $stockItem->setData('use_config_manage_stock', '0');
					//$stockItem->setManageStock('0');
					$stockItem->setData('manage_stock', '0');
            } elseif ($configManageStock == '0' && $prodXlsxRec->manage_stock == '1') {
                    $stockItem->setData('use_config_manage_stock', '1');
            }

			generalLib::mesg('setting stock controlled to \'' . $prodXlsxRec->manage_stock . '\'');
			$updStock = true;
		}

		if ($prodXlsxRec->manage_stock == '1') {
			$backOrders = $stockItem->getData('backorders');
			//echo 'backOrders = ' . $backOrders . "\n";

			$useConfigBackorders = $stockItem->getData('use_config_backorders');
			//echo 'useconfigBackOrders = ' . $useConfigBackorders . "\n";

			if ( $this->objectManager ) {

				$configBackorders = $this->scopeConfig->getValue('cataloginventory/item_options/backorders', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

			} else {

				$configBackorders = Mage::getStoreConfig('cataloginventory/item_options/backorders');

			}

			//echo 'configBackOrders = ' . $configBackorders . "\n";

			if ( property_exists( (object) $productXslxRec, 'back_orders') ) {

				$lcBackorders = strtolower($prodXlsxRec->back_orders);

				if ( $lcBackorders == 'y') {
					$prodXlsxRec->back_orders = '1';	//BACK ORDERS
				} elseif ( $lcBackorders == 'y-n' ) {
					$prodXlsxRec->back_orders = '2';	//BACK ORDERS WITH NOTIFY
	        	} else {
	        		$prodXlsxRec->back_orders = '0';
	        	}

			}  

			if ($prodXlsxRec->back_orders != '0') {

				if ($useConfigBackorders == '1') {
					$useConfigBackorders = '0';
					$stockItem->setData('use_config_backorders', $useConfigBackorders);
					generalLib::mesg('Allow back orders - do not use default settings');
					$updStock = true;
				}

			} else {

				if (!$useConfigBackorders) {
					$useConfigBackorders = '1';	//DEFAULT IS NO BACK ORDERING
					$stockItem->setData('use_config_backorders', $useConfigBackorders);
					generalLib::mesg('Do not allow back orders - use default settings');
					$updStock = true;
				}

			}

			if ($backOrders != $prodXlsxRec->back_orders && $useConfigBackorders == '0' && $configBackorders == '0') {
				$backOrders = $prodXlsxRec->back_orders;	
				$stockItem->setData('backorders', $backOrders);

				if ($backOrders == '1') {
					generalLib::mesg('Setting product to have backorders');
				} else {
					generalLib::mesg('Setting product to have backorders with notify');
				}

				$updStock = true;
			} 

		}

		if ($updStock) {

			if ( $this->objectManager ) {

				try {
					generalLib::mesg('saving stock item for sku \'' . $productSku . '\'');
					$this->objectManager->create('Magento\CatalogInventory\Api\StockRegistryInterface')->updateStockItemBySku($productSku , $stockItem);
				}

				catch(Exception $e) {
					eneralLib::error($e->getMessage());
					generalLib::error('stock item for sku \'' . $productSku . '\' not saved');
					$rc = false;
				}

			} else {

				try {
					generalLib::mesg('saving stock item for sku \'' . $productSku . '\'');
					$stockItem->save();
				}
				
				catch(Exception $e) {
					generalLib::error($e->getMessage());
					generalLib::error('stock item for sku \'' . $productSku . '\' not saved');
					$rc = false;
				}

			}
			
		} else {
			generalLib::mesg('No stock updates to apply');
		}

	}


	//###########################
	private function processInventorySheet($invRec) {
		$rc = true;

		//echo 'got sku ' . $productCsvArr[0] . "\n";
		//return;

		generalLib::mesg('');
		generalLib::mesg('====');

		print_r($invRec);

		$product = magentoProd::getMagProduct($invRec, 'sku', 'sku', 'magId', false);
		
		if (!$product) {
			generalLib::warn('Product sku \'' . $invRec->sku . '\' not found - skipping inventory');
			return $rc;
		}

		$productSku = $product->getSku();
		
		$stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product);
		//print_r($stockItem->getData());
		
		if ($stockItem->getUseConfigManageStock() == '0') {
			generalLib::mesg('This product is not stock controlled - skipping stock update');
			return $rc;
		}

		$oldQty = (float)$stockItem->getQty();
		
		$newQty = (float)$invRec->quantity;
		
		//echo 'oldqty = ' . $oldQty . "\n";
		//echo 'newqty = ' . $newQty . "\n";
		
		//TEMP
		//USE TRUE TO TRIGGER A TRUE-UP
		//$checkConfigProdStockStatus = true;
		$chkConfigProdStockStatus = false;

		if ( $oldQty != $newQty) {
			 
			$stockItem->setData('qty', $newQty);
			//$stockItem->setQty($newQty);
			generalLib::mesg('Stock level updated to \'' . $newQty .'\'');
			$updStock = true;

			if ( ($oldQty <= 0 && $newQty > 0 ) || ( $oldQty > 0 && $newQty <= 0) ) {
				$chkConfigProdStockStatus = true;
			}

		}
		
		//TEMP - FOR DANCE GEAR
		$chkConfigProdStockStatus = true;

		//NOTE: ITEMS ARE SHOWN IN STOCK EVEN WHEN THERE IS NO QTY, AS ALL ITEMS CAN BE BACK ORDERED BY DEFAULT (AS DEFINED IN THE MAGENTO CONFIGURATION)
		
		//print_r($stockItem->getData());

		$isInStock = $stockItem->getData('is_in_stock');
		//echo 'isInStock = ' . $isInStock . "\n";
		
		//SET THE PRODUCT TO BE OUT OF STOCK IF THE STOCK LEVEL <= 0
		$stockLevel = (float)$stockItem->getQty();
		//echo 'stockLevel = ' . $stockLevel . "\n";
		
		$backOrders = $stockItem->getData('backorders');
		//echo 'backOrders = ' . $backOrders . "\n";


		$useConfigBackorders = $stockItem->getData('use_config_backorders');
		//echo 'useconfigBackOrders = ' . $useConfigBackorders . "\n";

		$configBackorders = Mage::getStoreConfig('cataloginventory/item_options/backorders');
		//echo 'configBackOrders = ' . $configBackorders . "\n";


		//ALL PRODUCTS TO ALLOW BACK ORDERING
		if ($backOrders == '0' && $useConfigBackorders == '1' && $configBackorders == '1') {
			$backOrders = '1';	
			$stockItem->setData('backorders', $backOrders);
			generalLib::mesg('Setting product to have backorders');
			$updStock = true;
		} 

		if ($stockLevel <= 0 && $backOrders == '0' && $isInStock == '1') {
			$stockItem->setData('is_in_stock', '0');
			generalLib::mesg('Setting product to out of stock');
			$updStock = true;
		} else {

			if ( ( $stockLevel > 0 || ( $stockLevel <= 0 && $backOrders == '1' ) ) && $isInStock == '0') {
				$stockItem->setData('is_in_stock', 1);
				generalLib::mesg('Setting product to in stock');
				$updStock = true;
			}

		}

		if ($updStock) {

			try {
				generalLib::mesg('saving stock item for sku \'' . $productSku . '\'');
				$stockItem->save();
			}
			
			catch(Exception $e) {
				generalLib::error($e->getMessage());
				generalLib::error('stock item for sku \'' . $productSku . '\' not saved');
				$rc = false;
			}
		} else {
			generalLib::mesg('No stock updates to apply');
		}


		if ($rc && $chkConfigProdStockStatus) {
			magentoConfigurableProducts::checkSimProdForConfigProgParent( $product->getId() );
		}

		//die();

		return $rc;
	}

}

?>
