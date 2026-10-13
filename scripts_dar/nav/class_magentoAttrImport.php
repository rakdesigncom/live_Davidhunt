<?php

class magentoAttrImport {
	static private $defaultAttrData = array();
	static private $defaultAttrSetName = 'Default';
	static private $defaultAttrGrpName = 'General';
	static private $prodEntityTypeId;
	static private $attrCustomAttrs;
	static private $attrCustomAttrMappings;
	static private $attributescopeMappings = array('S' => '0', 'G' => '1', 'W' => '2');

	static private $attributetypeMappings = array('BO' => 'boolean', 'DA' => 'date', 'DR' => 'select', 'MD' => 'multiselect', 'PR' => 'price', 
												'TA' => 'textarea', 'TX' => 'text');
	
	static private $allowedLayedNavigationTypes = array('DR', 'MD', 'PR');
	
	static private $allowedSearchResultsLayeredNavigation = array('DR', 'MD', 'PR');
							
	static private $validationMappings = array('DN' => 'validate-number', 'IN' => 'validate-digits', 'EM' => 'validate-email', 'UR' => 'validate-url', 
												'LE' => 'validate-alpha', 'LN' => 'validate-alphanum');
												
	static private $applyto_producttypesMappings = array('SI' => 'simple', 'GR' => 'grouped', 'CN' => 'configurable', 'VT' => 'virtual', 'BU' => 'bundled', 
												'DW' => 'downloadable');
												
	static private $customAttrTypes = array('BO' => 'B', 'DA' => 'D', 'PR' => 'T', 'TA' => 'T', 'TX' => 'T');
	
	
	static private $setCustomAttrs = array('set_name' => 'attribute_set_name');
	
	static private $grpCustomAttrs = array('set_mag_id' => 'attribute_set_id', 'grp_name' => 'attribute_group_name');
	static private $prodAttrUpdArr = array();		//STORES AN ARRAY OF PRODUCT ATTRIBUTE RECORDS FOR UPDATE PROCESSING - NEEDED FOR actiontype UPDATE
	static private $prodAttrDelArr = array();		//STORES AN ARRAY OF PRODUCT ATTRIBUTE RECORDS FOR DELETION PROCESSING - NEEDED FOR actiontype UPDATE
	static private $prodsUpd;					//STORES MAGENTO PRODUCT ID TO UPDATE - ARRAY OF ONE PRODUCT ID
	static private $attrsUpd;					//STORES AN ARRAY OF MAGENTO ATTRIBUTE CODES AND CORRESPONDING VALUES 
	static private $attrProduct;				//STORES THE PRODUCT WHERE ATTRIBUTES ARE CURRENTLY BEING PROCESSED
	static private $numProdAttrs;				//NUMBER OF PRODUCT ATTRIBUTES TO PROCESS
	static private $updProd;					//PRODUCT UPDATED WITH A CHANGE OF PRODUCT ATTRIBUTES
	static private $procObj;

	//###########################
	static public function attrConfig($procObj, $defaultAttrSetName = null, $defaultAttrGrpName = null) {
		self::$procObj = $procObj;
		
		if (!is_null($defaultAttrSetName)) {
			self::$defaultAttrSetName = $defaultAttrSetName;
		}
		
		if (!is_null($defaultAttrGrpName)) {
			self::$defaultAttrGrpName = $defaultAttrGrpName;
		}

		//self::$prodEntityTypeId = self::$procObj->objectManager->get('\Magento\Eav\Model\Config')->getEntityType(\Magento\Catalog\Api\Data\ProductAttributeInterface::ENTITY_TYPE_CODE)->getEntityTypeId();
		
		//self::$prodEntityTypeId = Mage::getModel('eav/entity')->setType('catalog_product')->getTypeId();

		//echo 'prodEntityTypeId = ' . self::$prodEntityTypeId . "\n";

		self::$prodEntityTypeId = self::$procObj->objectManager->get('\Magento\Eav\Model\Entity\Type')->loadByCode('catalog_product')->getId();

		//echo 'prodEntityTypeId = ' . self::$prodEntityTypeId->getId() . "\n";
		//echo 'prodEntityTypeId = ' . self::$prodEntityTypeId . "\n";

		//die();
		
		//$entityTypeID = Mage::getModel('catalog/product')->getResource()->getTypeId();
		
		self::$defaultAttrData = array(
			'is_global'                     => '0',	//0=store view, 1=Global, 2=website
			'frontend_input'                => 'text', //text, textarea, date, boolean, multiselect (multi-select drop down), select (drop down), price, media_image, weee (fixed product tax)
			'default_value_text'            => '',
			'default_value_yesno'           => '0',
			'default_value_date'            => '',
			'default_value_textarea'        => '',
			'is_unique'                     => '0',
			'is_required'                   => '0',
			'frontend_class'                => '',
			'is_searchable'                 => '1',
			'is_visible_in_advanced_search' => '1',
			'is_comparable'                 => '1',
			'is_used_for_promo_rules'       => '0',
			'is_wysiwyg_enabled' 			=> '0',	//APPLICABLE TO textarea ONLY
			'is_html_allowed_on_front'      => '1',
			'is_visible_on_front'           => '0',
			'used_in_product_listing'       => '0',
			'used_for_sort_by'              => '0',
			'is_configurable'               => '1',
			'is_filterable'                 => '0',
			'is_filterable_in_search'       => '0',
			'backend_type'                  => 'varchar',
			'default_value'                 => '',
			'apply_to'						=> ''
		);


		//ATTRIBUTE CUSTOM FIELDS								
		self::$attrCustomAttrs = array('attr_code' => 'T', 'attr_label' => 'T', 'attr_scope' => 'S', 
													'attr_default' => 'T',
													'attr_unique' => 'B', 'attr_required' => 'B', 'attr_validation' => 'T',
													'attr_prod_apply' => 'T', 'attr_quick_search' => 'B',
													'attr_adv_search' => 'B', 'attr_comp_frontend' => 'B',
													'attr_layered_nav' => 'B', 'attr_search_layered_nav' => 'B',
													'attr_promo_rule' => 'B', 'attr_wysiwyg' => 'B',
													'attr_htmltags_frontend' => 'B', 'attr_prodview_frontend' => 'B',
													'attr_prod_listing' => 'B', 'attr_sort_prod_listing' => 'B'
													);
		
		//ATTRIBUTE CUSTOM FIELD MAPPINGS								
		self::$attrCustomAttrMappings = array('attr_code' => 'attribute_code', 'attr_label' => 'frontend_label', 'attr_scope' => 'is_global', 
													'attr_default' => 'default_value_text',
													'attr_unique' => 'is_unique', 'attr_required' => 'is_required', 'attr_validation' => 'frontend_class',
													'attr_prod_apply' => 'apply_to', 'attr_quick_search' => 'is_searchable', 
													'attr_adv_search' => 'is_visible_in_advanced_search', 'attr_comp_frontend' => 'is_comparable',
													'attr_layered_nav' => 'is_filterable', 'attr_search_layered_nav' => 'is_filterable_in_search',
													'attr_promo_rule' => 'is_used_for_promo_rules', 'attr_wysiwyg' => 'is_wysiwyg_enabled',
													'attr_htmltags_frontend' => 'is_html_allowed_on_front', 'attr_prodview_frontend' => 'is_visible_on_front',
													'attr_prod_listing' => 'used_in_product_listing', 'attr_sort_prod_listing' => 'used_for_sort_by'
													);
	}
	
	
	//###########################
	/*
	static public function getMagAttrType($attrType, $attrValue) {
		$attrType = 'mag' . $attrType . 'Types';
		
		if (isset(self::${$attrType}[$attrValue])) {	
			$magAttrType = self::${$attrType}[$attrValue];
		} else {
			$magAttrType = false;
			generalLib::warn('Invalid attribute \'' . $attrType . '\' type \'' . $attrValue . '\'');
		}
		
		return $magAttrType;
	}
	*/
	

	//###########################
	static public function getAttrSets() {

		$attrSets = array();

		$sets = self::$procObj->objectManager->get('\Magento\Eav\Model\Entity\Attribute\Set')
							->getResourceCollection();

		foreach($sets as $set) {

			if ($set->getEntityTypeId() == self::$prodEntityTypeId ) {
				$attrSets[ $set->getAttributeSetName() ] = $set->getId();
			}
			
		}

		return $attrSets;
	}
	
	
	//###########################
	static public function getAttrSet($attrSetId, $attrSetName, $reportErrors) {
		$gotAttr = false;		
		//echo 'attrSetId = ' . $attrSetId . "\n";
		//echo 'attrSetName = ' . $attrSetName . "\n";

		//SEARCH BY ATTRIBUTE ID - IF SUPPLIED 
		if (isset($attrSetId)) {
			//$attrSet = Mage::getModel('eav/entity_attribute_set')->load($attrSetId);
			$attrSet = self::$procObj->objectManager->get('\Magento\Eav\Model\Entity\Attribute\Set')->load($attrSetId);
			
			if ($attrSet->getId()) {
				$gotAttr = true;
			}

		}

		//echo "1\n";
		//print_r($attrSet->getData());
		//die();
		//echo 'attrSetName = ' . $attrSetName . "\n";
		
		//IF NOT FOUND - SEARCH BY ATTRIBUTE NAME - IF SUPPLIED
		if (!$gotAttr && isset($attrSetName)) {
			//$sets = Mage::getModel('eav/entity_attribute_set')
			$sets = self::$procObj->objectManager->get('\Magento\Eav\Model\Entity\Attribute\Set')
							->getResourceCollection()
							->addFieldToFilter('entity_type_id', array('eq' => self::$prodEntityTypeId))
							->addFieldToFilter('attribute_set_name', array('eq' => $attrSetName));
			
			foreach($sets as $set) {
				//$attrSet = Mage::getModel('eav/entity_attribute_set')->load($set->getId());
				$attrSet = self::$procObj->objectManager->get('\Magento\Eav\Model\Entity\Attribute\Set')->load($set->getId());
				
				if ($attrSet->getId()) {
					$gotAttr = true;
					break;
				}			
			}
		}

		//echo "2\n";
		//print_r($attrSet->getData());
		//die();
		
		if (!$gotAttr) {
			$attrSet = false;
			//echo "here\n";
			
			if ($reportErrors) {
				//echo 'attrSetName = ' . $attrSetName . "\n";

				if (isset($attrSetId)) {
					generalLib::warn('Attribute set not found for attr id \'' . $attrSetId . '\'');
				}
				
				if (isset($attrSetName)) {
					generalLib::warn('Attribute set not found for attribute \'' . $attrSetName . '\'');
				}
			}		
		}

		//die();
		return $attrSet;
	}
	
	
	//###########################
	static public function getAttrGrp($attrSetId, $attrGrpId, $attrGrpName, $reportErrors) {
		$gotAttr = false;
			
		//SEARCH BY ATTRIBUTE ID - IF SUPPLIED 
		if (isset($attrGrpId)) {
			//echo "checking number\n";
			//$attrGrpRec = Mage::getModel('eav/entity_attribute_group')->load($attrGrpId);
			$attrGrpRec = self::$procObj->objectManager->get('\Magento\Eav\Model\Entity\Attribute\Group')->load($attrGrpId);

			//print_r($attrGrpRec->getData());
			
			if ($attrGrpRec->getId()) {
				$gotAttr = true;
				//echo "got on id\n";
			}
		} 
		
		//IF NOT FOUND - SEARCH BY ATTRIBUTE NAME - IF SUPPLIED - NEED TO FINISH THIS - LOOK THROUGH COLLECTION - RESTRICT BY NAME ?
		if (!$gotAttr && isset($attrGrpName)) {
			//$groups = Mage::getModel('eav/entity_attribute_group')
			//echo 'attrSetId = ' . $attrSetId . "\n";

			$groups = self::$procObj->objectManager->get('\Magento\Eav\Model\Entity\Attribute\Group')
							->getResourceCollection()
							->setAttributeSetFilter($attrSetId)
							->addFieldToFilter('attribute_set_id', array('eq' => $attrSetId))
							->addFieldToFilter('attribute_group_name', array('eq' => $attrGrpName));
							//->setSortOrder()
							//->load();
	 
			$attributeCodes = array();
			
			foreach ($groups as $group) {
				//print_r($group->getData());				
				
				//$attrGrpRec = Mage::getModel('eav/entity_attribute_group')->load($attrGrpName, 'attribute_group_name');
				 $attrGrpRec = self::$procObj->objectManager->get('\Magento\Eav\Model\Entity\Attribute\Group')->load( $group->getAttributeGroupId() );
				//print_r($attrGrpRec->getData());
				$gotAttr = true;
				//echo "got on collection\n";
				break;
			}
			
			//die();	
		}
		
		if (!$gotAttr) {
			$attrGrpRec = false;
			
			if ($reportErrors) {				
				if (isset($attrGrpId)) {
					generalLib::warn('Attribute group not found for attribute set id \'' . $attrSetId . '\' and attribute group id \'' . $attrGrpId . '\'');
				}
				
				if (isset($attrGrpName)) {
					generalLib::warn('Attribute group not found for attribute set id \'' . $attrSetId . '\' and attribute group name \'' . $attrGrpName . '\'');
				}
			}		
		}

		return $attrGrpRec;
	}
	
	
	//###########################
	static public function getAttr($attrId, $attrCode, $reportErrors) {	
		$gotAttr = false;

		//$attrId = 121;
		$attrId = '';

		//SEARCH BY ATTRIBUTE ID - IF SUPPLIED 
		if (isset($attrId)) {
			//$eavAttr = Mage::getModel('catalog/resource_eav_attribute')->load($attrId);
			$eavAttr = self::$procObj->objectManager->get('\Magento\Catalog\Model\ResourceModel\Eav\Attribute')->load($attrId);

						
			if ($eavAttr->getId()) {
				$gotAttr = true;
			}
		}
		
		//IF NOT FOUND - SEARCH BY ATTRIBUTE NAME - IF SUPPLIED
		if (!$gotAttr && isset($attrCode)) {		
				//$eavAttr = Mage::getModel('catalog/resource_eav_attribute')->loadByCode(self::$prodEntityTypeId, $attrCode);
				$eavAttr = self::$procObj->objectManager->get('\Magento\Catalog\Model\ResourceModel\Eav\Attribute')->loadByCode(self::$prodEntityTypeId, $attrCode);
				
				if ($eavAttr->getId()) {
					$gotAttr = true;
				}
		}
		
		if (!$gotAttr) {
			$eavAttr = false;
			
			if ($reportErrors) {				
				if (isset($attrId)) {
					generalLib::warn('Attribute not found for attr id \'' . $attrId . '\'');
				}
				
				if (isset($attrCode)) {
					generalLib::warn('Attribute not found for attribute \'' . $attrCode . '\'');
				}
			}		
		}

		//print_r($eavAttr->getData());
		//die();

		return $eavAttr;
	}
	
	/**
     * Create an atribute-set.
     *
     * For reference, see Mage_Adminhtml_Catalog_Product_SetController::saveAction().
     *
     * @return array|false
     */
    static public function processProdAttrSet($actionType, $attrSetRec, $copyGroupsFromSetName = -1) {
 		$rc = true;
		
		//TEMP
		//$attrSetRec->set_name = 'Dar - amended';
		//$attrSetRec->set_mag_id = '10';

		$attrSetId = $attrSetRec->set_mag_id;
		$attrSetName = $attrSetRec->set_name;
		
		if ($attrSetName == '') {
			generalLib::warn('Attribute set name cannot be blank - aborting attribute set import for external set id \'' . $attrSetRec->id . '\'');
			$rc = false;
			
			return array(
						'rc' => $rc
						);
		}
		
		$attrSet = self::getAttrSet($attrSetId, $attrSetName, false);
		
		/*
		if ($attrSet) {
			print_r($attrSet->getData());
		}
		*/	
	
		if ($actionType == 'D') {
			
			if (!$attrSet) {
				generalLib::warn('Cannot delete - no product attribute set found for \'' . $attrSetName . '\'');
			} else {
				generalLib::mesg('Deleting product attribute set for \'' . $attrSetName . '\'');
	
				try {
					$attrSet->delete();
					generalLib::mesg('Product attribute set deleted');
				} 
				
				catch (Exception $ex) {
					generalLib::error($ex->getMessage());
					generalLib::error('Product attribute set not deleted');
					$rc = false;
				}
			}
			
			return array(
						'rc' => $rc,
						'setId' => NULL
						);
		}
 		
 		if (!$attrSet) {
			generalLib::mesg('Creating attribute set \'' . $attrSetName . '\'');

			//$attrSet = Mage::getModel('eav/entity_attribute_set');
			$attrSet = self::$procObj->objectManager->create('\Magento\Eav\Model\Entity\Attribute\Set');

			$attrSet->setEntityTypeId( self::$prodEntityTypeId );
			$createSet = true;
			$updAttrSet = true;
		} else {
			generalLib::mesg('Updating attribute set \'' . $attrSetName . '\'');
			
			if ($attrSetId == '') {
				$attrSetId = $attrSet->getId();
			}
			
			$createSet = false;
			$updAttrSet = false;
		}
		
        // We don't currently support groups, or more than one level. See
        // Mage_Adminhtml_Catalog_Product_SetController::saveAction().

        //print_r($attrSet->getData());
 		

 		$updAttrs = magentoImport::processMagAttrs($attrSet, $attrSetRec, self::$setCustomAttrs);

		if ($updAttrs && (!$updAttrSet)) {
			$updAttrSet = true;
		}

        try {
	        $attrSet->validate();
	    }

    	catch(Exception $ex) {
    		generalLib::error($ex->getMessage());
    		$rc = false;
    	}
		
		//print_r($attrSet->getData());
		//die();
  
  		if ($rc && $updAttrSet) {
			try {
				generalLib::mesg('Saving attribute set \'' . $attrSetName . '\'');
				$attrSet->save();
			}
			
			catch(Exception $ex) {
				generalLib::error($ex->getMessage());
				generalLib::error('attribute set \'' . $attrSetName . '\' not saved');
				$rc = false;
			}
	 
	 		if ($rc && $attrSetId == '') {
				if (($attrSetId = $attrSet->getId()) == false) {
					generalLib::warn('Could not get ID for attribute set \'' . $attrSetName . '\'');
					$rc = false;
				}
			}
			
			//>>>> Load the new set with groups (mandatory).
	 
			// Attach the same groups from the given set-ID to the new set.
	
			if ($createSet && $rc) {
				$setGrps = false;
				
				if ($copyGroupsFromSetName !== -1) {
					$copyAttrSet = self::getAttrSet('', $copyGroupsFromSetName, true);
	
					//print_r($copyAttrSet->getData());
					
					if ($copyAttrSet) {
						generalLib::mesg('Cloning attribute groups from existing set name \'' . $copyGroupsFromSetName . '\'');
						//print_r($copyAttrSet->getData());

						//CHECK ATTRIBUTE SET FIRST - TO DO
						$attrSet->initFromSkeleton($copyAttrSet->getId());
						$setGrps = true;
					} else {
						generalLib::warn('Cannot find attribute set name \'' . $copyGroupsFromSetName . '\' for cloning - creating with default group only');
					}
				} 
				
				if (!$setGrps) {
					// Just add a default group.
					generalLib::mesg('Adding default group \'' . self::$defaultAttrGrpName . '\' to attribute set \'' .  $attrSetName . '\'');
					
					$attrGrp = self::$procObj->objectManager->create('\Magento\Eav\Model\Entity\Attribute\Group');
					$attrGrp->setAttributeGroupName(self::$defaultAttrGrpName);
					$attrGrp->setAttributeSetId($attrSetId);
		 
					// This is optional, and just a sorting index in the case of
					// multiple groups.
					$attrGrp->setSortOrder(1);
		 
					$attrSet->setGroups(array($attrGrp));
				}
						 
				// Save the final version of our set.
		 
				try {
					generalLib::mesg('Saving attribute set \'' . $attrSetName . '\'');
					$attrSet->save();
				}
				
				catch(Exception $ex) {
					generalLib::error($ex->getMessage());
					generalLib::error('Cannot save attribute set.');
					$rc = false;
				}
			} 
		} else {

			if ($rc) {
				generalLib::mesg('No updates to apply');
			}

		}
		
        return array(
						'rc' 		=> $rc,
                        'setId'     => $attrSetId
                    );
			
    }
	
	
	//###########################
	static public function processProdAttrGrp($actionType, $attrGrpRec) {
		$rc = true;

		$attrSetId = $attrGrpRec->set_mag_id;
		$attrSetName = $attrGrpRec->set_name;
		$attrGrpId = $attrGrpRec->grp_mag_id;
		$attrGrpName = $attrGrpRec->grp_name;


		if ($attrSetId == '') {
			generalLib::warn('Attribute set id cannot be blank - aborting attribute group import for external group id \'' . $attrGrpRec->grp_id . '\'');
			$rc = false;
		}
		
		if ($attrGrpName == '') {
			generalLib::warn('Attribute set group name cannot be blank - aborting attribute group import for external group id \'' . $attrGrpRec->grp_id . '\'');
			$rc = false;
		}
		
		if (!$rc) {
			return array(
						'rc' => $rc
					);
		}
		
		$attrSet = self::getAttrSet($attrSetId, $attrSetName, false);
		
		if (!$attrSet) {
			generalLib::warn('Attribute set id \'' . $attrSetId . '\' not found - aborting attribute group import for \'' . $attrGrpName . '\'');
		}
				
		$attrGrp = self::getAttrGrp($attrSetId, $attrGrpId, $attrGrpName, false);
		
	
		/*
		if ($attrGrp) {
			print_r($attrGrp->getData());
		}
		*/
		
		if ($actionType == 'D') {
			
			if (!$attrGrp) {
				generalLib::warn('Cannot delete - no product attribute group found for \'' . $attrGrpName . '\'');
			} else {
				generalLib::mesg('Deleting product attribute group for \'' . $attrGrpName . '\'');
	
				try {
					$attrGrp->delete();
					generalLib::mesg('Product attribute group deleted');
				} 
				
				catch (Exception $ex) {
					generalLib::error($ex->getMessage());
					generalLib::error('Product attribute group not deleted');
					$rc = false;
				}
			}
			
			return array(
						'rc' => $rc,
						'grpId' => NULL
			);
		}
		
		if (!$attrGrp) {
			generalLib::mesg('Creating attribute group \'' . $attrGrpName . '\' for set id ' . $attrSetId);
            
			$attrGrp = self::$procObj->objectManager->create('\Magento\Eav\Model\Entity\Attribute\Group');

			$updAttrGrp = true;
		} else {
			generalLib::mesg('Updating attribute group \'' . $attrGrpName . '\' for set id ' . $attrSetId);
			
			if ($attrGrpId == '') {
				$attrGrpId = $attrGrp->getId();
			}
			
			$updAttrGrp = false;
		}

 		$updAttrs = magentoImport::processMagAttrs($attrGrp, $attrGrpRec, self::$grpCustomAttrs);
				
		if ($updAttrs && (!$updAttrGrp)) {
			$updAttrGrp = true;
		}
		
		if ($updAttrGrp) {
			try {
				generalLib::mesg('Saving attribute group \'' . $attrGrpName . '\'');
				$attrGrp->save();
			}
			
			catch(Exception $ex) {
				generalLib::error($e->getMessage());
				generalLib::error('Cannot save attribute group \'' . $attrGrpName . '\'');
				$rc = false;
			}
	 
	 		if ($rc && $attrGrpId == '') {
				if (($attrGrpId = $attrGrp->getId()) == false) {
					generalLib::error('Could not get ID for attribute group \'' . $attrGrpName . '\'');
					$rc = false;
				}
			}
		} else {
			generalLib::mesg('No updates to apply');
		}

		return array(
					'rc' => $rc,
                    'grpId' => $attrGrpId
		);				
	}
	
	
	 /**
     * Create an attribute.
     *
     * For reference, see Mage_Adminhtml_Catalog_Product_AttributeController::saveAction().
     *
     * @return int|false
     */
     static public function processAttribute($actionType, $attrRec) {
		 $rc = true;
		 $attrId = '';

		 //TEMP
		//$attrRec->set_mag_id = '10';
		//$attrRec->grp_mag_id = '33';

		 //print_r($attrRec);
		 //die();

		//NOTE: ALL ATTRIBUTES ARE STORED IN LOWER CASE
		$oldAttrCode = $attrRec->attr_code;
		$attrRec->attr_code = trim($attrRec->attr_code, ' ');
		$attrRec->attr_code = str_replace( array(' ', '/', '&', '(',')', '-', '.'), array('_', '_', '_','','','_', ''), strtolower($attrRec->attr_code));
		$attrRec->attr_code = preg_replace('/[_]+/', '_', $attrRec->attr_code);
		

		if ($oldAttrCode != $attrRec->attr_code) {
			generalLib::warn('Incompatible attribute code: Changed from \'' . $oldAttrCode .  '\' to \'' . $attrRec->attr_code . '\'');
		}

		if ( isset( $attrRec->attr_mag_id ) ) {
			$attrMagId = $attrRec->attr_mag_id;
		} else {
			$attrMagId = '';
		}

	 	$attrCode = $attrRec->attr_code;
		$attrLabel = $attrRec->attr_label;
		 
		if ($attrCode == '') {
			generalLib::warn('Attribute code cannot be blank - aborting attribute import for external group id \'' . $attrRec->attr_id . '\'');

			$rc = false;
        }
				
		if ($attrLabel == '') {
			generalLib::warn('Attribute label cannot be blank - aborting attribute group import for external group id \'' . $attrRec->attr_id . '\'');
			$rc = false;
		}
		
		if (!$rc) {
			return array(
						'rc' => $rc
					);
		}
		
		$eavAttr = self::getAttr($attrMagId, $attrCode, false);

		//print_r($eavAttr);

		if ($actionType == 'D') {		
			if (!$eavAttr) {
				generalLib::warn('Cannot delete - no attribute for code \'' . $attrCode . '\'');
			} else {
				if ($eavAttr->getIsUserDefined() == '1') {
					generalLib::mesg('Deleting attribute for code \'' . $attrCode . '\'');

					try {
						$eavAttr->delete();
						generalLib::mesg('Attribute deleted');
					} 
				
					catch (Exception $e) {
						generalLib::error($e->getMessage());
						generalLib::error('Attribute not deleted');
						$rc = false;
					}
				} else {
					generalLib::warn('Cannot delete attribute for code \'' . $attrCode . '\' - system defined');
					$rc = false;
				}
			}
			
			return array(
						'rc' => $rc,
						'attrId' => NULL
						);
		}
				
		if (!$eavAttr) {
			generalLib::mesg('Creating attribute \'' . $attrLabel . '\' code \'' . $attrCode . '\'');

			//$eavAttr = Mage::getModel('catalog/resource_eav_attribute');
			$eavAttr = self::$procObj->objectManager->create('\Magento\Catalog\Model\ResourceModel\Eav\Attribute');


        	$eavAttr->setEntityTypeId(self::$prodEntityTypeId);
 			$eavAttr->setIsUserDefined(1);
			
			//print_r(self::$defaultAttrData);
			
			$eavAttr->addData(self::$defaultAttrData);
			
			print_r($eavAttr->getData());
			//die();
			
			//MAP ATTRIBUTE TYPE TO A MAGENTO FRONTEND INPUT TYPE - AS MAGENTO ADMIN DOES NOT ALLOW THIS FIELD TO BE SET ON ATTRIBUTE UPDATE, THIS WON'T EITHER
			if ( isset(self::$attributetypeMappings[ $attrRec->attr_type ]) ) {
				$eavAttr->setFrontendInput( self::$attributetypeMappings[ $attrRec->attr_type ] );
			} else {
				generalLib::warn('No mapping found for attribute type \'' . $attrRec->attr_type . '\' - set to default \'' . $eavAttr->getFrontendInput() . '\'');
			}
			
			//SET TO THE DEFAULT BACK END TYPE FOR THE FRONT END INPUT FIELD TYPE
			if ( isset(self::$attributetypeMappings[ $attrRec->attr_type ]) ) {
				$mappedAttrType = self::$attributetypeMappings[ $attrRec->attr_type ];

				echo 'mappedAttrType = '. $mappedAttrType . "\n";
				//$backendType = Mage::getModel('eav/entity_attribute')->getBackendTypeByInput( $mappedAttrType );
				$backendType = self::$procObj->objectManager->get('\Magento\Eav\Model\Entity\Attribute')->getBackendTypeByInput( $mappedAttrType );

				echo 'backendType = ' . $backendType . "\n";

				$eavAttr->setBackendType( $backendType );
			} else {
				$backendType = self::$defaultAttrData['backend_type'];
				generalLib::warn('Backend type cannot be determined - setting to default value of \'' . $backendType . '\'');
				$eavAttr->setBackendType( $backendType );
			}
			
			$updAttr = true;
		} else {
			generalLib::mesg('Updating attribute \'' . $attrLabel . '\' code \'' . $attrCode . '\'');
			
			if ($attrId == '') {
				$attrId = $eavAttr->getId();
			}
			
			$updAttr = false;
		}
		
		//THE IMPORT ALLOWS A DEFAULT VALUE - SET THE APPROPRIATE DEFAULT VALUE ATTRIBUTE, BASED ON THE FRONTEND INPUT TYPE
		if (isset($attrRec->default_value)) {
			//$magDefaultValueField = Mage::getModel('eav/entity_attribute')->getDefaultValueByInput($eavAttr->getFrontendInput());
			$magDefaultValueField = self::$procObj->objectManager->get('\Magento\Eav\Model\Entity\Attribute')->getDefaultValueByInput($eavAttr->getFrontendInput());

			echo 'magDefaultValueField = ' . $magDefaultValueField . "\n";

			$oldDefaultValue = $eavAttr->{'get' . $magDefaultValueField}();
			
			if ($attrRec->default_value != $oldDefaultValue) {
				$eavAttr->{'set' . $magDefaultValueField}( $attrRec->default_value );
				$updAttr = true;
			}		
		}
		
		
		/*
		//COMMENTED THIS OUT AS CONFLICTING WITH MAGENTO CUSTOM PROCESSOR - SHOULD HAVE CONFLICTED WITH M1
		$updLabel = false;
		$oldLabelArr = $eavAttr->getFrontendLabel();
		//OLD LABEL
		echo "old label\n";
		print_r($oldLabelArr);

		$newLabelArr = explode(',', $attrRec->attr_label);
		$i = 0;
		
		foreach($newLabelArr as $newLabel) {

			if ($newLabel != $oldLabelArr[ $i ]) {
				$updLabel = true;
				break;
			}

		}
		
		if ($updLabel) {
			$eavAttr->setFrontendLabel( $newLabelArr );
			$updAttr = true;
		}

		*/
		
		
		$updProdTypes = false;
		$oldMagProdTypeArr = $eavAttr->getApplyTo();
		
		//PROCESS SPECIFIC PRODUCT TYPES
		if ( isset( $attrRec->applyto_producttypes ) &&  $attrRec->applyto_producttypes != '') {
			//CONVERT EXTERNAL PRODUCT TYPES TO MAGENTO PRODUCT TYPES AND CHECK FOR ANY NEW PRODUCT TYPES
			
			$extProdTypeArr = explode(',', $attrRec->attr_prod_apply);
			$newMagProdTypeArr = array();
			
			foreach ($extProdTypeArr as $extProdType) {	
				if (isset(self::$applyto_producttypesMappings[ $extProdType ])) {
					$magProdType = self::$applyto_producttypesMappings[ $extProdType ];
					
					if (!array_key_exists($magProdType, $oldMagProdTypeArr)) {
						generalLib::mesg('Adding product type \'' . $magProdType. ' to product types');
						$oldMagProdTypeArr[] = $magProdType;
						$updProdTypes = true;
					}
					
					$newMagProdTypeArr[] = $magProdType;
				} else {
					generalLib::warn('Unable to map attribute product type value \'' . $extProdType . '\' - Excluding value');
				}
			}
		
			//REMOVE ANY MAGENTO PRODUCT TYPES NOT IN THE CURRENT PRODUCT LIST
			$magProdsToDelete = array_diff($oldMagProdTypeArr, $newMagProdTypeArr);
			
			foreach($magProdsToDelete as $magProdToDelete) {
							
				if (($magProdKey = array_search($magProdToDelete, $oldMagProdTypeArr)) !== false) {
					unset($oldMagProdTypeArr[ $magProdKey ]);
					generalLib::mesg('Removing product type \'' . $magProdToDelete . ' from product types');
					$updProdTypes = true;
				} else {
					generalLib::warn('Cannot remove product type \'' . $magProdToDelete . ' from product types - does not exist');
				}
			}
		} else {
			//REPLACE SPECIFIC PRODUCT TYPES TO BE ALL PRODUCT TYPES - BLANK ARRAY MEANS ALL PRODUCT TYPES
			if (count($oldMagProdTypeArr) > 0) {
				$oldMagProdTypeArr = array();
				$updProdTypes = true;
			}
		}
		
		if ($updProdTypes) {
			$eavAttr->setApplyTo( $oldMagProdTypeArr );
			$updAttr = true;
		}
		
		/*
        $data['frontend_label'] = array(
                                            0 => $labelText,
                                            1 => '',
                                            3 => '',
                                            2 => '',
                                            4 => '',
                                        );
 		*/

		 
 		//ASSIGN TO A ATTRIBUTE SET AND GROUP IF DETAILS PROVIDED
        if ( ( isset($attrRec->set_mag_id) && isset($attrRec->grp_mag_id) ) || 
				( isset($attrRec->set_name) && isset($attrRec->grp_name) ) ) {
			
			$updSet = false;
			
			if ( $attrSet = self::getAttrSet($attrRec->set_mag_id, $attrRec->set_name, false)) {
				$attrSetId = $attrSet->getId();
				
				if ($eavAttr->getAttributeSetId() != $attrSetId) {
					$eavAttr->setAttributeSetId($attrSetId);
					$updSet = true;
				}
			} else {
				generalLib::warn('Attribute set \'' . $attrRec->set_name . '\' does not exist - cannot set for attribute \'' . $attrCode . '\'');
				/*
				$attrSetId = self::processProdAttrSet($actionType, '', $attrRec->set_name, $attrRec->set_copy_from);
				
				if ($attrSetId != false) {
					$eavAttr->setAttributeSetId($attrSetId);
					$updSet = true;
				}
				*/
			}
			
			if ($updSet) {
				generalLib::mesg('Setting set id to \'' . $attrSetId . '\' set name \'' . $attrRec->set_name . '\'');
			}
			
			if ($attrSetId) {
				$updGrp = false;
				
				if ( ($attrGrp = self::getAttrGrp($attrSetId, $attrRec->grp_mag_id, $attrRec->grp_name, false)) ) {
					$attrGrpId = $attrGrp->getId();

					//print_r($eavAttr->getData());
					//echo 'eav grp id = ' . $eavAttr->getAttributeGroupId() . "\n";
					//echo 'attr grp id = ' . $attrGrpId . "\n";
					
					//THE GET DOES NOT WORK - Group ID NOT IN eavAttr() INVESTIGATION REQUIRED
					if ($eavAttr->getAttributeGroupId() != $attrGrpId) {
						$eavAttr->setAttributeGroupId($attrGrpId);
						$updGrp = true;
					}
				} else {
					generalLib::warn('Attribute group \'' . $attrRec->grp_name . '\' does not exist - cannot set for attribute \'' . $attrCode . '\'');

					/*
					$attrGrpId = self::processProdAttrGrp($actionType, $attrSetId, '', $attrRec->grp_name);
					
					if ($attrGrpId != false) {
						$eavAttr->setAttributeGroupId($attrGrpId);
						$updGrp = true;
					}
					*/
				}
				
				if ($updGrp) {
					generalLib::mesg('Setting group id to \'' . $attrGrpId . '\' group name \'' . $attrRec->grp_name . '\'');
				}
			}
        }
				
		if (isset($attrRec->attr_layered_nav)) {
			if ($attrRec->attr_layered_nav == 'Y' && !in_array($attrRec->attr_type, self::$allowedLayedNavigationTypes)) {
				generalLib::warn('Layered navigation not applicable to attribute type \'' . $attrRec->attr_type . '\' - setting to \'N\'');
				$attrRec->attr_layered_nav = 'N';
			}
		}
		
		if (isset($attrRec->attr_search_layered_nav)) {
			if ($attrRec->attr_search_layered_nav == 'Y' && !in_array($attrRec->attr_type, self::$allowedSearchResultsLayeredNavigation)) {
				generalLib::warn('Search Results Layered navigation not applicable to attribute type \'' . $attrRec->attr_type . '\' - setting to \'N\'');
				$attrRec->attr_search_layered_nav = 'N';
			}
		}
		
		//print_r($eavAttr->getData());

		//print_r($attrRec);
		//die();

		try {
			$updAttrs = magentoImport::processCustomAttrs('magentoAttrImport', $eavAttr, $attrRec, self::$attrCustomAttrs, self::$attrCustomAttrMappings);
		}

		catch(Exception $ex) {
					generalLib::error($ex->getMessage());
		}


		if ($updAttrs && (!$updAttr)) {
			$updAttr = true;
		}
 
 		//print_r($eavAttr->getData());
		//die();

 		if ($updAttr) {

 			$postProcessFunc = 'postAttrApplied';
				
            if (method_exists(self::$procObj, $postProcessFunc)) {
                    $rc = self::$procObj->$postProcessFunc($eavAttr);
            }

            unset($postProcessFunc);

            if ($rc) {
				try {
					generalLib::mesg('Saving Attribute \'' . $attrCode . '\'');
					$eavAttr->save();
				}

				catch(Exception $ex) {
					generalLib::error($ex->getMessage());
					generalLib::error('Attribute \'' . $attrCode . '\' could not be saved');
					$rc = false;
				}
			}
			
			if ($rc && $attrId == '') {
				if (($attrId = $eavAttr->getId()) == false) {
					generalLib::error('Could not get ID for attribute code \'' . $attrCode . '\'');
					$rc = false;
				}
			}
		} else {
			generalLib::mesg('No updates to apply');
		}
  
  		//die();

  		return array(
						'rc' 		=> $rc,
                        'attrId'     => $attrId
                    );	
    }


	//###########################
    static public function processProdAttributes($actionType, $prodAttrRec) {
    	$rc = true;

		//print_r($prodAttrRec);
				
		if ( (count( magentoAttrImport::$prodAttrUpdArr ) == 0)  && (count( magentoAttrImport::$prodAttrDelArr ) == 0) ) {

			generalLib::mesg('');
			generalLib::mesg('====');

			//$magProductId = Mage::getModel('catalog/product')->getIdBySku($prodAttrRec->pattr_product_id);

			$magProductId = self::$procObj->objectManager->get('Magento\Catalog\Model\Product')->getIdBySku( $prodAttrRec->pattr_product_id );
		
			if (!is_numeric($magProductId)) {
				generalLib::warn('Cannot find product for SKU \'' . $prodAttrRec->pattr_product_id . '\'');
				$rc = false;
				return $rc;
			}

			//echo 'getProductForPartNum: product id = ' . $productId . "\n";	
			$prodObj = new stdClass();
			$prodObj->sku = $prodAttrRec->pattr_product_id;
			$prodObj->magId = $magProductId;

			//print_r($prodObj);
		
			magentoAttrImport::$attrProduct = magentoProd::getMagProduct($prodObj, 'sku', 'sku', 'magId', true);
		
			if (!magentoAttrImport::$attrProduct) {
				$rc = false;
			}

			if ($rc) {

				$postProcessFunc = 'getNumProdAttrs';

				if (method_exists(self::$procObj, $postProcessFunc)) {

					magentoAttrImport::$numProdAttrs = self::$procObj->$postProcessFunc( magentoAttrImport::$attrProduct);

            	} else {
					$query = 'SELECT COUNT(*) FROM ' . $prodAttrRec->prodAttrTable . ' pa INNER JOIN '. $prodAttrRec->attrTable . ' a on pa.pattr_attr_id = a.attr_id WHERE pa.pattr_action_type IN ("U", "D") AND pattr_product_id = "' . $prodAttrRec->pattr_product_id . '"'; 

					//echo 'query = ' . $query . "\n";

					$result = self::$procObj->dbObj->absDbQuery($query);


					while($queryResult = self::$procObj->dbObj->absDbFetchArray($result)) {
						print_r($queryResult);
						magentoAttrImport::$numProdAttrs = $queryResult[0];
						break;
					}

					//echo 'num = ' . magentoAttrImport::$numProdAttrs . "\n";

					//TEMP
					//magentoAttrImport::$numProdAttrs = 1;
				}

				if (magentoAttrImport::$numProdAttrs == false) {
					$rc = false;
				}
			}

			magentoAttrImport::$updProd = false;
			magentoAttrImport::$prodsUpd = array( $magProductId );
			magentoAttrImport::$attrsUpd = array();

			if (!$rc) {
				return $rc;
			}
		}


		if ($actionType == 'D') {
			magentoAttrImport::$prodAttrDelArr[] = $prodAttrRec->pattr_attr_id;
		} else {
			magentoAttrImport::$prodAttrUpdArr[] = $prodAttrRec->pattr_attr_id;
		}

		//print_r(magentoAttrImport::$prodAttrUpdArr);

		//NOTE: ALL ATTRIBUTES ARE STORED IN LOWER CASE
		$oldAttrCode = $prodAttrRec->attr_code;
		$prodAttrRec->attr_code = trim($prodAttrRec->attr_code, ' ');
		$prodAttrRec->attr_code = str_replace( array(' ', '/', '&', '(',')'), array('_', '_', '_','',''), strtolower($prodAttrRec->attr_code));
		$prodAttrRec->attr_code = preg_replace('/[_]+/', '_', $prodAttrRec->attr_code);
		
		//$prodAttrRec->pattr_value = utf8_encode($prodAttrRec->pattr_value);

		if ($oldAttrCode != $prodAttrRec->attr_code) {
			generalLib::warn('Incompatible attribute code: Changed from \'' . $oldAttrCode .  '\' to \'' . $prodAttrRec->attr_code . '\'');
		}

		

		$updProd = magentoAttrImport::processProdAttrValue($actionType, magentoAttrImport::$attrProduct, $prodAttrRec);
		
		if ($updProd && !magentoAttrImport::$updProd) {
			magentoAttrImport::$updProd = true;
		}

		magentoAttrImport::$numProdAttrs--;

		//echo 'numProdAttrs = ' .  magentoAttrImport::$numProdAttrs . "\n";

		if (magentoAttrImport::$numProdAttrs == 0) {
			$productSku = magentoAttrImport::$attrProduct->getSku();

			if (magentoAttrImport::$updProd) {
				$postProcessFunc = 'postProdAttrApplied';
				
                if (method_exists(self::$procObj, $postProcessFunc)) {
                        $retArr = self::$procObj->$postProcessFunc(magentoAttrImport::$attrProduct, magentoAttrImport::$prodAttrUpdArr);

                        $rc = $retArr['rc'];

                        //THIS IS ONLY USEFUL IF THE magentoAttrImport::$updProd IS COMMENTED OUT - THIS IS ONLY DONE WHEN FIXING DATA IN $postProcessFunc
						if ( (!magentoAttrImport::$updProd) && $retArr['updProd'] == true) {
							magentoAttrImport::$updProd = true;
						} 
                }
		//die();

                unset($postProcessFunc);
                
				//print_r(magentoAttrImport::$attrProduct->getColour());
				//die();

				//print_r(magentoAttrImport::$prodsUpd);

				//print_r(magentoAttrImport::$attrsUpd);
				//die();

                if ($rc) {

					try {
						generalLib::mesg('Saving product sku \'' . $productSku . '\'');

						self::$procObj->objectManager->create('\Magento\Catalog\Model\ResourceModel\Product\Action')->updateAttributes(magentoAttrImport::$prodsUpd, magentoAttrImport::$attrsUpd, '0');
					}

					catch(Exception $e) {
						$rc = false;
						generalLib::error($e->getMessage());
						generalLib::error('Product sku \'' . $productSku . '\' status not saved');
					}

				}

				/*
                if ($rc) {
					try {
							generalLib::mesg('Saving product sku \'' . $productSku . '\'');
							magentoAttrImport::$attrProduct->save();
					}
						
					catch(Exception $e) {
						generalLib::error($e->getMessage());
						generalLib::error('Product sku \'' . $productSku . '\' not saved');
						$rc = false;
					}
				}
				*/
			}

			//die();

			if ($rc) {

				//MAY CHANGE THIS TO BE IN AN EXTENDED CLASS AS IT'S NOT CONDITIONAL
				$postProcessFunc = 'updProdAttrs';

				if (method_exists(self::$procObj, $postProcessFunc)) {

					$rc = self::$procObj->$postProcessFunc(magentoAttrImport::$attrProduct);

            	} else {
					//UPDATE ALL THE PRODUCT ATTRIBUTE RECORDS FOR THE ONE PRODUCT IN ONE GO
					$actTypeRec = new stdClass();
					$actTypeRec->dbObj = self::$procObj->dbObj;
					$actTypeRec->actionTypeName = 'pattr_action_type';
					$actTypeRec->extTable = $prodAttrRec->prodAttrTable;
					$actTypeRec->actionTypeWhereFieldTables = array($prodAttrRec->prodAttrTable, $prodAttrRec->prodAttrTable);
					$actTypeRec->actionTypeWhereFields = array('pattr_product_id' => 'TXT', 'pattr_attr_id' => 'INT');
					$actTypeRec->actionTypeWhereFieldVals = array('pattr_product_id' => $prodAttrRec->pattr_product_id);

					$rc = self::$procObj->dbObj->absDbBeginTransaction();

					if ($rc) {
						$attrIds = magentoAttrImport::getProdAttrIds(magentoAttrImport::$prodAttrUpdArr);

						if ($attrIds != '') {
							$actTypeRec->actionType = '-';
							$actTypeRec->actionTypeWhereFieldVals[ 'pattr_attr_id' ] = $attrIds;
							//print_r($actTypeRec);
							$rc = magentoDbFuncs::updActionType($actTypeRec);
						}

						if ($rc) {
							$attrIds = magentoAttrImport::getProdAttrIds(magentoAttrImport::$prodAttrDelArr);

							if ($attrIds != '') {
								$actTypeRec->actionType = 'd';
								$actTypeRec->actionTypeWhereFieldVals[ 'pattr_attr_id' ] = $attrIds;
								//print_r($actTypeRec);
								$rc = magentoDbFuncs::updActionType($actTypeRec);
							}
						}

						if ($rc) {
							$rc = self::$procObj->dbObj->absDbCommitTransaction();
						} else {
							self::$procObj->dbObj->absDbRollbackTransaction();
							$rc = false;
						}

					}
					//die();
				}
			}

			magentoAttrImport::$prodAttrUpdArr = array();
			magentoAttrImport::$prodAttrDelArr = array();
			magentoAttrImport::$numProdAttrs = 0;
		}

		return $rc;
	}


	//###########################
	public static function getProdAttrIds($prodAttrArr) {
		$attrIds = '';

		$numProdAttrs = count( $prodAttrArr );

		if ($numProdAttrs > 1) {
			$attrIds = 'IN (';
		}

		foreach($prodAttrArr as $prodAttrId) {
			$attrIds .= $prodAttrId . ',';
		}

		$attrIds = trim($attrIds, ',');

		if ($numProdAttrs > 1) {
			$attrIds .= ')';
		}

		return $attrIds;
	}
	
	
	//###########################
	static public function processProdAttrValue($actionType, $product, $prodAttrRec) {
		$updProd = false;
		
		$productSku = $product->getSku();
		
		if ($actionType == 'D') {
			$prodAttrRec->pattr_value = '';	//OVERWRITE VALUE
		}

		if (isset(self::$customAttrTypes[ $prodAttrRec->attr_type ])) {	
			$prodAttrRec->{	$prodAttrRec->attr_code } = $prodAttrRec->pattr_value; //NEEDED FOR processCustomAttrs() TO GET THE ATTRIBUTE VALUE
			
			$retArr = magentoImport::processCustomAttrsRetArr(self::$procObj, $product, $prodAttrRec, 
										array($prodAttrRec->attr_code => self::$customAttrTypes[ $prodAttrRec->attr_type ]) );


			$updProd = $retArr['recUpdated'];

			if ($updProd) {
				magentoAttrImport::$attrsUpd[ $prodAttrRec->attr_code ] = $retArr['newVal'];
			}

		} else {
			switch($prodAttrRec->attr_type) {
				case 'DR':
							$dropDownOpts = magentoImport::setAttrOptions('catalog_product', $prodAttrRec->attr_code);

							$updProd = magentoimport::chkDropdownAttr($product, $prodAttrRec->attr_code, $prodAttrRec->pattr_value, $dropDownOpts);

							if ($updProd) {
								magentoAttrImport::$attrsUpd[ $prodAttrRec->attr_code ] = $dropDownOpts[ strtolower( $prodAttrRec->pattr_value ) ];
							}

							break;
							
				case 'MD':
							$multiSelectOpts = magentoImport::setAttrOptions('catalog_product', $prodAttrRec->attr_code);

							$prodAttrValArr = explode(',', $prodAttrRec->pattr_value);

							$oldMdValue = $product->getData($prodAttrRec->attr_code);
							//echo "oldMdValue: type= " .gettype($oldMdValue) . "\n";
							//print_r($oldMdValue);

							//echo "prodAttrValArr: \n";
							//print_r($prodAttrValArr);

							$newMdValueArr = array();

							foreach ($prodAttrValArr as $prodAttrVal) {
								//echo 'prodAttrVal = ' . $prodAttrVal . "\n";

								magentoimport::chkMultiSelectAttr($product, $prodAttrRec->attr_code, $prodAttrVal, $multiSelectOpts, $newMdValueArr);
								//echo "After Upd:\n";
							}

							$newMdValue = implode(',', $newMdValueArr);

							//echo "newMdValue = " . $newMdValue . "\n";

							if ($newMdValue != $oldMdValue) {
								$product->setData($prodAttrRec->attr_code, $newMdValue);
								//print_r($product->getColour());
								$updProd = true;
							}

							if ($updProd) {
								magentoAttrImport::$attrsUpd[ $prodAttrRec->attr_code ] = $newMdValue;
							}

							break;
			}		
		}
		
		return $updProd;
	}
	
	
	//###########################
	//CALLED FROM magentoImport::processCustomAttrs()
	static public function process_attr_scope_Field($fieldVal, &$rc) {
		
		if (isset(self::$attributescopeMappings[$fieldVal])) {
			$fieldVal = self::$attributescopeMappings[$fieldVal];
		} else {
			generalLib::warn('Unable to map attribute scope value \'' . $fieldVal . '\' - keeping old value');
			$rc = false;
		}
		
		return $fieldVal;
	}
	
	
	//###########################
	//CALLED FROM magentoImport::processCustomAttrs()	
	static public function process_validation_Field($fieldVal, &$rc) {
		if (isset(self::$validationMappings[$fieldVal])) {
			$fieldVal = self::$validationMappings[$fieldVal];
		} else {
			generalLib::warn('Unable to map attribute validation value \'' . $fieldVal . '\' - keeping old value');
			$rc = false;
		}
		
		return $fieldVal;
	}
}
?>
