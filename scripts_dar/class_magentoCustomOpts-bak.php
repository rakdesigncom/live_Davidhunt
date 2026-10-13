<?php
class magentoCustomOpts {
	static private $procObj;
	
	//###########################
	static public function customOptsConfig($procObj) {
		self::$procObj = $procObj;
	}


	//###########################
	static public function getCustomOptionValues($product, $optionTitle) {
		$optionValues = array();

		//echo "getCustomOptionValues:\n";
		//echo 'sku = ' . $product->getSku() . "\n";

		foreach ($product->getOptions() as $customOption) {

			if ($customOption->getData('title') == $optionTitle) {

				//echo 'optionTitle = ' . $optionTitle . "\n";

				$customOptionValues = $customOption->getValues();

				foreach ($customOptionValues as $customOptionValue) {
					$optionValues[] = $customOptionValue->getData();

					//print_r($customOptionValue->getData());
				}
			}
			
		}

		return $optionValues;
	}
	
	//###########################
	static public function processCustomOption($actionType, $product, $customOptionSrchField, $customOptionArr, $customOptionValueSrchField, $customOptionValueArr) {
		$updProd = false;

		//echo "\n";
		//echo "=====\n";
		//echo "processCustomOption:\n";
		//echo "customOptionArr:\n";
		//print_r($customOptionArr);

		//echo "customOptionValueArr:\n";
		//print_r($customOptionValueArr);

		//echo 'actionType = ' . $actionType . "\n";

		$gotCustomOption = false;
		$gotCustomOptionValue = false;
		$deleteCustomOption = false;
		
		$productId = $product->getId();
		
		$customOptionSrchFieldVal = $customOptionArr[ $customOptionSrchField ];
		//echo 'customOptionSrchField = ' . $customOptionSrchField . "\n";
		//echo 'customOptionSrchFieldVal = ' . $customOptionSrchFieldVal . "\n";

		if ( isset( $customOptionValueArr[ $customOptionValueSrchField ] ) ) {
			$customOptionValueSrchFieldVal = $customOptionValueArr[ $customOptionValueSrchField ];
		}
		//echo 'customOptionValueSrchField = ' . $customOptionValueSrchField . "\n";
		//echo 'customOptionValueSrchFieldVal = ' . $customOptionValueSrchFieldVal . "\n";
		
		//$customOptionSrchFieldVal = 'Cable';

		$lastOptionSortOrder = 0;
		$lastOptionValSortOrder = 0;

		foreach ($product->getOptions() as $customOption) {
			$custOptSortOrder = $customOption->getData('sort_order');

			if ($lastOptionSortOrder < $custOptSortOrder) {
				$lastOptionSortOrder = $custOptSortOrder;
			}
		}

		foreach ($product->getOptions() as $customOption) {

			//echo 'get customOptionTitle = ' . $customOption->getData($customOptionSrchField) . "\n";

			if ($customOption->getData($customOptionSrchField) == $customOptionSrchFieldVal) {
				$gotCustomOption = true;
				//echo 'found custom option ' . $customOptionSrchFieldVal . "\n";
				
				//print_r($customOption->getData());

				//FOR THE 'field' CUSTOM OPTION TYPE, THE VALUE IS HELD WITHIN THE CUSTOM OPTION ITSELF, RATHER THAN A SEPARATE VALUES RECORD
				if ($customOption->getType() == 'field') {
					$customOptionValues = array();

					if ($actionType == 'D') {
						$deleteCustomOption = true;
					} else {
						$customOptionValueArr = $customOption;

						$gotCustomOptionValue = true;
					}

				} else {
					$customOptionValues = $customOption->getValues();
				}

				//print_r($customOptionValues);
				//die();

				/*
				foreach ($customOptionValues as $customOptionValue) {
					print_r($customOptionValue->getData());
				}
				*/

				//die();

				$numCustomValues = count($customOptionValues);
				//echo 'numCustomValues = ' . $numCustomValues . ' for ' . $customOptionSrchFieldVal . "\n";

				foreach ($customOptionValues as $customOptionValue) {

					$custOptValSortOrder = $customOptionValue->getData('sort_order');

					if ($lastOptionValSortOrder < $custOptValSortOrder) {
						$lastOptionValSortOrder = $custOptValSortOrder;
					}
				}

				foreach ($customOptionValues as $customOptionValue) {

					//echo 'get customOptionValue = ' . $customOptionValue->getData($customOptionValueSrchField) . "\n";

					//FOUND CUSTOM VALUE
					if ($customOptionValue->getData($customOptionValueSrchField) == $customOptionValueSrchFieldVal) {

						if ($actionType == 'D') {
							generalLib::mesg('Deleting value for ' . $customOptionValueSrchField . ' \'' . $customOptionValueSrchFieldVal . '\' from ' . $customOptionSrchField . ' \'' . $customOptionSrchFieldVal . '\'');
							
							if ($numCustomValues > 1) {
								$updProd = true;
								$customOptionValue->delete();
							} else {
								//THE LAST CUSTOM VALUE IS TO BE REMOVED, SO REMOVE THE CUSTOM OPTION, RATHER THAN THE INDIVIDIAL VALUE
								//echo 'set custom option deletion' . "\n";
								$deleteCustomOption = true;
							}
						} else {
							//echo 'found custom option value for ' . $customOptionValueSrchFieldVal . "\n";
							$gotCustomOptionValue = true;
							break;
						}

					} else {
							//echo 'not found custom option value for ' . $customOptionValueSrchFieldVal . "\n";
					}
				}
				
				break;
			}
		}
		
		//die();
		if ($deleteCustomOption) {
			$updProd = true;
			//echo 'delete custom option' . "\n";

			if ($gotCustomOption) {
				generalLib::mesg('Deleting custom option for ' . $customOptionSrchField . ' \'' . $customOptionSrchFieldVal . '\'');
				$customOption->delete();
			} else {
				generalLib::warn('Cannot delete custom option for ' . $customOptionSrchField . ' \'' . $customOptionSrchFieldVal . '\' - does not exist');
			}
		}
		
		if ($actionType == 'D') {
			return $updProd;
		}

		/*
		if ($gotCustomOption) {
			echo "got custom option\n";
		} else {
			echo "not got custom option\n";
		}
		*/

		//die();

		if (!$gotCustomOption) {

			if ( !isset( $customOptionArr['sort_order'] ) ) {
				//echo 'setting sort order from lastOptionSortOrder = ' . $lastOptionSortOrder . "\n";
				$customOptionArr['sort_order'] = ++$lastOptionSortOrder;
			}

			$customOptionFlds = $customOptionArr;

			if (count($customOptionValueArr) > 0) {

				if ( !isset( $customOptionValueArr['sort_order'] ) ) {
					$customOptionValueArr['sort_order'] = '1';
				}

				$customOptionFlds['values'] = array($customOptionValueArr);		//CREATE THE CUSTOM OPTION VALUE AT THE SAME TIME AS THE CUSTOM OPTION
			}

			/*
			$customOptionArr = array(
			'title' => $customOptionTitle,
			'type' => $customOptionObj->inputType, // could be drop_down ,checkbox , multiple, radio
			'is_require' => $customOptionObj->isRequired,
			'sort_order' => $customOptionObj->sortOrder,
			'values' => array($this->getCustomOptionValue($devProduct, $customOptionType))
			);
			*/
										
			//echo "customOptionFlds:\n";
			//print_r($customOptionFlds);
			//die();
	
			generalLib::mesg('Adding custom option for ' . $customOptionSrchField . ' \'' . $customOptionSrchFieldVal . '\' to product id \'' . $productId . '\'');
			
			try {
				//$customOption = self::$procObj->objectManager->create('Magento\Catalog\Api\Data\ProductCustomOptionInterface');
				$customOption = self::$procObj->objectManager->create('\Magento\Catalog\Model\Product\Option');
			} 

			catch(Exception $e) {
				generalLib::error($e->getMessage());
			}

			echo "after\n";
			//die();

			//print_r($customOptionFlds);

			try {
				$product->setHasOptions(true);
				$product->setCanSaveCustomOptions(true);

				//echo 'prod id = ' . $product->getId() . "\n";
				//echo 'store id = ' . $product->getStoreId() . "\n";

				$customOption->setProductId( $product->getId() );
				$customOption->setStoreId( $product->getStoreId() );
				$customOption->addData( $customOptionFlds );
				//echo "after2\n";
				$customOption->save();
				//echo "after2.1\n";

				$product->addOption( $customOption );

				$updProd = true;
				//$product->save();

			} catch(Exception $e) {
				generalLib::error($e->getMessage());
			}

			//echo "after3\n";

			self::$procObj->objectManager->get('\Magento\Catalog\Model\Product\Option')->unsetOptions();
			//Mage::getSingleton('catalog/product_option')->unsetOptions();
        		//$product->setCanSaveCustomOptions(false);
		} else {
			//echo 'gotCustomOption = ' . $gotCustomOption . "\n";
			//echo 'gotcustomoptionvalue = ' . $gotCustomOptionValue . "\n";
			//die();
			//print_r($customOption->getData());

			$oldIsRequired = $customOption->getIsRequire();

			if ($oldIsRequired != $customOptionArr['is_require']) {
				generalLib::mesg('Updating custom option required to \'' . $customOptionArr['is_require'] . '\' for ' .
					$customOptionSrchField . ' \'' . $customOptionSrchFieldVal . '\'');

				$customOption->setIsRequire($customOptionArr['is_require']);
				//print_r($customOption->getData());

				//NOTE: IF UPDATING A DROP DOWN CUSTOM OPTIONS, THE VALUES MUST CONTAIN THE OPTION VALUES, EVRN THOUGH
				//THEY HAVE JUST BEEN LOADED. 
				//IF THEY ARE NOT INCLUDED HERE, THEN ON SAVE, A 'Select type options required values rows.' IS THROWN
				//FOR NON DROP DOWN OPTIONS, RE-SAVING THE VALUES HAS NO ILL EFFECT
				$customOptionValues = $customOption->getValues();

				$numCustomValues = count($customOptionValues);
				$savedCustomOptionValues = array();

				foreach ($customOptionValues as $customOptionValue) {
					//print_r($customOptionValue->getData());
					$savedCustomOptionValues[] =  $customOptionValue->getData();
				}

				//print_r($savedCustomOptionValues);
				$customOption->values = $savedCustomOptionValues;
				$customOption->save();
				$updProd = true;
			}

			//echo 'goCustomOptionValue = ' . $gotCustomOptionValue . "\n";
			//die();
		
			if (!$gotCustomOptionValue) {
				//$customOptionValue = Mage::getModel('catalog/product_option_value');

				$customOptionValue = self::$procObj->objectManager->create('Magento\Catalog\Model\Product\Option\Value'); 

				if ( !isset( $customOptionValueArr['sort_order'] ) ) {
					$customOptionValueArr['sort_order'] = ++$lastOptionValSortOrder;
				}
				
				$customOptionValue->setOption($customOption)
					->setStoreId('0')
					->setTitle($customOptionValueArr['title'])
					->setPrice($customOptionValueArr['price'])
					->setPriceType($customOptionValueArr['price_type'])
					->setSku($customOptionValueArr['sku'])
					->setSortOrder($customOptionValueArr['sort_order'])
					->setOptionId($customOption->getId());

				
				generalLib::mesg('Adding custom option value ' . $customOptionValueSrchField . ' \'' . $customOptionValueSrchFieldVal . '\' to custom option for ' . $customOptionSrchField . ' \'' . $customOptionSrchFieldVal . '\'');
				//print_r($customOptionValueArr);

				$customOptionValue->save();
				$updProd = true;
				//die();
			} else {
				$updCustomOptionValue = false;

				foreach($customOptionValueArr as $customOptionValueField => $customOptionValueFieldValue) {

					$oldValue = $customOptionValue->getData($customOptionValueField);

					//echo 'old val ' . $customOptionValueField  . ' value = ' . $oldValue . "\n";
					//echo 'new val = ' . $customOptionValueFieldValue . "\n";

					if ($oldValue != $customOptionValueFieldValue) {
						$customOptionValue->setData($customOptionValueField, $customOptionValueFieldValue);
						generalLib::mesg('Updating ' . $customOptionValueField . ' to \'' . $customOptionValueFieldValue . '\'');
						$updCustomOptionValue = true;
					}
				}
				
				//echo 'updCustomOptionValue = ' . $updCustomOptionValue . "\n";

				//print_r($customOptionValue->getData());

				/*
				$oldTitle = $customOptionValue->getTitle();
				$oldPrice = $customOptionValue->getPrice();
				
				//echo 'oldTitle = ' . $oldTitle . "\n";

				if ($oldTitle != $customOptionValueArr['title']) {
					generalLib::mesg('Updating custom title to \'' . $customOptionValueArr['title'] . '\' for value \'' . $oldTitle . '\'');

					$customOptionValue->setTitle($customOptionValueArr['title']);
					$updCustomOptionValue = true;
				}
					
				if ($oldPrice != $customOptionValueArr['price']) {
					generalLib::mesg('Updating custom option price to \'' . $customOptionValueArr['price'] . '\' for value \'' . $customOptionValueArr['title'] . '\'');
					$customOptionValue->setPrice($customOptionValueArr['price']);
					$updCustomOptionValue = true;
				}
				*/
				
				if ($updCustomOptionValue) {
					//SETTING THE STORE ID BEFORE SAVING A CUSTOM VALUE UPDATE IS ESSENTIAL FOR PRICE UPDATES.
					//WITHOUT IT, THE PRICE UPDATE WILL NOT BE APPLIED, ALTHOUGH OTHER CHANGES WILL BE SAVED
					//THIS IS A QUIRK IN Mage/Catalog/Model/Resource/Product/Option/Value.php LINE 82: 
					//if ($object->getStoreId() == '0') {
					//IN THE FUTURE THIS MAY BE EXPANDED SO THAT CUSTOM OPTIONS VALUES ARE STORE SPECIFIC (IF REQUIRED)
					$customOptionValue->setStoreId('0');

					generalLib::mesg('Updating custom option value \'' . $customOptionValueArr['title'] . '\'');

					//print_r($customOptionValue->getData());
					//$product->setCanSaveCustomOptions(true);
					$customOptionValue->setOption($customOption)->save();
					$updProd = true;
				}
			}
		}
		
		return $updProd;
		//return (!$gotCustomOption);
	}
	

	//###########################
	public static function saveCustomOptProd(&$product, $productSku, $reloadProd) {
		//$retArr = array();

		$rc = true;

		generalLib::mesg('Saving product sku \'' . $productSku . '\'');
				
		try {
			//THIS IS THE KEY TO MY SECOND CUSTOM OPTIONS ERROR - I NEEDED TO FIND ANOTHER FIX
			//$product->save();

			if ($reloadProd) {
				//RELOAD THE PRODUCT - VERY IMPORTANT, OTHERWISE A NEW CUSTOM OPTION WILL NOT BE RECOGNIZED FOR ADDITIONAL VALUES, AND WILL CREATE A NEW CUSTOM OPTION
				//$product = Mage::getModel('catalog/product')->load($product->getId());

				$prodKey = new stdClass();
				$prodKey->magId = $product->getId();

				$product = magentoProd::getMagProduct($prodKey, 'sku', 'sku', 'magId', true);
				echo "reloading product\n";
			}
		} 
		
		catch(Exception $e) {
			$rc = false;
			generalLib::error($e->getMessage());
			generalLib::error('Product sku \'' . $productSku . '\' not saved');
		}

		//$retArr['rc'] = $rc;
		//$retArr['product'] = $product;

		return $rc;
	}
	
}

?>
