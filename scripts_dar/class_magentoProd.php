<?php
class magentoProd {
	static private $prodAttrs;
	static private $procObj;

	//###########################
	static public function prodConfig($procObj) {
		self::$procObj = $procObj;
	}


	//###########################
	static public function getAttrsForProd($product) {
		self::$prodAttrs = Mage::getModel('eav/config')->getEntityAttributeCodes(Mage_Catalog_Model_Product::ENTITY, $product);
	}


	//###########################
	static public function attrInProd($attrName) {
		$gotAttr = false;

		if ( in_array($attrName, self::$prodAttrs) ) {
		    $gotAttr = true;
		}

		return $gotAttr;
	}


	//###########################
	static public function getMagStoreProduct($prodData, $storeId, $extProdIdField, $extProdIdAttr, $intProdIdField, $reportErrors) {
		return self::_getMagProduct($prodData, $storeId, $extProdIdField, $extProdIdAttr, $intProdIdField, $reportErrors);
	}


	//###########################
	static public function getMagProduct($prodData, $extProdIdField, $extProdIdAttr, $intProdIdField, $reportErrors) {
		return self::_getMagProduct($prodData, '', $extProdIdField, $extProdIdAttr, $intProdIdField, $reportErrors);
	}


	//###########################
	static public function _getMagProduct($prodData, $storeId, $extProdIdField, $extProdIdAttr, $intProdIdField, $reportErrors) {
		$gotProduct = false;

		//echo 'intProdIdField = ' . $intProdIdField . "\n";
		//print_r($prodData);

		if ( isset($prodData->$intProdIdField) && ( !empty($prodData->$intProdIdField) ) ) {
			//generalLib::mesg('Product \'' . $intProdIdField . '\' selection');
			$magProdId = (string)$prodData->$intProdIdField;
			
			//echo 'magprodId  = ' . $magProdId . "\n";

			if ($storeId != '') {
				//$product = Mage::getModel('catalog/product')->setStoreId($storeId)->load($magProdId);

				//echo "reading for store id " . $storeId . "\n";
				$product = self::$procObj->objectManager->create('Magento\Catalog\Model\Product')->setStoreId($storeId)->load($magProdId);
				//$product = self::$procObj->objectManager->create('\Magento\Catalog\Model\ProductRepository')->getById($magProdId, true, $storeId, true);
				//$product = self::$procObj->objectManager->create('Magento\Catalog\Api\ProductRepositoryInterface')->getById($magProdId, true, $storeId, true);
			} else {
				//$product = Mage::getModel('catalog/product')->load($magProdId);

				$product = self::$procObj->objectManager->create('Magento\Catalog\Model\Product')->load($magProdId);
			}		
			
			if ($product->getId()) {
				$gotProduct = true;
			}

		} else {

			if ($storeId != '') {
				generalLib::error('Can only select product by store id via the Magento ID - re-try with magento id'); 
				return false;
			}	

		}

		if (!$gotProduct) {

			if (isset($prodData->$extProdIdField)) {
				//generalLib::mesg('Product \'' . $extProdIdField . '\' selection');
				$extProdId = (string)$prodData->$extProdIdField;
				//echo 'extProdId = ' . $extProdId . "\n";
				//echo 'extProdIdAttr = ' . $extProdIdAttr . "\n";

				//I FOUND THAT TRYING TO LOAD A PRODUCT FOR A SPECIFIC STORE DID NOT WORK HERE WHEN LOADING
				//BY ATTRIBUTE OR WHEN RE-LOADING THE PRODUCT BY MAGENTO ID AFTER LOADING BY ATTRIBUTE
				//$product = Mage::getModel('catalog/product')->loadByAttribute($extProdIdAttr, $extProdId);

				$product =self::$procObj->objectManager->create('Magento\Catalog\Model\Product')->loadByAttribute($extProdIdAttr, $extProdId);

				if ($product) {
					//$product = Mage::getModel('catalog/product')->load($product->getId());

					$product = self::$procObj->objectManager->create('Magento\Catalog\Model\Product')->load($product->getId());
					
					if ($product->getId()) {
						$gotProduct = true;
					}
				}
			} else {
				generalLib::error('No internal or external product IDs set'); 
			}

		}
				
		if (!$gotProduct) {
			$product = false;
			
			if ($reportErrors) {				
				if (isset($magProdId)) {
					generalLib::warn('Product not found for product id \'' . $magProdId . '\'');
				}
				
				if (isset($extProdId)) {
					generalLib::warn('Product not found for attribute \'' . $extProdIdAttr . '\' value \'' . $extProdId . '\'');
				}
			}		
		}
		
		return $product;
	}

	
	//###########################
	static public function processLinkedProduct($actionType, $masterProd, $linkType, $extProdIdField, $intProdIdField, $linkedProdAttrValue, $position = NULL) {
		//echo "processLinkedProduct:\n";

		$updProd = false;

		$prodObj = new stdClass();
		
		if (!is_null($intProdIdField)) {		
			$prodObj->$intProdIdField = $linkedProdAttrValue;
		} else {
			$prodObj->$extProdIdField = $linkedProdAttrValue;
		}
		
		//print_r($prodObj);

		$linkedProduct = magentoProd::getMagProduct($prodObj, $extProdIdField, $extProdIdField, $intProdIdField, true);
		
		if (!$linkedProduct) {
			return $updProd;
		}
		
		$linkedProductSku = $linkedProduct->getSku();

		//echo 'linkedProductSku = ' . $linkedProductSku . "\n";
	
		//GET CURRENT LIST OF ASSOCIATED PRODUCTS FOR THE GROUPED PRODUCT
		
		$linkedProducts = array();
		
		//$currLinkedProds = $masterProd->getRelatedProductCollection();
		$currLinkedProds = $masterProd->getProductLinks();
		//print_r($currLinkedProds);

		//$currLinkedProds = $masterProd->getRelatedProducts();
		$lnkCnt = 0;

		//GET CURRENT LIST OF LINKED PRODUCTS FOR THE MASTER PRODUCT
		foreach ( $currLinkedProds as $currLinkedProd ) {
			//print_r($currLinkedProd->getData());
			//echo 'currSku = ' . $currLinkedProd->getSku() . "\n";
			//echo 'curr Link ProductSku = ' . $currLinkedProd->getLinkedProductSku() . "\n";
			//echo 'currLinkType = ' . $currLinkedProd->getLinkType() . "\n";
			//echo 'currPos = ' . $currLinkedProd->getPosition() . "\n";

			$linkedProducts[] = $currLinkedProd;

			if ($currLinkedProd->getLinkType() == $linkType) {
				$lnkCnt++;
			}

    	}
	
		//die();

		$linkedSkuIdx = -1;

		//echo 'link type = ' . $linkType . "\n";

		//echo 'num linked products = ' . count($linkedProducts) . "\n";

		foreach ( $linkedProducts as $linkedIdx => $linkedProduct ) {

			if ( ( $linkedProduct->getLinkedProductSku() == $linkedProductSku ) && ( $linkedProduct->getLinkType() == $linkType ) ) {
				$linkedSkuIdx = $linkedIdx;
			}
		}
		
		if ($actionType == 'D') {

			if ($linkedSkuIdx != -1) {

				//echo 'linkedSkuIdx = ' . $linkedSkuIdx . "\n";

				unset( $linkedProducts[ $linkedSkuIdx ] );

				generalLib::mesg('Removing ' . $linkType . ' product sku \'' . $linkedProductSku . '\' from linked products');
				$posIdx = 0;

				foreach ( $linkedProducts as $lnkIdx => $linkedProduct) {

					if ($linkedProducts[ $lnkIdx ]->getlinkType() == $linkType) {
						$posIdx++;

						if ($linkedProducts[ $lnkIdx ]->getPosition() != $posIdx) {
							$linkedProducts[ $lnkIdx ]->setPosition( $posIdx );
						}

					}

				}

				$updProd = true;
			} else {
				generalLib::warn('Cannot remove ' . $linkType . ' product id \'' . $linkedProductSku . '\' - not a linked product');
			}

		} else {

			//echo 'linkedSkuIdx = ' . $linkedSkuIdx . "\n";

			if ($linkedSkuIdx == -1) {
				++$lnkCnt;

				if (!is_null($position)) {

					$newPos = $position;

					if ($position >= $lnkCnt) {
						$newPos = $lnkCnt;
					} 
					
				} else {
					$newPos = $lnkCnt;
				}

				$linkData = self::$procObj->objectManager->create('\Magento\Catalog\Api\Data\ProductLinkInterfaceFactory')->create();

				$linkData->setSku( $masterProd->getSku() );
				$linkData->setLinkedProductSku( $linkedProductSku );
				$linkData->setLinkType( $linkType );
				$linkData->setPosition( $newPos );
				$linkedProducts[] = $linkData;

				generalLib::mesg('Adding ' . $linkType . ' product id \'' . $linkedProductSku . '\' to linked products');
				$updProd = true;
			}
		}
		
		if ($updProd) {
			//echo 'aft num linked products = ' . count($linkedProducts) . "\n";

			$masterProd->setProductLinks( $linkedProducts );
		}
		
		return $updProd;
	}
	
	
	//###########################
	static public function processProdDeletion($product, $extProdId) {
		$rc = true;
		
		if (!$product) {
			generalLib::warn('Cannot delete - no product for external product id: ' . $extProdId);
			return $rc;		//flag as deleted
		}
		
		generalLib::mesg('Deleting product with SKU: ' . $product->getSku() . ' product ID ' .  $product->getId());
		
		try {
			$product->delete();
			generalLib::mesg('Product deleted');
		} 
		
		catch (Exception $e) {
			generalLib::error($e->getMessage());
			generalLib::error('Product not deleted');
			$rc = false;
		}	
		
		return $rc;
	}
}
?>
