<?php
class magentoImport {
	static private $chkArr;
	static private $replArr;
	static private $booleanChkArr = array('-1', '1', 'true', 'y', 'yes');
	const ENABLE_SHIPMENT_EMAILS = true;
	const ALL = 'A';
	static private $feedLkFile;
	static private $procObj;


	static public function importConfig($procObj) {
		self::$procObj = $procObj;
	}
	
	
	//###########################
	static public function getStoreDets() {

		$storeDets = array();

		//$storeCollection = Mage::getModel('core/store')->getCollection();

		$storeCollection = self::$procObj->objectManager->get('\Magento\Store\Model\StoreManagerInterface')->getStores();

		//print_r($storeCollection);

		//die();

		
		foreach($storeCollection as $site => $store) {
			$storeID = $store->getStoreId();
			
			$storeObj = new stdClass();
			$storeObj->websiteId = $store->getWebsiteId();
			$storeObj->storeCode = $store->getCode();
			$storeObj->storeName = $store->getName();

			$storeDets[ $storeID ] = $storeObj;
		}

		return $storeDets;
	}


	//###########################
	static public function getStoreCodeDets() {
		$storeDets = array();

		//$storeCollection = Mage::getModel('core/store')->getCollection();

		$storeCollection = self::$procObj->objectManager->get('\Magento\Store\Model\StoreManagerInterface')->getStores();

		foreach($storeCollection as $store) {
			$storeObj = new stdClass();
			$storeObj->websiteId = $store->getWebsiteId();
			$storeObj->storeId = $store->getStoreId();
			$storeObj->storeName = $store->getName();
			$storeDets[ $store->getCode() ] = $storeObj;
		}

		return $storeDets;
	}


	//###########################
	static public function getWebSiteDets() {
		$websiteDets = array();

		$websiteCollection = self::$procObj->objectManager->get('\Magento\Store\Model\StoreManagerInterface')->getWebsites();

		//foreach (Mage::app()->getWebsites() as $website) {

		foreach ($websiteCollection as $website) {
			$websiteObj = new stdClass();
			$websiteObj->websiteCode = $website->getCode();
			$websiteObj->websiteName = $website->getName();
			$websiteObj->defaultStoreId = $website->getDefaultStore()->getId();

			$websiteDets[ $website->getWebsiteId() ] = $websiteObj;
		}

		return $websiteDets;
	}


	//###########################
	static public function getWebSiteCodeDets() {
		$websiteDets = array();

		$websiteCollection = self::$procObj->objectManager->get('\Magento\Store\Model\StoreManagerInterface')->getWebsites();

		//foreach (Mage::app()->getWebsites() as $website) {

		foreach ($websiteCollection as $website) {
			$websiteID = $website->getWebsiteId();

			$websiteObj = new stdClass();
			$websiteObj->websiteId = $website->getWebsiteId();
			$websiteObj->websiteName = $website->getName();
			$websiteObj->defaultStoreId = $website->getDefaultStore()->getId();

			$websiteDets[ $website->getCode() ] = $websiteObj;
		}

		return $websiteDets;
	}

	//###########################
	static public function setChkReplChars($chkArr, $replArr) {
		self::$chkArr = $chkArr;
		self::$replArr = $replArr;
	}
	
	
	//###########################
	static public function shipOrder($procType, $orderNum, $shipTrackDets, $shipItems = null, $sendShipmentEmail = false) {
		generalLib::mesg('');
		generalLib::mesg('====');
		generalLib::mesg('Processing shipment for order ' . $orderNum);
				
		$order = Mage::getModel('sales/order')->loadByIncrementId($orderNum);

		if (!$order->canShip()) {
			generalLib::warn('Nothing to ship');
			return;
		}
		
		$itemShipmentQtys = array();

		$orderItems = $order->getAllItems();
		
		foreach($shipItems as $shipItem) {
			$gotItem = false;
			$gotCustomOption = false;
			
			foreach($orderItems as $orderItem) {
				
				$orderItemProduct = $orderItem->getProduct();
				$orderItemProdOptions = $orderItem->getProductOptions();

				//IF AN ORDER ITEM HAS CUSTOM OPTIONS, THE SKU WILL BE APPENDED WITH THE CUSTOM OPTION SKU - OBTAIN THE ITEM SKU FROM THE PRODUCT
				if (isset($orderItemProdOptions['options'])) {
					$prodOptions = $orderItemProduct->getProductOptionsCollection();
					
					foreach($orderItemProdOptions['options'] as $orderItemProdOption) {
						foreach ($prodOptions as $prodOption) {
							//print_r($prodOption->getData());

							if ($orderItemProdOption['option_id'] == $prodOption['option_id']) {
								$prodOptionValues = $prodOption->getValues();
										
								$orderItemProdValues = explode(',', $orderItemProdOption['option_value']);
								//print_r($orderItemProdValues);
										
								foreach ($orderItemProdValues as $orderItemProdValue) {
									//echo 'orderItemProdValue = ' . $orderItemProdValue . "\n";
											
									foreach ($prodOptionValues as $prodOptionValue) {
										if ($prodOptionValue->getOptionTypeId() == $orderItemProdValue) {
											if ($prodOptionValue->getSku() == $shipItem->itemSku) {
												$gotCustomOption = true;
											}
										}
									}
								}
							}
						}
					}
					
					$product = Mage::getModel('catalog/product')->load($orderItem->getProductId());
					
					if ($product) {
						$productCode = $product->getSku();
					} else {
						$productCode = $orderItem->getSku();
					}
				} else {
					$productCode = $orderItem->getSku();
				}
				
				if ($productCode == $shipItem->itemSku) {
					$gotItem = true;
					break;
				}
			}
			
			if ($gotItem) {
				if ($shipItem->itemShippedQty > 0) {
					$itemQtyLeftToShip = $orderItem->getQtyOrdered() - $orderItem->getQtyShipped();

					if ($itemQtyLeftToShip > 0) {
						if ( $itemQtyLeftToShip >= $shipItem->itemShippedQty) {
							$itemShipmentQtys[$orderItem->getItemId()] = $shipItem->itemShippedQty;
							generalLib::mesg('Setting product sku ' . $shipItem->itemSku . ' - ' . $shipItem->itemShippedQty . ' shipped');
						} else {
							$itemShipmentQtys[$orderItem->getItemId()] = $itemQtyLeftToShip;

							generalLib::warn('Specified shipped qty of \'' . $shipItem->itemShippedQty . '\' for Product sku \'' . $shipItem->itemSku . '\' is greater than the \'' .  $itemQtyLeftToShip . '\' left to ship -  shipping remainder of items');

						}
					} else {
						generalLib::warn('Product sku ' . $shipItem->itemSku . ' has already been shipped');
					}
				} else {
					generalLib::error('Invalid shipping quantity ' . $shipItem->itemShippedQty . ' for product sku ' . $shipItem->itemSku);
				}
			} else {
				if ($gotCustomOption) {
					generalLib::mesg('Product sku ' . $shipItem->itemSku . ' is only a custom option in order ' . $orderNum . ' -- skipping');
				} else {
					generalLib::error('Product sku ' . $shipItem->itemSku . ' does not exist in order ' . $orderNum);
				}
				
				continue;
			}
		}
		
		//echo '<pre>';
		//print_r($itemShipmentQtys);
		//print_r($shipTrackDets);
		//print_r($shipItems);
		//echo '</pre>';
		//die();
		
		if (count($itemShipmentQtys) == 0) {
			generalLib::warn('No specified items to ship for order ' . $orderNum);
			return;
		}
		
		$shipment = $order->prepareShipment($itemShipmentQtys);
		
		 if ($shipment) {
			 $shipment->register();
			 $shipment->getOrder()->setIsInProcess(true);
			 //$order->setIsInProcess(true);

			foreach($shipTrackDets as $shipTrackDet) {
				$shiptrackData = array();
				
				//ALL CARRIER CODES SETUP WILL BE IN LOWERCASE WITH NO SPACES. IF THE CARRIER CODE BEGINS WITH A NUMBER, IT WILL BE PUT AT THE END, AS MAGENTO CANNOT SUPPORT
				//CARRIER CODES BEGINNING WITH A NUMBER (EG. 13ten WOULD BE SETUP ten13)
				$shipTrackDet->carrier = strtolower($shipTrackDet->carrier);
				$shipTrackDet->carrier = str_replace(' ', '', $shipTrackDet->carrier);
				
				if (preg_match("~^(\d+)~", $shipTrackDet->carrier, $numsAtStart)) {		//ANY NUMBERS AS THE START OF THE CARRIER NAME
					$numStr = $numsAtStart[1];											//NUMBERS AT START OF THE CARRIER NAME
					$numLen = strlen($numStr);											//LENGTH OF NUMBERS IN THE CARRIER NAME
					$shipTrackDet->carrier = substr($shipTrackDet->carrier, $numLen);	//STRIP OFF NUMBERS AT THE START OF THE CARRIER NAME
					$shipTrackDet->carrier .= $numStr;									//PUT STRIPPED OFF NUMBERS AT END OF THE CARRIER NAME
				}
				
				//Check whether the carrier exists - if not, set to custom			
				//$storeId = Mage::app()->getStore()->getId();
				$carrierInstances = Mage::getSingleton('shipping/config')->getAllCarriers($order->getStoreId());
				$gotCarrier = false;
				
				foreach ($carrierInstances as $carrierCode => $carrier) {
					if ($shipTrackDet->carrier == $carrierCode) {
						$gotCarrier = true;
						break;
					}
				}
				
				if (!$gotCarrier) {
					$shipTrackDet->carrier = 'Custom';
				}
				
				$shiptrackData['carrier_code'] = $shipTrackDet->carrier;
				$shiptrackData['title'] = $shipTrackDet->carrierTitle;
				$shiptrackData['number'] = $shipTrackDet->trackingNum;
			 
				//echo '<pre>';
				//print_r($shiptrackData);
				//echo '</pre>';
				
				$track = Mage::getModel('sales/order_shipment_track')->addData($shiptrackData);
				$shipment->addTrack($track);
			}
			
			try {
				if ($sendShipmentEmail) {
					$shipment->sendEmail(true, 'Order marked as shipped by the ' . $procType . ' Processor.')
						->setEmailSent(true)
						->save();
				}
		
				$transactionSave = Mage::getModel('core/resource_transaction')
										->addObject($shipment)
										->addObject($shipment->getOrder())
										->save();
										
				generalLib::mesg('Shipment created for Order number \'' . $orderNum . '\'');
				
				//IF NOTHING LEFT TO SHIP - COMPLETE ORDER - DO THIS IN THE SAME TRANSACTION, AS SOMETIMES A SHIPMENT CANNOT BE CREATED, ONCE COMPLETE YOU CAN'T AMEND THE ORDER
				if (!$order->canShip()) {
					$magentoOrderSts = Mage_Sales_Model_Order::STATE_COMPLETE;
					magentoGeneral::updateOrderStatus($orderNum, $magentoOrderSts, $magentoOrderSts, true, $procType . ' Processor');
				}
			}
			
			catch(Exception $e) {
				generalLib::mesg($e->getMessage());
			}
		 } else {
			generalLib::mesg('Cannot create shipment for Order number \'' . $orderNum . '\'');
		 }
	}
	
	
	//###########################	
	static public function processMagAttrs($dataRecord, $dataFeed, $feedFields) {
		//echo "got to processMagAttrs\n";
		$recUpdated = false;

		foreach($feedFields as $feedField => $magField) {
			//echo 'magField = ' . $magField . "\n";
			//echo 'dataFeed = ' . $dataFeed . "\n";
			//echo 'feedField = ' . $feedField . "\n";

			if (property_exists($dataFeed, $feedField)) {
				$oldVal = $dataRecord->getData($magField);
				$newVal = (string)$dataFeed->$feedField;
				
				//echo 'magField = ' . $magField . "\n";
				//echo 'oldVal = ' . $oldVal . "\n";
				//echo 'newVal = ' . $newVal . "\n";
				
				//WITH SOME NUMERIC FIELDS (EG. WEIGHT, MSRP) MAGENTO STORES NUMBERS FORMATTED TO A FIXED DPS,
				//ROUNDING ACCORDINGLY. THEREFORE, BEFORE COMPARISON MATCH THE $newVal TO THE SAME NUMBER OF DPS
				//AS THE OLD MAGENTO VALUE, STOPPING ANY UNNECESSARY UPDATES
				if (is_numeric($oldVal)) {
					if (is_numeric($newVal)) {
						$oldVal = strval( floatval($oldVal) );
						$newVal = strval( floatval($newVal) );
						//echo 'aft oldVal = ' . $oldVal . "\n";
						//echo 'aft newVal = ' . $newVal . "\n";
					}
				} 

				if (strcmp($oldVal, $newVal) != 0) {
					generalLib::mesg('Setting ' . $magField . ' to \'' . $newVal . '\'');
					$dataRecord->setData($magField, $newVal);
					
					if (!$recUpdated) {
						$recUpdated = true;
					}
				}
			}
		}
		
		//echo 'recUpdated = ' . $recUpdated . "\n";
		return $recUpdated;	
	}

	//###########################	
	static public function processCustomAttrs($procObj, $dataRecord, $dataFeed, $feedFields, $feedFieldMappings = array()) {

		$retArr = magentoImport::processCustomAttrsRetArr($procObj, $dataRecord, $dataFeed, $feedFields, $feedFieldMappings);

		return $retArr['recUpdated'];
	}
	
	
	//###########################	
	static public function processCustomAttrsRetArr($procObj, $dataRecord, $dataFeed, $feedFields, $feedFieldMappings = array()) {
		$retArr = array();

		$recUpdated = false;
		$feedFieldMapping = false;

		//print_r($feedFields);
		//print_r($feedFieldMappings);
		//print_r($dataFeed);

		foreach($feedFields as $feedField => $feedFieldType) {
			//echo 'feed field = ' . $feedField . "\n";
			//echo 'feed field type = ' . $feedFieldType . "\n";
			//echo 'data field = ' . $dataFeed->$feedField . "\n";
			
			if (property_exists($dataFeed, $feedField)) {
				$newVal = (string)$dataFeed->$feedField;

				if (isset($feedFieldMappings[$feedField])) {
					$feedFieldMapping = $feedFieldMappings[$feedField];
					//echo 'feedFieldMapping = ' . $feedFieldMapping . "\n";
					$oldVal = $dataRecord->getData($feedFieldMapping);
				} else {
					$feedField = strtolower($feedField);	//STORING ALL MAGENTO ATTRIBUTE NAMES IN LOWER CASE BY DEFAULT
					$oldVal = $dataRecord->getData($feedField);
				}
				
				//echo "\n" . 'old val = ' . $oldVal . "\n";
				//echo "\n\n" . 'bef switch newVal = ' . $newVal . "\n";
				
				switch($feedFieldType) {
					case 'B':									
								if (in_array( strtolower($newVal), self::$booleanChkArr)) {
									$newVal = '1';
								} else {
									$newVal = '0';
								}
								
								break;
					
					case 'S':	$processFunc = 'process_' . $feedField . '_Field';
					
								if (method_exists($procObj, $processFunc)) {
									$rc = true;
									
									//CHANGE TO RETURN AN ARRAY WITH THE NEW VALUE AND RC, CURRENTLY DONE THIS MAY FOR BACKWARD COMPATIBILITY - TO DO
									
									if ($procObj = 'magentoAttrImport') {
										$newVal = magentoAttrImport::$processFunc($newVal, $rc);
									} else {
										$newVal = $procObj->$processFunc($newVal, $rc);
									}
									
									if (!$rc) {
										$newVal = $oldVal;
									}
								} else {
									generalLib::error($processFunc . ' is not defined');
								}
															
								break;
											
					case 'D':
								//SAA DATES ARE PROVIDED IN THE FORMAT 'YYYY-MM-DDT00:00:00'. MAGENTO RETURNS 'YYYY-MM-DD 00:00:00'. CHANGE FOR COMPARISON
								$newVal = str_replace('T', ' ', $newVal);
								
								if (strpos($newVal, '00:00:00') === false) {
									$newVal .= ' 00:00:00';
								}
								break;
				}
				
				//echo 'feedField = ' . $feedField . "\n";
				//echo 'feedFieldType = ' . $feedFieldType . "\n";
				//echo 'oldVal = ' . $oldVal . "\n";
				//echo 'oldValType = ' . gettype($oldVal) . "\n";
				//echo 'newVal = ' . $newVal . "\n";
				//echo 'newValType = ' . gettype($newVal) . "\n";


				//IF THE OLD FIELD IS EMPTY AND THE NEW FIELD IS EMPTY, THEN THE ABSOLUTE COMPARISON '!==' TREATS IT AS
				//AS A CHANGE. HOWEVER, THE '!==' IS NEEDED FOR NUMERIC STRING COMPARISONS AS PHP CONVERTS A NUMERIC STRING 
				//TO A //NUMBER BEFORE COMPARISON EG. '010' AND '10' ARE CONSIDERED TO BE THE SAME. THIS PHP BEHAVIOUR IS 
				//WRONG, BUT WE CAN ONLY WORK AROUND IT.
				/*
				$nullNoChange = false;

				if (is_null($oldVal)) {
					if (strlen($newVal) == 0) {
						$nullNoChange = true;
					}
				}
				*/

				//if (($oldVal !== $newVal) && (!$nullNoChange)) {
				if (strcmp($oldVal, $newVal) != 0) {
					
					if (isset($feedFieldMappings[$feedField])) {
						generalLib::mesg('Setting ' . $feedFieldMapping . ' (' .  $feedField . ') to \'' . $newVal . '\'');
						$dataRecord->setData($feedFieldMapping, $newVal);
					} else {
						generalLib::mesg('Setting ' . $feedField . ' to \'' . $newVal . '\'');
						$dataRecord->setData($feedField, $newVal);
					}
					
					if (!$recUpdated) {
						$recUpdated = true;
					}
				}
			}
		}
		
		$retArr['newVal'] = $newVal;
		$retArr['recUpdated'] = $recUpdated;

		return $retArr;
	}
	
	
	//###########################	
	static public function makePlainField($fieldValue) {
		$plainFieldName = str_replace( self::$chkArr, self::$replArr, $fieldValue);
		
		//CATCH OTHER SPECIAL CHARACTERS
		$plainFieldName = htmlspecialchars_decode($plainFieldName);
		return $plainFieldName;
	}
	
	
	//###########################	
	static public function setAttrOptions($attrModel, $attrName, $setLowerCaseLabel = true) {
		$attrOpts = array();
		
		//$attribute = Mage::getModel('eav/config')->getAttribute($attrModel, $attrName);

		//$attribute = magentoProdMage::getModel('\Magento\Eav\Model\Config')->getAttribute($attrModel, $attrName);

		$attribute = self::$procObj->objectManager->get('\Magento\Eav\Model\Config')->getAttribute($attrModel, $attrName);


		//print_r($attribute->getData());
		//die();

		foreach ($attribute->getSource()->getAllOptions(true, true) as $option ) {
				$optLabel = $option['label'];

				if ($setLowerCaseLabel) {
					$optLabel = strtolower( $option['label'] );
				}

				$attrOpts[ $optLabel ] = $option['value'];
		}
		
		return $attrOpts;
	}
	
	
	//###########################
	//NOTE: THIS FUNCTION NEEDS TESTING WITH $attrArr MODIFICATION
	static public function setAttrMultOptions($attrArr, $inpAttrValues) {
		$attrValuesArr = explode(',', $inpAttrValues);
		$attrMagValues = array();
		
		foreach($attrValuesArr as $attrValue) {
			//$attrMagValues[] = $this->procConfig->$attrName[ strtolower($attrValue) ];
			$attrMagValues[] = $attrArr[ strtolower($attrValue) ];
		}
		
		return $attrMagValues;
	}


	//###########################
	static public function chkModelDropdownAttr($attrModel, $modelRec, $dropDownAttrName, $modelAttrValue, &$dropDownAttrOpts, $chkLowerCaseLabel = true) {
		
		$updModel = false;

		$oldModelDropDownVal = $modelRec->getData($dropDownAttrName);

		//echo 'oldModelDropDownVal = ' . $oldProdDropDownVal . "\n";

		$newModelDropDownVal = magentoImport::chkModelDropdownMultiSelectAttr($attrModel, $dropDownAttrName, $modelAttrValue, $dropDownAttrOpts, $chkLowerCaseLabel);

		//echo 'newModelDropDownVal = ' . $newModelDropDownVal . "\n";

		if ($oldModelDropDownVal != $newModelDropDownVal) {
			$modelRec->setData($dropDownAttrName, $newModelDropDownVal);

			generalLib::mesg('Setting ' . $dropDownAttrName . ' to \'' . $newModelDropDownVal . '\' (' . $modelAttrValue . ')');
			$updModel = true;
		}

		return $updModel;
	}


	//###########################
	static public function chkModelMultiSelectAttr($attrModel, $modelRec, $multiSelectAttrName, $modelAttrValue, &$multiSelectAttrOpts, &$newMdValueArr, $chkLowerCaseLabel = true) {

		//echo 'multiSelectAttrName = ' .  $multiSelectAttrName . "\n";

		//print_r($multiSelectAttrOpts);
		//die();

		$newMultiSelectVal = magentoImport::chkModelDropdownMultiSelectAttr($attrModel, $multiSelectAttrName, $modelAttrValue, $multiSelectAttrOpts, $chkLowerCaseLabel);

		$newMdValueArr[] = $newMultiSelectVal;

		//print_r($newMdValueArr);

		generalLib::mesg('Setting ' . $multiSelectAttrName . ' to \'' . $newMultiSelectVal . '\' (' . $modelAttrValue . ')');
	}


	//###########################
	static public function chkModelMultiSelectAttrVals($attrModel, $modelRec, $multiSelectAttrName, $newMdValueArr, $multiSelectAttrOpts) {
		$updModel = false;

		$attrModelId = $modelRec->getId();

		$oldMdValue = $modelRec->getData($multiSelectAttrName);

		$oldMdValueArr = explode(',', $oldMdValue);

		//echo 'oldMdValueArr:' . "\n";
		//print_r($oldMdValueArr);

		//echo 'newMdValueArr:' . "\n";
		//print_r($newMdValueArr);

		if (!sort($oldMdValueArr, SORT_NUMERIC)) {
			generalLib::error('Cannot sort old ' . $attrModel . ' id \'' . $attrModelId . '\' multi select values for attr \'' . $multiSelectAttrName . '\'');
		}

		if (!sort($newMdValueArr, SORT_NUMERIC)) {
			generalLib::error('Cannot sort new ' . $attrModel . ' id \'' . $attrModelId . '\' multi select values for attr \'' . $multiSelectAttrName . '\'');
		}

		$addedAttrVals = array_diff($newMdValueArr, $oldMdValueArr);
		//echo 'addedAttrVals:' . "\n";
		//print_r($addedAttrVals);
		
		$deletedAttrVals = array_diff($oldMdValueArr, $newMdValueArr);
		//echo 'deletedAttrVals:' . "\n";
		//print_r($deletedAttrVals);
		
		if ( (count($addedAttrVals) > 0) || (count($deletedAttrVals) > 0) ) {

			foreach($addedAttrVals as $addedAttrVal) {
				$addAttrKey = array_search($addedAttrVal, $multiSelectAttrOpts);

				generalLib::mesg('Adding \'' . $addedAttrVal . '\' (' . $addAttrKey . ') to ' . $multiSelectAttrName);
			}
			
			foreach($deletedAttrVals as $deletedAttrVal) {
				$delAttrKey = array_search($deletedAttrVal, $multiSelectAttrOpts);

				generalLib::mesg('deleting \'' . $deletedAttrVal . '\' (' . $delAttrKey . ') from ' . $multiSelectAttrName);
			}

			$newMdValue = implode(',', $newMdValueArr);

			$modelRec->setData($multiSelectAttrName, $newMdValue);
			$updModel = true;
		}

		return $updModel;
	}


	//###########################
	static public function chkModelDropdownMultiSelectAttr($attrModel, $attrName, $modelAttrValue, &$attrOpts,  $chkLowerCaseLabel = true) {
		$rc = true;
		$modelAttrValue = trim($modelAttrValue);

		//echo 'modelAttrValue = ' . $modelAttrValue . "\n";
		//print_r($attrOpts);
		//die();

		if ($chkLowerCaseLabel) {
			$chkModelAttrValue = strtolower($modelAttrValue);
		} else {
			$chkModelAttrValue = $modelAttrValue;
		} 
			
		//echo 'chkModelAttrValue = ' . $chkModelAttrValue . "\n";

		//print_r($attrOpts);
		//die();

		if (!array_key_exists( $chkModelAttrValue, $attrOpts)) {
			//echo 'attrModel = ' . $attrModel . "\n";
			//echo 'attrName = ' . $attrName . "\n";

			//$magAttr = Mage::getModel('eav/config')->getAttribute($attrModel, $attrName);

			$magAttr = self::$procObj->objectManager->get('\Magento\Eav\Model\Config')->getAttribute($attrModel, $attrName);
			
			$magAttr->setData(
				'option',
				array(
					'value' => 	array(
								'option' => array($modelAttrValue, $modelAttrValue),
								),
				)
			);

			try {
				generalLib::mesg('Adding \'' . $modelAttrValue . '\' to ' . $attrName . ' list');
				$magAttr->save();
			
				//RELOAD ATTRIBUTE - TO GET THE NEW ATTRIBUTE
				$attrOpts = self::setAttrOptions($attrModel, $attrName);
			}
		
			catch(Exception $e) {
				generalLib::error($e->getMessage());
				generalLib::error('Cannot Add \'' . $modelAttrValue . '\' to ' . $attrName . ' list');
				$rc = false;
			}
		}

		//print_r($attrOpts);

		if ($rc) {
			$newAttrVal = $attrOpts[ $chkModelAttrValue ];
		} else {
			$newAttrVal = false;
		}

		//echo 'productAttrValue = ' . $modelAttrValue . "\n";
		//echo 'newAttrVal = ' . $newAttrVal . "\n";
		//die();
		
		return $newAttrVal;
	}


	//THIS FUNCTION HAS BEEN SUPERCEDED BY THE chkModelDropdownAttr FUNCTION ABOVE - KEPT FOR OLDER INTEGRATIONS
	//###########################
	static public function chkDropdownAttr($product, $dropDownAttrName, $productAttrValue, &$dropDownAttrOpts, $chkLowerCaseLabel = true) {
		$updProd = false;

		$oldProdDropDownVal = $product->getData($dropDownAttrName);

		//echo 'oldProdDropDownVal = ' . $oldProdDropDownVal . "\n";

		$newProdDropDownVal = magentoImport::chkDropdownMultiSelectAttr($dropDownAttrName, $productAttrValue, $dropDownAttrOpts, $chkLowerCaseLabel);

		//echo 'newProdDropDownVal = ' . $newProdDropDownVal . "\n";

		if ($oldProdDropDownVal != $newProdDropDownVal) {
			$product->setData($dropDownAttrName, $newProdDropDownVal);

			generalLib::mesg('Setting ' . $dropDownAttrName . ' to \'' . $newProdDropDownVal . '\' (' . $productAttrValue . ')');
			$updProd = true;
		}

		return $updProd;
	}


	//THIS FUNCTION HAS BEEN SUPERCEDED BY THE chkModelMultiSelectAttr FUNCTION ABOVE - KEPT FOR OLDER INTEGRATIONS
	//###########################
	static public function chkMultiSelectAttr($product, $multiSelectAttrName, $productAttrValue, &$multiSelectAttrOpts, &$newMdValueArr, $chkLowerCaseLabel = true) {
		$newMultiSelectVal = magentoImport::chkDropdownMultiSelectAttr($multiSelectAttrName, $productAttrValue, $multiSelectAttrOpts, $chkLowerCaseLabel);

		$newMdValueArr[] = $newMultiSelectVal;

		//print_r($newMdValueArr);

		//generalLib::mesg('Setting ' . $multiSelectAttrName . ' to \'' . $newMultiSelectVal . '\' (' . $productAttrValue . ')');
	}


	//THIS FUNCTION HAS BEEN SUPERCEDED BY THE chkModelMultiSelectAttrVals FUNCTION ABOVE - KEPT FOR OLDER INTEGRATIONS
	//###########################
	static public function chkMultiSelectAttrVals($product, $multiSelectAttrName, $newMdValueArr, $multiSelectAttrOpts) {
		$updProd = false;

		$oldMdValue = $product->getData($multiSelectAttrName);

		$oldMdValueArr = explode(',', $oldMdValue);

		//echo 'oldMdValueArr:' . "\n";
		//print_r($oldMdValueArr);

		//echo 'newMdValueArr:' . "\n";
		//print_r($newMdValueArr);

		if (!sort($oldMdValueArr, SORT_NUMERIC)) {
			generalLib::error('Cannot sort old product sku \'' . $productSku . '\' multi select values for attr \'' . $multiSelectAttrName . '\'');
		}

		if (!sort($newMdValueArr, SORT_NUMERIC)) {
			generalLib::error('Cannot sort new product sku \'' . $productSku . '\' multi select values for attr \'' . $multiSelectAttrName . '\'');
		}

		$addedAttrVals = array_diff($newMdValueArr, $oldMdValueArr);
		//echo 'addedAttrVals:' . "\n";
		//print_r($addedAttrVals);
		
		$deletedAttrVals = array_diff($oldMdValueArr, $newMdValueArr);
		//echo 'deletedAttrVals:' . "\n";
		//print_r($deletedAttrVals);
		
		if ( (count($addedAttrVals) > 0) || (count($deletedAttrVals) > 0) ) {

			foreach($addedAttrVals as $addedAttrVal) {
				$addAttrKey = array_search($addedAttrVal, $multiSelectAttrOpts);

				generalLib::mesg('Adding \'' . $addedAttrVal . '\' (' . $addAttrKey . ') to ' . $multiSelectAttrName);
			}
			
			foreach($deletedAttrVals as $deletedAttrVal) {
				$delAttrKey = array_search($deletedAttrVal, $multiSelectAttrOpts);

				generalLib::mesg('deleting \'' . $deletedAttrVal . '\' (' . $delAttrKey . ') from ' . $multiSelectAttrName);
			}

			$newMdValue = implode(',', $newMdValueArr);

			$product->setData($multiSelectAttrName, $newMdValue);
			$updProd = true;
		}

		return $updProd;
	}


	//THIS FUNCTION HAS BEEN SUPERCEDED BY THE chkModelDropdownMultiSelectAttr FUNCTION ABOVE - KEPT FOR OLDER INTEGRATIONS
	//###########################
	static public function chkDropdownMultiSelectAttr($attrName, $productAttrValue, &$attrOpts,  $chkLowerCaseLabel = true) {
		$rc = true;
		//echo 'bef productAttrValue = \'' . $productAttrValue . "'\n";
		$productAttrValue = trim($productAttrValue);

		//echo 'aft productAttrValue = \'' . $productAttrValue . "'\n";
		//print_r($attrOpts);
		//die();

		//MAGENTO STORES A BLANK DROP DOWN AS A SPACE, SO CHANGE A BLANK VALUE TO A SPACE, SO THE COMPARISON WORKS CORRECTLY
		if ($productAttrValue == '') {
			$productAttrValue = ' ';
		}

		if ($chkLowerCaseLabel) {
			$chkProductAttrValue = strtolower($productAttrValue);
		} else {
			$chkProductAttrValue = $productAttrValue;
		} 
		
		//echo 'chkProductAttrValue = \'' . $chkProductAttrValue . "' \n";

		if (!array_key_exists( $chkProductAttrValue, $attrOpts)) {
			//$magAttr = Mage::getModel('eav/config')->getAttribute('catalog_product', $attrName);

			//echo 'attrName = ' . $attrName . "\n";

			$magAttr = self::$procObj->objectManager->get('\Magento\Eav\Model\Config')->getAttribute('catalog_product', $attrName);
			
			$magAttr->setData(
				'option',
				array(
					'value' => 	array(
								'option' => array($productAttrValue, $productAttrValue),
								),
				)
			);

			try {
				generalLib::mesg('Adding \'' . $productAttrValue . '\' to ' . $attrName . ' list');
				$magAttr->save();
			
				//RELOAD ATTRIBUTE - TO GET THE NEW ATTRIBUTE
				$attrOpts = self::setAttrOptions('catalog_product', $attrName);
			}
		
			catch(Exception $e) {
				generalLib::error($e->getMessage());
				generalLib::error('Cannot Add \'' . $productAttrValue . '\' to ' . $attrName . ' list');
				$rc = false;
			}
		}

		//print_r($attrOpts);

		if ($rc) {
			$newAttrVal = $attrOpts[ $chkProductAttrValue ];
		} else {
			$newAttrVal = false;
		}

		//echo 'productAttrValue = ' . $productAttrValue . "\n";
		//echo 'newAttrVal = ' . $newAttrVal . "\n";
		//die();
		
		return $newAttrVal;
	}


	//###########################
	static public function gotMagFeedLock($feedType, $lckDir = '/tmp') {
		$rc = true;

		self::$feedLkFile = $lckDir . '/' . $feedType . '.lck';

		if ( !generalLib::createProcLckFile( self::$feedLkFile ) ) {		
			generalLib::warn('Cannot process ' . $feedType . ' feed - ' . $feedType . ' import already in progress');

			$rc = false;
		}

		return $rc;
	}


	//###########################
	static public function delMagFeedLock() {
		generalLib::deleteProcLckFile( self::$feedLkFile );
	}


	//###########################
	static public function strEndsWith($str, $endStr, $strLen = null) {
		//echo 'string = ' . $string . "\n";
		//echo 'test = ' . $test . "\n";
		
	    if ( is_null($strLen) ) {
	    	$strLen = strlen($str);
		}

	    $endStrLen = strlen($endStr);

	    if ($endStrLen > $strLen) {
			return false;
		}

	    return substr_compare($str, $endStr, $strLen - $endStrLen, $endStrLen) === 0;
	}

}

?>
