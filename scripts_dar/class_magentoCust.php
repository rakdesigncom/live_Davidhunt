<?php
class magentoCust {
	const BILLING = 'B';
	const SHIPPING = 'S';
	const BILLING_AND_SHIPPING = 'BS';
	const DEFAULT_COUNTRY_ID = 'GB';
	static private $mandAddrFields = array('firstname', 'lastname', 'street', 'city', 'postcode', 'telephone');
	static private $procObj;

	//###########################
	static public function CustConfig($procObj) {
		self::$procObj = $procObj;
	}

	//###########################
	static public function getMagCustomer($custObj, $extCustIdField, $extCustIdAttr, $intCustIdField, $reportErrors) {
		$gotCustomer = false;
		
		if (isset($custObj->$intCustIdField)) {
			generalLib::mesg('Customer \'' . $intCustIdField . '\' selection');
			$magCustId = (string)$custObj->$intCustIdField;
					
			//$customer = Mage::getModel('customer/customer')->load($magCustId);

			$customer = self::$procObj->objectManager->create('Magento\Customer\Model\Customer')->load($magCustId);

			//$customer = self::$procObj->objectManager->create('Magento\Customer\Model\CustomerFactory')->load($customer->getId());
			
			if ($customer->getId()) {
				$gotCustomer = true;
			}
		}
		
		if (!$gotCustomer) {
			if (isset($custObj->$extCustIdField)) {
				generalLib::mesg('Customer \'' . $extCustIdField . '\' selection');
				$extCustId = (string)$custObj->$extCustIdField;

				//echo 'extCustId = ' . $extCustId . "\n";
				//echo 'extCustIdAttr = ' . $extCustIdAttr . "\n";

				//$customer = Mage::getModel('customer/customer')
				$customer = self::$procObj->objectManager->create('Magento\Customer\Model\Customer')
						->getCollection()
						->addAttributeToSelect($extCustIdAttr)
						->addAttributeToFilter($extCustIdAttr, $extCustId)
                                                ->addAttributeToFilter('store_id', 2)
						->getFirstItem();


				if ($customer->getId()) {
					//print_r($customer->getData());

					//$customer = Mage::getModel('customer/customer')->load($customer->getId());

					$customer = self::$procObj->objectManager->create('Magento\Customer\Model\Customer')->load($customer->getId());

					//$customer = self::$procObj->objectManager->create('Magento\Customer\Model\CustomerFactory')->load($customer->getId());
					
					if ($customer->getId()) {
						$gotCustomer = true;
					}
				}
			} else {
				generalLib::error('No internal or external customer IDs set'); 
			}
		}
				
		if (!$gotCustomer) {
			$customer = false;
			
			if ($reportErrors) {				
				if (isset($magCustId)) {
					generalLib::warn('Customer not found for customer id \'' . $magCustId . '\'');
				}
				
				if (isset($extCustId)) {
					generalLib::warn('Customer not found for attribute \'' . $extCustIdAttr . '\' value \'' . $extCustId . '\'');
				}
			}		
		}
		
		return $customer;
	}
	
	
	//###########################
	static public function getCustGroupId($custNo) {
		$rc = true;
		
		$custNoGroup = self::$procObj->objectManager->create('\Magento\Customer\Model\Group')->load($custNo, 'customer_group_code');
		
		if (!($custGroupId = $custNoGroup->getCustomerGroupId())) {
			$custNoGroup = self::$procObj->objectManager->create('\Magento\Customer\Model\Group');
			$custNoGroup->setCustomerGroupCode($custNo);
			$custNoGroup->setTaxClassId(3);					//RETAIL CUSTOMER
				
			generalLib::mesg('Creating customer group \'' . $custNo . '\'');
								
			try {
				$custNoGroup->save();
				generalLib::mesg('Saved customer group');
			} catch(Exception $e){
				generalLib::error($e->getMessage());
				generalLib::error('Customer group not saved');
				$rc = false;	
				$custGroupId = false;
			}
			
			if ($rc) {
				$custGroupId = $custNoGroup->getId();
			}
		}

		if ($custGroupId != false) {
			//$this->chkCustGroupId($custNo, $custGroupId, $custNo);	
			//$this->chkWebUsersGroupId($custNo, $custGroupId, $custNo);
		}
		
		return $custGroupId;
	}
	
	
	//###########################
	static public function processCustomerAddress($actionType, $_customer, $dataFeed, $feedFields, $custAddrSettings) {
		$addrUpdated = false;
		$gotDefaultBillingAddress = false;
		$gotDefaultShippingAddress = false;
		$foundAddr = false;
		$updAddr = false;

		$custId = $_customer->getId();
		
		$defaultBillingAddr = $_customer->getDefaultBillingAddress();
		
		if ($defaultBillingAddr != '') {
        	$defaultBillingEntityId = $defaultBillingAddr->getData('entity_id');
		} else {
			$defaultBillingEntityId = '';
		}
		
		if ($defaultBillingEntityId == $custId) {
			$oldDefaultBilling = true;
		} else {
			$oldDefaultBilling = false;
		}
		
		
		$defaultShippingAddr = $_customer->getDefaultShippingAddress();
		
		if ($defaultShippingAddr != '') {
        	$defaultShippingEntityId = $defaultShippingAddr->getData('entity_id');
		} else {
			$defaultShippingEntityId = '';
		}
		
		if ($defaultShippingEntityId == $custId) {
			$oldDefaultShipping = true;
		} else {
			$oldDefaultShipping = false;
		}
		
		
		$custAddrs = $_customer->getAddresses();
		
		//IF A customerAddrId IS SUPPLIED THEN JUST DELETE THE CUSTOMER ADDRESS WITH THAT ID, OTHERWISE DELETE ALL THE ADDRESSES FOR THIS CUSTOMER
		foreach ($custAddrs as $custAddr) {
			
			if ($custAddrSettings->customAddrId != '') {
				$customAddrId = $custAddr->getCustomAddrId();
			} else {
				$customAddrId = '';
			}
			
			if ($customAddrId == $custAddrSettings->customAddrId) {
				$custAddrEntityId = $custAddr->getEntityId();
				
				$customerAddress = Mage::getModel('customer/address')->load($custAddrEntityId);
				
				if ($actionType == 'D') {
					generalLib::mesg('Deleting address ' . $customAddrId . ' for customer ' . $custId);
					$customerAddress->delete();
				} else {
					$foundAddr = true;
					//print_r($custAddr->getData());
					break;
				}
			}
		}
		
		//echo 'foundAddr = ' . $foundAddr . "\n";
		//die();
		
		if ($actionType == 'D') {
			return;
		}
		
		$defaultBilling = false;
		$defaultShipping = false;

		switch($custAddrSettings->addrType) {
			case magentoCust::BILLING:
								//IF NO DEFAULT BILLING OR SHIPPING ADDRESS, MAKE THIS BILLING ADDRESS BOTH DEFAULT ADDRESSES
								if ($defaultBillingEntityId == '' && $defaultShippingEntityId == '') {
									$defaultBilling = true;
									$defaultShipping = true;
								} else {
									//IF THERE IS NO DEFAULT BILLING ADDRESS, MAKE THIS BILLING ADDRESS THE DEFAULT BILLING ADDRESS
									if ($defaultBillingEntityId == '') {
										$defaultBilling = true;
									}
								}
								break;
								
			case magentoCust::SHIPPING:
								//IF THE BILLING ADDRESS IS THE DEFAULT SHIPPING ADDRESS, MAKE THIS SHIPPING ADDRESS THE DEFAULT SHIPPING ADDRESS
								if ($defaultBillingEntityId == $defaultShippingEntityId) {
									$defaultShipping = true;
								} else {
									//IF THERE IS NO DEFAULT SHIPPING ADDRESS, MAKE THIS SHIPPING ADDRESS THE DEFAULT
									if ($defaultShippingEntityId == '') {
										$defaultShipping = true;
									}
								}	
								break;	
								
			default:		
							$defaultBilling = true;
							$defaultShipping = true;
							break;
 
		}
		
		if (($defaultBilling != $oldDefaultBilling) || ($defaultShipping != $oldDefaultShipping)) {
			generalLib::mesg('Default billing or shipping address setting changed - set to update');
			$updAddr = true;
		}
		
		$_customerAddress = array();
		
		$streetArr = array();
												
		foreach($feedFields as $feedField => $magField) {
			if (isset($dataFeed->$feedField)) {
				$fieldVal = (string)$dataFeed->$feedField;
				
				if (strpos($magField, 'street') !== false) {
					$strCnt = count($streetArr);
					
					if ($strCnt == 0 || ($strCnt > 0 && $fieldVal != '')) {
						$streetArr[] = $fieldVal;
					}
					
					$magField = 'street';
					$fieldVal = $streetArr;
				}
				
				$_customerAddress[$magField] = $fieldVal;
			}
		}
		
		if (count($_customerAddress) == 0) {
			generalLib::mesg('No Address details supplied to update');
			return;
		}
		
		if (!array_key_exists('country_id', $_customerAddress)) {
			$_customerAddress['country_id'] = $custAddrSettings->defaultCountryId;
			generalLib::mesg('Country ID set - set to update');
			$updAddr = true;
		}

		try {
			$regionCollection  = Mage::getModel('directory/region_api')->items($_customerAddress['country_id']);
		} catch(Exception $e) {
			generalLib::error('region not found for country id \'' . $_customerAddress['country_id'] . '\'');
		}

		//IF THERE IS A REGION COLLECTION - DO NOT IMPORT 'REGION' AS IT'S NOT APPLICABLE, SINCE REGION IS SET VIA 'REGION_ID'
        if ($regionCollection && isset($_customerAddress['region'])) {
			unset($_customerAddress['region']);
        }
				
		if ($foundAddr && (!$updAddr)) {
			foreach($feedFields as $feedField => $magField) {
				$oldVal = $custAddr->getData($magField);
				
				if (isset($_customerAddress[$magField])) {
					$fieldVal = (string)$dataFeed->$feedField;
					
					if ($magField != 'street') {
						if ($oldVal != $fieldVal) {
							//echo "old val $magField = $oldVal\n";
							//echo "new val = $fieldVal\n";
							generalLib::mesg('Address details changed - set to update');
							$updAddr = true;
						}
					}
				}
			}
		}
		
		if (!$updAddr) {
			$oldStreetArr = $custAddr->getStreet();

			if (count($oldStreetArr) != count($_customerAddress['street'])) {
				//print_r($oldStreetArr);
				//print_r($_customerAddress['street']);
				//echo "changed street arr\n";
				generalLib::mesg('Number of address street lines has changed - set to update');
				$updAddr = true;
			} else {
				foreach($oldStreetArr as $streetCnt => $oldStreetVal) {
					//echo "old val $magField = $oldStreetVal\n";
					//echo 'new val = ' . $_customerAddress['street'][$streetCnt] . "\n";
					
					if ($_customerAddress['street'][$streetCnt] != $oldStreetVal) {
						generalLib::mesg('street address has changed - set to update');
						$updAddr = true;
					}
				}
			}
		}
		
		//$_customerAddress = get_object_vars($custAddrObj);
		//unset($_customerAddress['_addlData']);
		
		//print_r($_customerAddress);
		
		if (!$foundAddr) {
			generalLib::mesg('Address not found - set to update');

			$customerAddress = Mage::getModel('customer/address');
			
			$customerAddress
				->setCustomerId($custId)
				->setSaveInAddressBook('1');
			;
			
			$updAddr = true;
		}
		
		$customerAddress
			->setIsDefaultBilling($defaultBilling)
			->setIsDefaultShipping($defaultShipping);	
		
		if (!$foundAddr) {		
			$_customer->addAddress($customerAddress);
		}
		
		if ($updAddr) {
			try {
				$customerAddress
					->addData($_customerAddress)
					->save(); 
					
				generalLib::mesg('Address saved');
			} catch(Exception $e){
				//Mage::log('Address Save Error::' . $e->getMessage());
				$updAddr = false;
				generalLib::error('Address not saved');
			}
		} else {
			generalLib::mesg('No Address updates to apply');
		}
		
		return $updAddr;	
	}
	
	
	//###########################
	static public function processCustomerAddressNew($actionType, $_customer, $dataFeed, $feedFields, $custAddrSettings) {
		$rc = true;

		$magCustId = $_customer->getId();
		//echo 'magCustId = ' . $magCustId . "\n";

		$retArr = self::setNewCustAddr($actionType, $dataFeed, $feedFields, $custAddrSettings, $magCustId);

		$rc = $retArr['rc'];

		if (!$rc) {
			return $rc;
		}

		$updAddr = $retArr['updAddr'];
		$newCustAddr = $retArr['newCustAddr'];

		if ( !isset($custAddrSettings->customAddrIdName) ) {
			$custAddrSettings->customAddrIdName = 'custom_addr_id';
		}

		if ( !isset($custAddrSettings->customAddrIdSetByMagento) ) {
			$custAddrSettings->customAddrIdSetByMagento = false;
		}

		$foundAddr = false;
		$gotAddrMatch = false;

		$custAddrs = $_customer->getAddresses();

		/*
		foreach ($custAddrs as $custAddr) {								
			echo "custAddr:\n";
			print_r($custAddr->getData());
		}
		*/

		$custAddrId = '';

		//IF A CUSTOM ADDRESS ID IS SPECIFIED
		if ($custAddrSettings->customAddrId != '') {
			
			//LOOK FOR AN ADDRESS WITH A MATCHING CUSTOM ADDRESS ID
			foreach ($custAddrs as $custAddr) {	

				if ($custAddr->getData( $custAddrSettings->customAddrIdName ) == $custAddrSettings->customAddrId) {				
					//$customerAddress = Mage::getModel('customer/address')->load($custAddr->getEntityId());
					$customerAddress = $custAddr;
					$custAddrId = $custAddr->getId();
					$foundAddr = true;
					//print_r($custAddr->getData());
					break;
				}
			}

			//FOR INTALLATIONS WHERE A CUSTOM ADDRESS ID IS USED, THIS IS POSSIBLE WHERE AN ADDRESS HAS BEEN ADDED VIA THE WEB, AND IS UPDATED FROM EXTERNAL DATA, PROVDING A CUSTOM ADDRESS ID
			if (!$foundAddr) {
				foreach ($custAddrs as $custAddr) {
					if (!self::addrUpd($feedFields, $dataFeed, $custAddr, $newCustAddr)) {
						$customerAddress = self::$procObj->objectManager->create('Magento\Customer\Model\Address')->load($custAddr->getEntityId());
						$custAddrId = $custAddr->getEntityId();
						$gotAddrMatch = true;
						break;
					}
				}
			}
		} else {

			if (!$custAddrSettings->customAddrIdSetByMagento) {
				//IF NO CUSTOM ADDRESS ID IS NOT SPECIFIED

				//CATCH 22 ISSUE - HOW TO INDENTIFY ADDRESSES WITH NO CUSTOM ADDRESS ID - OLD DEFAULT SETTING NEEDS $custAddrID, BUT TO SET THAT YOU NEED THE DEFAULT ADDRESSES
				//THE FOLLOWING CODE WILL WORK IF YOU ONLY HAVE ONE BILLING AND SHIPPING ADDRESS OR A COMBINED BILLING/SHIPPING ADDRESS
				//WHAT IF YOU HAVE ONE BILLING ADDRESS AND MULTIPLE SHIPPING ADDRESSES

				switch($custAddrSettings->addrType) {
					case magentoCust::BILLING:
					case magentoCust::BILLING_AND_SHIPPING:			//DEFAULT BILLING AND SHIPPING ADDRESS ARE THE SAME
										if ($defaultBillingEntityId != '') {
											//$customerAddress = Mage::getModel('customer/address')->load($defaultBillingEntityId);
											$customerAddress = self::$procObj->objectManager->create('Magento\Customer\Model\Address')->load($defaultBillingEntityId);
											$custAddrId = $customerAddress->getId();
											$foundAddr = true;
										}
										break;
										
					case magentoCust::SHIPPING:
										if ($defaultShippingEntityId != '') {
											//$customerAddress = Mage::getModel('customer/address')->load($defaultShippingEntityId);
											$customerAddress = self::$procObj->objectManager->create('Magento\Customer\Model\Address')->load($defaultShippingEntityId);
											$custAddrId = $customerAddress->getId();
											$foundAddr = true;
										}
										break;
				}

				echo "address:\n";
				print_r($customerAddress->getData());
			}

		}

		//ADDRESS NOT FOUND, BUT GOT AN ADDRESS MATCH - INDICATE FOUND
		if (!$foundAddr && $gotAddrMatch) {
			$foundAddr = true;
		}

		//echo 'foundAddr = ' . $foundAddr . "\n";
		//die();

		//echo "Customer Address found:\n";
		//print_r($customerAddress->getData());
		
		if ($actionType == 'D') {
			$custDelCust = ' for magento customer id \'' . $magCustId . '\' and address type \'' . $custAddrSettings->addrType . '\'';
			
			if ($custAddrSettings->customAddrId != '') {
				$custDelAddrId = ' and custom addr id \'' . $custAddrSettings->customAddrId . '\'';
			} else {
				$custDelAddrId = '';
			}

			if ($foundAddr) {
				generalLib::mesg('Deleting address' . $custDelCust . $custDelAddrId);

				try {
					$customerAddress->delete();
				} catch(Exception $e){
					generalLib::error($e->getMessage());
					generalLib::error('Address not deleted' . $custDelCust . $custDelAddrId);
					$rc = false;
				}

			} else {				
				generalLib::warn('Cannot delete - no customer address found' . $custDelCust . $custDelAddrId);
			}
			
			return $rc;
		}

		$retArr = self::getDefaultAddr($_customer, 'B', $custAddrId, $custAddrSettings);

		$defaultBillingEntityId = $retArr['defaultAddrEntityId'];
		$oldDefaultBilling = $retArr['oldDefaultAddr'];


		$retArr = self::getDefaultAddr($_customer, 'S', $custAddrId, $custAddrSettings);
		$defaultShippingEntityId = $retArr['defaultAddrEntityId'];
		$oldDefaultShipping = $retArr['oldDefaultAddr'];

		//echo 'defaultBillingEntityId = ' . $defaultBillingEntityId . "\n";

		//echo 'defaultShippingEntityId = ' . $defaultShippingEntityId . "\n";
		
		//print_r($newCustAddr);
		
		$defaultBilling = false;
		$defaultShipping = false;

		switch($custAddrSettings->addrType) {
			case magentoCust::BILLING:
								//IF STATED AS BEING THE DEFAULT BILLING ADDRESS
								if (isset($custAddrSettings->defaultAddr)) {
									$defaultBilling = $custAddrSettings->defaultAddr;
								} else {
									//IF NO DEFAULT BILLING OR SHIPPING ADDRESS, MAKE THIS ADDRESS BOTH THE DEFAULT BILLING AND SHIPPING ADDRESS
									if ($defaultBillingEntityId == '' && $defaultShippingEntityId == '') {
										$defaultBilling = true;
										$defaultShipping = true;
									} else {
										//IF THERE IS NO DEFAULT BILLING ADDRESS, MAKE THIS THE DEFAULT BILLING ADDRESS
										if ($defaultBillingEntityId == '') {
											$defaultBilling = true;
										}
									}
								}
								break;
								
			case magentoCust::SHIPPING:
								//IF STATED AS BEING THE DEFAULT SHIPPING ADDRESS
								if (isset($custAddrSettings->defaultAddr)) {
									$defaultShipping = $custAddrSettings->defaultAddr;
								} else {
									//IF THE BILLING ADDRESS IS THE SAME AS THE DEFAULT SHIPPING ADDRESS, MAKE THIS ADDRESS THE DEFAULT SHIPPING ADDRESS
									if ($defaultBillingEntityId == $defaultShippingEntityId) {
										$defaultShipping = true;
									} else {
										//IF THERE IS NO DEFAULT SHIPPING ADDRESS, MAKE THIS ADDRESS THE DEFAULT SHIPPING ADDRESS
										if ($defaultShippingEntityId == '') {
											$defaultShipping = true;
										}
									}
								}
								break;	
			
			case magentoCust::BILLING_AND_SHIPPING:
			default:
								$defaultBilling = true;
								$defaultShipping = true;
								break; 
		}
		
		//echo 'defbilling = ' . $defaultBilling . "\n";
		//echo 'olddefbilling = ' . $oldDefaultBilling . "\n";

		if ($defaultBilling != $oldDefaultBilling) {

			if ($defaultBilling) {
				generallib::mesg('Set to default billing address');
			} else {
				generallib::mesg('No longer the default billing address');
			}

			$updAddr = true;
		}

		//echo 'defshipping = ' . $defaultShipping . "\n";
		//echo 'olddefshipping = ' . $oldDefaultShipping . "\n";

		if ($defaultShipping != $oldDefaultShipping) {

			if ($defaultShipping) {
				generallib::mesg('Set to default shipping address');
			} else {
				generallib::mesg('No longer the default shipping address');
			}

			$updAddr = true;
		}
				
		//IF ADDRESS NOT FOUND FOR THIS CUSTOMER - CREATE A NEW ADDRESS
		if (!$foundAddr) {
			generalLib::mesg('Creating address for magento customer id \'' . $magCustId . '\'');
			//$customerAddress = Mage::getModel('customer/address');
			$customerAddress = self::$procObj->objectManager->create('Magento\Customer\Model\Address');
			
			$customerAddress
				->setCustomerId($magCustId)
				->setSaveInAddressBook('1');
			
			$updAddr = true;
		} else {
			generalLib::mesg('Updating address for magento customer id \'' . $magCustId . '\'');

			//NOTE: NOT WORTH CHECKING FOR UPDATING ADDRESS FIELDS IF GOT AN ADDR MATCH, AS THE DETAILS WILL BE THE SAME
			if ((!$updAddr) && (!$gotAddrMatch)) {
				$updAddr = self::addrUpd($feedFields, $dataFeed, $customerAddress, $newCustAddr);
			}
		}
		
		$customerAddress
			->setIsDefaultBilling($defaultBilling)
			->setIsDefaultShipping($defaultShipping);	
		
		if (!$foundAddr) {		
			$_customer->addAddress($customerAddress);
		}
		
		print_r($newCustAddr);

		if ($updAddr) {
			try {
				$customerAddress
					->addData($newCustAddr)
					->save(); 
					
				generalLib::mesg('Address saved');
			} catch(Exception $e){
				generalLib::error($e->getMessage());
				generalLib::error('Address not saved for magento customer id \'' . $magCustId . '\'');
				$rc = false;
			}

			if (!$foundAddr && $custAddrSettings->customAddrIdSetByMagento) {
				$newCustAddr[ $custAddrSettings->customAddrIdName ] = $customerAddress->getId();

				generalLib::mesg('Updating custom address id to \'' . $newCustAddr[ $custAddrSettings->customAddrIdName ] . '\'');

				try {
					$customerAddress
						->addData($newCustAddr)
						->save(); 
					
					generalLib::mesg('Address saved');
				} catch(Exception $e){
					generalLib::error($e->getMessage());
					generalLib::error('Address not saved for magento customer id \'' . $magCustId . '\'');
					$rc = false;
				}

			}
		} else {
			generalLib::mesg('No Address updates to apply');
		}
		
		if ($custAddrSettings->customAddrIdSetByMagento) {
			$retArr = array();
			$retArr['rc'] = $rc;
			$retArr[ $custAddrSettings->customAddrIdName ] = $newCustAddr[ $custAddrSettings->customAddrIdName ];

			return $retArr;
		} else {
			return $rc;
		}
	}


	//###########################
	static private function setNewCustAddr($actionType, $dataFeed, $feedFields, $custAddrSettings, $magCustId) {
		$retArr = array();

		$rc = true;

		$updAddr = false;

		//POPULATE NEW ADDRESS ARRAY
		$newCustAddr = array();
		
		$streetArr = array();
												
		foreach($feedFields as $feedField => $magField) {
			if (isset($dataFeed->$feedField)) {
				$newVal = (string)$dataFeed->$feedField;
				
				if (strpos($magField, 'street') !== false) {
					$strCnt = count($streetArr);
					
					if ($strCnt == 0 || ($strCnt > 0 && $newVal != '')) {
						$streetArr[] = $newVal;
					}
					
					$magField = 'street';
					$newVal = $streetArr;
				}
				
				$newCustAddr[$magField] = $newVal;
			}
		}

		//print_r($newCustAddr);
		
		if ($rc) {
			if ( (count($newCustAddr) == 0) && ($actionType != 'D') ) {
				generalLib::mesg('No Address details supplied to update');
				$rc = false;
			}
		}
		

		if ($rc) {
			if ( isset($custAddrSettings->chkMandFields) ) {

				if ($custAddrSettings->chkMandFields) {
					$gotMandFields = true;

					foreach(magentoCust::$mandAddrFields as $mandAddrField) {

						if (!array_key_exists($mandAddrField, $newCustAddr)) {
							generalLib::warn('No \'' . $mandAddrField . '\' mandatory address field for magento cust id \'' . $magCustId . '\'');
							$gotMandFields = false;
						} else {
							if ($newCustAddr[$mandAddrField] == '') {
								generalLib::warn('Blank \'' . $mandAddrField . '\' mandatory address field for magento cust id \'' . $magCustId . '\'');
								$gotMandFields = false;
							}
						}

					}

					if (!$gotMandFields) {
						generalLib::warn('skipping address');
						$rc = false;
					}

				}
			}
		}


		if ($rc) {
			if ( isset($newCustAddr['country_name']) ) {
				if ( ($countryId = magentoCust::getCountryCode( $newCustAddr['country_name'] )) != '') {
					generallib::mesg('Replacing Country name \'' . $newCustAddr['country_name'] . '\' with country id \'' . $countryId . '\'');
					$newCustAddr['country_id'] = $countryId;
					unset($newCustAddr['country_name']);
				} else {
					generallib::warn('Country \'' . $newCustAddr['country_name'] . '\' not found - skipping address for magento cust id \'' . $magCustId . '\'');
					$rc = false;
				}
			}
		}

		if (!$rc) {
			$retArr['rc'] = $rc;
			return $rc;
		}

		//print_r($newCustAddr);

		$gotRegionId = true;

		if (!array_key_exists('region_id', $newCustAddr)) {
			$gotRegionId = false;
		} else {
			if ($newCustAddr['region_id'] == '') {
				$gotRegionId = false;
			}
		}

		$gotCountryId = true;

		if (!array_key_exists('country_id', $newCustAddr)) {
			$gotCountryId = false;
		} else {
			if ($newCustAddr['country_id'] == '') {
				$gotCountryId = false;
			}
		}

		if (!$gotCountryId) {
			//IF NO COUNTRY ID SPECIFIED - TRY AND DETERMINE IT FROM THE COUNTRY NAME (IF PRESENT)
			if ( isset($newCustAddr['country_name']) ) {

				if ( ($countryId = magentoCust::getCountryCode( $newCustAddr['country_name'] )) != '') {
					generallib::mesg('Replacing Country name \'' . $newCustAddr['country_name'] . '\' with country id \'' . $countryId . '\'');
					$newCustAddr['country_id'] = $countryId;
					unset($newCustAddr['country_name']);
					$gotCountryId = true;
				} else {
					generallib::warn('Country \'' . $newCustAddr['country_name'] . '\' not found');
				}
			}

			//IF STILL NOT FOUND - APPLY DEFAULT
			if (!$gotCountryId) {
				//IF STILL NOT FOUND - APPLY SPECIFIED DEFAULT - IF SET
				if ( isset($custAddrSettings->defaultCountryId) ) {
					$newCustAddr['country_id'] = $custAddrSettings->defaultCountryId;
					generalLib::mesg('Country ID not specified - Setting to default country \'' . $custAddrSettings->defaultCountryId . '\'');
				} else {
					//IF STILL NOT FOUND - APPLY CLASS DEFAULT
					$newCustAddr['country_id'] = magentoCust::DEFAULT_COUNTRY_ID;
					generalLib::mesg('Country ID and default not specified - Setting to constant default country \'' . magentoCust::DEFAULT_COUNTRY_ID . '\'');
				}

				$gotCountryId = true;
			}

			$updAddr = true;
		}
	
		//print_r($newCustAddr);
		//die();
		
		try {
			//$regionCollection  = Mage::getModel('directory/region_api')->items($newCustAddr['country_id']);
			$regionCollection = self::$procObj->objectManager->create('Magento\Directory\Model\Country')->setId( $newCustAddr['country_id'] )->getLoadedRegionCollection()->toOptionArray();

			//print_r($regionCollection);
		} catch(Exception $e) {
			generalLib::error($e->getMessage());
			generalLib::error('region not found for country id \'' . $newCustAddr['country_id'] . '\'');
		}

		//IF THERE IS A REGION COLLECTION - DO NOT IMPORT 'REGION' AS CANNOT BE APPLIED, SINCE REGION IS SET VIA 'REGION_ID' THAT SETS A DROPDOWN
        if ($regionCollection && isset($newCustAddr['region'])) {
			unset($newCustAddr['region']);
        }


        $gotRegionId = true;

		if (!array_key_exists('region_id', $newCustAddr)) {
			$gotRegionId = false;
		} else {
			if ($newCustAddr['region_id'] == '') {
				$gotRegionId = false;
			}
		}

		if (!$gotRegionId && $gotCountryId) {

			if ( isset($newCustAddr['region_code']) ) {

				//$region = Mage::getModel('directory/region')->loadByCode( $newCustAddr['region_code'], $newCustAddr['country_id'] );

				$region = self::$procObj->objectManager->create('Magento\Directory\Model\Region')->loadByCode( $newCustAddr['region_code'], $newCustAddr['country_id'] );
		
				//print_r($region->getData());

				if ( is_numeric($region->getId()) ) {
					$newCustAddr['region_id'] = $region->getId();
					generalLib::mesg('Replacing Region Code \'' . $newCustAddr['region_code'] . '\' with Region ID \'' . $newCustAddr['region_id'] . '\'');
					unset($newCustAddr['region_code']);
					$updAddr = true;
				}
			}
		}


		if (!array_key_exists('region_id', $newCustAddr) && isset($newCustAddr['region']) && $gotCountryId) {
			$lcRegion = strtolower($newCustAddr['region']);

			//$regionCollection = Mage::getModel('directory/country')->load( $newCustAddr['country_id'] )->getRegions();

			$regionCollection = self::$procObj->objectManager->get('Magento\Directory\Model\Country')->setId( $newCustAddr['country_id'] )->getLoadedRegionCollection();

			foreach($regionCollection as $region) {
				//echo $region->getName() . "\n";
				//print_r($region->getData());

				if ( strtolower($region->getName()) == $lcRegion) {
					$newCustAddr['region_id'] = $region->getId();
					generalLib::mesg('Replacing Region name \'' . $newCustAddr['region'] . '\' with Region ID \'' . $newCustAddr['region_id'] . '\'');
					unset($newCustAddr['region']);
					$updAddr = true;
					break;
				}

			}
		}

		$retArr['rc'] = $rc;
		$retArr['newCustAddr'] = $newCustAddr;
		$retArr['updAddr'] = $updAddr;

		return $retArr;
	}

	
	//###########################
	static public function addrUpd($feedFields, $dataFeed, $oldCustAddr, $newCustAddr) {
		//HAS THE ADDRESS (EXCLUDING 'street') BEEN UPDATED? - IF SO, FLAG THE ADDRESS AS UPDATED

		//NOTE: MAGENTO HAS A FLAW IN THAT ADDRESS FIELDS NOT ADDED TO THE RECORD ARE NOT RETURNED WHEN QUERIED, UNLIKE OTHER MODELS
		//WHERE ALL FIELDS ARE PRESENT, YOU DO NOT HAVE TO REFER TO THEM IN ORDER FOR THEM TO BE PART OF THE MODEL RECORD.
		//EVEN IF A VALUE IS SUPPLIED LATER, AS THE FIELD WAS NOT ADDED AS PART OF THE ORIGINAL RECORD, THERE IS NO OLD VALUE FOR COMPARISON.
		//THIS IS WHY THE OLD CUSTOMER ADDRESS RECORDS ARE PLACED IN AN ARRAY, TO SEE IF THE FIELD EXISTS. IF NOT, SET THE OLD VALUE TO 'NOT IN OLD ADDRESS',
		//SO THE UPDATE IS TRIGGERED.

		//FOR EXAMPLE, WITH NAV, BLANK FIELDS ARE NOT SUPPLIED, SO THEREFORE WILL NOT BE ADDED TO THE MAGENTO ADDESS RECORD.
		//THIS ALSO HAS A SIDE EFFECT IN THAT THE LAST VALUE USED FOR THAT FIELD ON ANOTHER RECORD IS APPLIED IN IT'S PLACE.
		
		$updAddr = false;

		//echo "oldCustAddr:\n";
		$oldCustAddrArr = $oldCustAddr->getData();
		//print_r($oldCustAddrArr);

		//echo "newCustAddr:\n";
		//print_r($newCustAddr);

		foreach($feedFields as $feedField => $magField) {

			if (array_key_exists($magField, $oldCustAddrArr)) {
			//if (isset($oldCustAddr[$magField])) {
				$oldVal = $oldCustAddr->getData($magField);
			} else {
				//THIS WILL TRIIGER AN UPDATE
				$oldVal = 'NOT IN OLD ADDRESS';
			}
			
			if (isset($newCustAddr[$magField])) {

				if ( isset( $dataFeed->$feedField ) ) {
					$newVal = (string)$dataFeed->$feedField;
					
					if ($magField != 'street') {
						if ($oldVal != $newVal) {
							//echo "old val $magField = $oldVal\n";
							//echo "new val = $newVal\n";
							$updAddr = true;
						}
					}

				}
				
			}
		}
		
		if (!$updAddr) {
			//HAS THE STREET BEEN UPDATED? - IF SO, FLAG THE ADDRESS AS UPDATED
			$updAddr = self::streetUpd($oldCustAddr->getStreet(), $newCustAddr['street']);
		}
		
		return $updAddr;
	}
		
	
	//###########################
	static public function streetUpd($oldStreetArr, $newStreetArr) {
		$updStr = false;
		
		if (count($oldStreetArr) != count($newStreetArr)) {
			//print_r($oldStreetArr);
			//print_r($newStreetArr);
			//echo "changed street arr\n";
			$updStr = true;
		} else {
			foreach($oldStreetArr as $streetCnt => $oldStreetVal) {				
				if ($newStreetArr[$streetCnt] != $oldStreetVal) {
					//echo 'new val = ' . $newStreetArr[$streetCnt] . "\n";
					$updStr = true;
				}
			}
		}
		
		return $updStr;
	}
	

	//###########################
	static public function getCountryCode($countryName) {
		$countryName = ucwords( strtolower($countryName) );

		$countryCode = Zend_Locale_Data_Translation::$regionTranslation[$countryName];

		//echo 'countryCode = ' . $countryCode . "\n";

		/*
		if ($countryCode != '') {
			$countryId = Mage::getModel('directory/country')->loadByCode($countryCode);
			print_r($countryId);
		}	
		*/

		return $countryCode;
	}


	//###########################
	static public function getDefaultAddr($customer, $defAddrType, $custAddrId, $custAddrSettings) {
		$retArr = array();

		if ($defAddrType == 'B') {
			$defaultAddr = $customer->getDefaultBillingAddress();
			//$defaultAddr = $customer->getDefaultBilling();
		} else {
			$defaultAddr = $customer->getDefaultShippingAddress();
		}

		//echo "past def\n";
		//print_r($defaultAddr);
	
		if ($defaultAddr != '') {
			//print_r($defaultBillingAddr->getData());
			$defaultAddrEntityId = $defaultAddr->getData('entity_id');
		} else {
			$defaultAddrEntityId = '';
		}


		if ($custAddrSettings->customAddrId != '' && $defaultAddr != '') {

			if ($defaultAddr->getData( $custAddrSettings->customAddrIdName ) == $custAddrSettings->customAddrId) {				
				$oldDefaultAddr = true;
			} else {
				$oldDefaultAddr = false;
			}

		} else {
			//echo 'defaultAddrEntityId = ' . $defaultAddrEntityId . "\n";
			//echo 'custAddrId = ' . $custAddrId . "\n";

			if ($defaultAddrEntityId == $custAddrId) {
				$oldDefaultAddr = true;
			} else {
				$oldDefaultAddr = false;
			}

		}

		$retArr['oldDefaultAddr'] = $oldDefaultAddr;
		$retArr['defaultAddrEntityId'] = $defaultAddrEntityId;

		return $retArr;
	}

	
	//###########################
	static public function processCustDeletion($websiteId, $extCustId, $_customer) {
		$rc = true;

		if ($extCustId == NULL || !$_customer) {
			generalLib::error('Cannot delete - no customer with cust id: ' . $extCustId);
			$rc = false;
		} else {
			generalLib::mesg('Deleting customer: id ' . $_customer->getId() . ' ('. $extCustId . ') - ' . $_customer->getEmail() . ' for website \'' . $websiteId . '\'');
	
			try {
				$_customer->delete();
				generalLib::mesg('Customer deleted');
			} 
			
			catch (Exception $e) {
				generalLib::error($e->getMessage());
				generalLib::error('Cannot delete customer: id ' . $_customer->getId() . ' ('. $extCustId . ') - ' . $_customer->getEmail() . ' for website \'' . $websiteId . '\'');
				$rc = false;
			}		
		}

		return $rc;
	}
}
?>
