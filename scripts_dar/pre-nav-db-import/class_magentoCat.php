<?php

class magentoCat {
	static private $prodCatUpdArr = array();
	static private $prodCatDelArr = array();
	static private $catProduct;
	static private $numProdCats;
	static private $magCatIdFld;
	static private $magProdCatIds;
	static private $updProd;
	static private $magProdSku;
	static private $procObj;
	static private $postProcessFunc = 'postProdCatApplied';
	static private $categoryLinkManagement;


	//###########################
	public static function setMagCatIdFld($procObj) {
		self::$procObj = $procObj;
		magentoCat::$magCatIdFld = self::$procObj->procConfig->catCustomAttrMappings[ self::$procObj->procConfig->extCatId ];


		if (magentoCat::$magCatIdFld == '') {
			generalLib::error('magentoCat magcatIdFld is not set');
			die();
		}

		self::$categoryLinkManagement = self::$procObj->objectManager->get('Magento\Catalog\Api\CategoryLinkManagementInterface');
	}


	//###########################
	static public function setCatPath($catType, $catId) {
		$catPath = false;

		if (is_numeric($catId)) {
			//$category = Mage::getModel('catalog/category')->load($catId);
			$category = self::$procObj->objectManager->create('Magento\Catalog\Model\Category')->load($catId);
		
			if ($category->getID()) {			
				$catPath = $category->getPath();
			} else {
				generalLib::warn('The specified ' . $catType . ' category ' . $catId . ' does not exist');
			}
		} else {
			generalLib::warn('The ' . $catType . ' category id must be a numeric value');
		}

		return $catPath;
	}


	//###########################	
	static public function getCategory($extCatIdAttr, $extCatId) {
		$category = false;
		
		//echo 'getCategory = ' . $extCatId . "\n";
		//echo 'getCategory = ' . $extCatIdAttr . "\n";
		//$matchingCategories = Mage::getModel('catalog/category')->getCollection()
								// ->addAttributeToSelect('*')
							//	->addAttributeToFilter($extCatIdAttr, $extCatId);

		$matchingCategories = self::$procObj->objectManager->create('Magento\Catalog\Model\Category')->getCollection()
								 ->addAttributeToSelect('*')
								->addAttributeToFilter($extCatIdAttr, $extCatId);
		
		foreach($matchingCategories as $matchingCategory) {
				$category =  $matchingCategory;
				print_r($category->getData());
				break;
		}	

		return $category;
	}
	

	//###########################
	public static function processProdCategories($actionType, $product, $prodCatRec) {
		$rc = true;

		print_r($prodCatRec);
		//print_r($product->getCategoryIds());
		//die();


		if ( (count( magentoCat::$prodCatUpdArr ) == 0)  && (count( magentoCat::$prodCatDelArr ) == 0) ) {
			generalLib::mesg('');
			generalLib::mesg('====');
		
			magentoCat::$catProduct = $product;

			//MAY CHANGE THIS TO BE IN AN EXTENDED CLASS AS IT'S NOT CONDITIONAL
			$postProcessFunc = 'getNumProdCats';


			if (method_exists(self::$procObj, $postProcessFunc)) {

				magentoCat::$numProdCats = self::$procObj->$postProcessFunc(magentoCat::$catProduct);

            } else {

            	//print_r($prodCatRec);

				magentoCat::$numProdCats = magentoDbFuncs::getNumProdActionTypeRecs(self::$procObj->dbObj, 
										$prodCatRec->prodCatTable, $prodCatRec->prodCatIdName, $prodCatRec->productId, 
										$prodCatRec->actionTypeName, '"U", "D"');
										//$prodCatRec->actionTypeName, '"-"');
			}

			//echo 'num = ' . magentoCat::$numProdCats . "\n";

			if (magentoCat::$numProdCats == false) {
				$rc = false;
				return $rc;
			}

        	magentoCat::$magProdCatIds = magentoCat::$catProduct->getCategoryIds();

			//echo "old cat ids\n";
			//print_r(magentoCat::$magProdCatIds);

        	magentoCat::$magProdSku = magentoCat::$catProduct->getSku(); 
			magentoCat::$updProd = false;
		}

		if ($actionType == 'D') {
			magentoCat::$prodCatDelArr[] = $prodCatRec->catId;
		} else {
			magentoCat::$prodCatUpdArr[] = $prodCatRec->catId;
		}

		$updProd = magentoCat::processProdCategory($actionType, magentoCat::$catProduct, $prodCatRec);
		
		if ($updProd && !magentoCat::$updProd) {
			magentoCat::$updProd = true;
		}

		magentoCat::$numProdCats--;

		if (magentoCat::$numProdCats == 0) {

			if (magentoCat::$updProd) {

				//echo "here\n";
				//echo 'magProdSku = ' . magentoCat::$magProdSku . "\n";

				//echo "new catids\n";
				//sort(magentoCat::$magProdCatIds);
				//print_r(magentoCat::$magProdCatIds);

				try {
					self::$categoryLinkManagement->assignProductToCategories(
						magentoCat::$magProdSku,
						magentoCat::$magProdCatIds
					);
				}

				catch(Exception $e) {
					generalLib::error($e->getMessage());
					$rc = false;
				}

				//echo "here2\n";
				//die();
			} else {
				generalLib::mesg('No product category updates to apply');
			}

			if ($rc) {
				//MAY CHANGE THIS TO BE IN AN EXTENDED CLASS AS IT'S NOT CONDITIONAL
				$postProcessFunc = 'updProdCats';

				if (method_exists(self::$procObj, $postProcessFunc)) {
					$rc = self::$procObj->$postProcessFunc(magentoCat::$catProduct);
            	} else {
					//UPDATE ALL THE PRODUCT CATEGORY RECORDS FOR THE ONE PRODUCT IN ONE GO - UPDATES AND DELETIONS
					$actTypeRec = new stdClass();
					$actTypeRec->dbObj = self::$procObj->dbObj;
					$actTypeRec->actionTypeName = $prodCatRec->actionTypeName;
					$actTypeRec->extTable = $prodCatRec->prodCatTable;
					$actTypeRec->actionTypeWhereFieldTables = array($prodCatRec->prodCatTable, $prodCatRec->prodCatTable);

					if ( isset($prodCatRec->prodCatIdType) ) {
						$prodCatIdType = $prodCatRec->prodCatIdType;
					} else {
						$prodCatIdType = 'TXT';
					}

					if (isset($prodCatRec->catIdType)) {
						$catIdType = $prodCatRec->catIdType;
					} else {
						$catIdType = 'INT';
					}

					$actTypeRec->actionTypeWhereFields = array($prodCatRec->prodCatIdName => $prodCatIdType, $prodCatRec->catIdName => $catIdType);
					$actTypeRec->actionTypeWhereFieldVals = array($prodCatRec->prodCatIdName => $prodCatRec->productId);

					//die();

					self::$procObj->dbObj->absDbBeginTransaction();

					//print_r(magentoCat::$prodCatUpdArr);
					//print_r(magentoCat::$prodCatDelArr);
					$catIds = magentoCat::getProdCatIds(magentoCat::$prodCatUpdArr, $catIdType);

					//print_r($actTypeRec);
					//echo 'catIds = ' . $catIds . "\n";	

					if ($catIds != '') {
						$actTypeRec->actionType = '-';
						$actTypeRec->actionTypeWhereFieldVals[ $prodCatRec->catIdName ] = $catIds;
						//print_r($actTypeRec->actionTypeWhereFieldVals);
						$rc = magentoDbFuncs::updActionType($actTypeRec);
					}

					if ($rc) {
						$catIds = magentoCat::getProdCatIds(magentoCat::$prodCatDelArr, $catIdType);

						if ($catIds != '') {
							$actTypeRec->actionType = 'd';
							$actTypeRec->actionTypeWhereFieldVals[ $prodCatRec->catIdName ] = $catIds;
							//print_r($actTypeRec->actionTypeWhereFieldVals);
							$rc = magentoDbFuncs::updActionType($actTypeRec);
						}
					}

					if ($rc) {
						self::$procObj->dbObj->absDbCommitTransaction();
					} else {
						self::$procObj->dbObj->absDbRollbackTransaction();
					}
				}
			}

			magentoCat::$prodCatUpdArr = array();
			magentoCat::$prodCatDelArr = array();

			magentoCat::$numProdCats = 0;
			//die();
		}

		return $rc;
	}


	//###########################
	public static function getProdCatIds($prodCatArr, $catIdType) {
		$catIds = '';

		$numProdCats = count( $prodCatArr );

		if ($numProdCats > 1) {
			$catIds = 'IN (';
		}

		foreach($prodCatArr as $prodCatId) {
			if ($catIdType == 'TXT' && $numProdCats > 1) {
				$catIds .= '"' . $prodCatId . '",';
			} else {
				$catIds .= $prodCatId . ',';
			}
		}

		$catIds = trim($catIds, ',');

		if ($numProdCats > 1) {
			$catIds .= ')';
		}

		//echo 'catIds = ' . $catIds . "\n";
		return $catIds;
	}


	//###########################
	public static function processProdCategory($actionType, $product, $prodCatRec) {
		$updProd = false;
		
		$extCatId = $prodCatRec->catId;

		//echo 'extCatId = ' . $extCatId . "\n";
		//echo 'magCatIdFld = ' . magentoCat::$magCatIdFld . "\n";
		
		//CAT_ID TO BE DEFINED BY DAR
		$category = magentoCat::getCategory( magentoCat::$magCatIdFld, $extCatId);
						 				 
		if ($category) {

			$magCatId = $category->getId();
			$prodSku = $product->getSku();
			
			if ($actionType == 'D') {

				if ( ($prodCatIdKey = array_search($magCatId, magentoCat::$magProdCatIds)) !== false ) {
					unset( magentoCat::$magProdCatIds[ $prodCatIdKey ] );
					generalLib::mesg('removing category id \'' . $magCatId . '\' ('. $extCatId . ') from product sku \'' . $prodSku . '\'');
					$updProd = true;

				} else {

					generalLib::warn('Cannot remove category id \'' . $magCatId . '\' ('. $extCatId . ') from product sku \'' . $prodSku . '\' - not found');

				}

			} else {

				if (!in_array($magCatId, magentoCat::$magProdCatIds)) {

					magentoCat::$magProdCatIds[] = $magCatId;
					generalLib::mesg('adding category id \'' . $magCatId . '\' ('. $extCatId . ') to product sku \'' . $prodSku . '\'');
					$updProd = true;

				}

			}

		} else {

			generalLib::warn('Cannot find external category id \'' . $extCatId . '\'');

		}

		return $updProd;
	}

}

?>
