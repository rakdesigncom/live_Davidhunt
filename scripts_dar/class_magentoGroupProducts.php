<?php
class magentoGroupProducts {
	static private $procObj;


	//###########################
	static public function grpProdConfig($procObj) {
		self::$procObj = $procObj;
	}

	
	//###########################
	static public function processGroupedProductForProductMasterList($grpProduct, $extProdIdField, $extProdIdAttr, $intProdIdField, $oldAssocProdIds, $procAssocProdIds, $delGrpProdIfNoAssocProds) {
		//ALL THE ASSOCIATED PRODUCTS ARE GIVEN IN $procAssocProdIds AND THE OLD ONES IN $oldAssocExtProdIds - THE CURRENT ASSOCIATED PRODUCTS LIST IS UPDATED TO MATCH $procAssocProdIds
		return self::processGroupedProductForProduct($grpProduct, $extProdIdField, $extProdIdAttr, $intProdIdField, $oldAssocProdIds, $procAssocProdIds, null, $delGrpProdIfNoAssocProds);
	}
	
	
	//###########################
	static public function processGroupedProductForSingleProduct($grpProduct, $extProdIdField, $extProdIdAttr, $intProdIdField, $procAssocProdIds, $actionType, $delGrpProdIfNoAssocProds) {
		//ONLY ONE ASSOCIATED PRODUCT IS SPECIFIED IN $procAssocProdIds, WHICH IS EITHER ADDED OR DELETED TO/FROM THE CURRENT ASSOCIATED PRODUCTS LIST, ACCORDING TO $actionType
		return self::processGroupedProductForProduct($grpProduct, $extProdIdField, $extProdIdAttr, $intProdIdField, '', $procAssocProdIds, $actionType, $delGrpProdIfNoAssocProds);
	}

/*
namespace Magento\GroupedProduct\Model\Product\Link\CollectionProvider;

class Grouped implements \Magento\Catalog\Model\ProductLink\CollectionProviderInterface
{
    public function getLinkedProducts(\Magento\Catalog\Model\Product $product)
    {
        return $product->getTypeInstance()->getAssociatedProducts($product);
    }
}
*/

	//###########################
	static public function processGroupedProductForProduct($grpProduct, $extProdIdField, $extProdIdAttr, $intProdIdField, $oldAssocProdIds, $procAssocProdIds, $actionType, $delGrpProdIfNoAssocProds) {
		$rc = true;
		$updGrpProd = false;
		$keepGrpProd = true;

		//$actionType = 'D';
		//echo 'got to grouped products - id = ' . $grpProduct->getId() . "\n";

		$linkedProducts = array();

		$assocProducts = array();

		$relCnt = 1;

		$lnkCnt = 0;

		if ($grpProduct->getId() != '') {

			$linkedProducts = $grpProduct->getProductLinks();

			$lnkCnt = count( $linkedProducts );

			//echo 'lnkCnt = ' . $lnkCnt . "\n";
			//print_r($linkedProducts);
			//die();


			try {
				$currAssocProductsColl = self::$procObj->objectManager->create('\Magento\GroupedProduct\Model\Product\Type\Grouped')->getAssociatedProductCollection( $grpProduct );
			}

			catch(Exception $e) {
					generalLib::error($e->getMessage());
			}

			foreach ( $currAssocProductsColl as $currAssocProduct ) {

				$assocProducts[ $currAssocProduct->getSku() ] = $lnkCnt;

				$linkData = self::$procObj->objectManager->create('\Magento\Catalog\Api\Data\ProductLinkInterfaceFactory')->create();

				$linkData->setSku( $grpProduct->getSku() )
					->setLinkId( $currAssocProduct->getLinkId() )
					->setLinkType( 'associated' )
					->setLinkedProductSku( $currAssocProduct->getSku() )
					->setLinkedProductType( $currAssocProduct->getTypeId() )
					->setPosition( $relCnt++ )
					->getExtensionAttributes()->setQty( 0 );

				$linkedProducts[ $lnkCnt++ ] = $linkData;

				//echo "current links:\n";
				//print_r($currAssocProduct->getData());

				$relCnt++;
			}

			/*
			foreach ( $linkedProducts as $currLinkedProd ) {
				//print_r($currLinkedProd->getData());
				echo "\n";
				echo 'currSku = ' . $currLinkedProd->getSku() . "\n";
				echo 'curr Link ProductSku = ' . $currLinkedProd->getLinkedProductSku() . "\n";
				echo 'currLinkType = ' . $currLinkedProd->getLinkType() . "\n";
				echo 'currPos = ' . $currLinkedProd->getPosition() . "\n";
			}
			*/

			//die();
		}

		//GET CURRENT LIST OF ASSOCIATED PRODUCTS FOR THE GROUPED PRODUCT

		//echo 'processGroupedProductForProduct: assocProducts = ' . "\n";

		//echo 'processGroupedProductForProduct: procAssocProdIds = ' . "\n";
		//print_r($procAssocProdIds);
					
		$prodObj = new stdClass();
		
		//echo 'actionType = ' . $actionType . "\n";
		//print_r($procAssocProdIds);

		//ADD ANY ASSOCIATED PRODUCTS NOT IN THE CURRENT LIST
		if (is_null($actionType) || $actionType != 'D') {

			foreach($procAssocProdIds as $procAssocProdId) {
				
				if (!is_null($intProdIdField)) {		
					$prodObj->$intProdIdField = $procAssocProdId;
				} else {
					$prodObj->$extProdIdField = $procAssocProdId;
				}
				
				//print_r($prodObj);

				$assocProduct = magentoProd::getMagProduct($prodObj, $extProdIdField, $extProdIdAttr, $intProdIdField, true);
			
				if (!$assocProduct) {
					continue;
				}
		
				$assocProductSku = $assocProduct->getSku();
				
				if (!array_key_exists($assocProductSku, $assocProducts)) {

					$linkData = self::$procObj->objectManager->create('\Magento\Catalog\Api\Data\ProductLinkInterfaceFactory')->create();

					$linkData->setSku( $grpProduct->getSku() )
						->setLinkedProductSku( $assocProductSku )
						->setLinkedProductType( $assocProduct->getTypeId() )
						->setLinkType( 'associated' )
						->setPosition( ++$relCnt )
						->getExtensionAttributes()->setQty( 0 );

					$linkedProducts[ $lnkCnt ] = $linkData;

					$assocProducts[ $assocProductSku ] = $lnkCnt++;
				
					generalLib::mesg('Adding associated product id ' . $assocProductSku . ' (' . $procAssocProdId . ')');
					$updGrpProd = true;
				}
			}
		}
		
		if (is_null($actionType)) {

			$assocIdsToDelete = array_diff($oldAssocProdIds, $procAssocProdIds);

		} else {

			if ($actionType == 'D') {
				$assocIdsToDelete = $procAssocProdIds;
			} else {
				$assocIdsToDelete = array();
			}

		}
		
		//print_r($assocIdsToDelete);

		//DELETE ANY ASSOCIATED PRODUCTS WHICH SHOULD NO LONGER BE IN THE CURRENT LIST
		foreach($assocIdsToDelete as $assocIdToDelete) {
			
			if (!is_null($intProdIdField)) {		
				$prodObj->$intProdIdField = $assocIdToDelete;
			} else {
				$prodObj->$extProdIdField = $assocIdToDelete;
			}

			//echo 'prodObj = ' . $prodObj . "\n";
			//print_r($prodObj);
			
			$assocProduct = magentoProd::getMagProduct($prodObj, $extProdIdField, $extProdIdAttr, $intProdIdField, true);
		
			if (!$assocProduct) {
				continue;
			}
	
			$assocProductSku = $assocProduct->getSku();

			//echo 'assocProductSku = ' . $assocProductSku . "\n";

			//print_r($assocProducts);

			if (array_key_exists($assocProductSku, $assocProducts)) {

				$linkProdKey = $assocProducts[ $assocProductSku ];

				//echo 'del link id = ' . $linkedProducts[ $linkProdKey ]->getLinkId() . "\n";
				//die();

				try {
					self::$procObj->objectManager->create(\Magento\Catalog\Model\ResourceModel\Product\Link::class)->deleteProductLink( $linkedProducts[ $linkProdKey ]->getLinkId() );
				} catch(Exception $e) {
					generalLib::error($e->getMessage());
					$rc = false;
        		}

        		if ($rc) {
					unset($linkedProducts[ $assocProducts[ $assocProductSku ] ]);

					unset($assocProducts[ $assocProductSku ]);

					generalLib::mesg('Removing associated product sku ' . $assocProductSku . ' (' . $assocIdToDelete . ')');
				}

				//$updGrpProd = true;

			} else {
				generalLib::warn('Cannot remove product id ' . $assocProductSku  . ' (' . $assocIdToDelete . ') - not an associated product');
			}
		}
		
		//die();

		//MAKE SURE THE ASSOCIATED PRODUCT POSITIONS ARE CONSECUTIVE
		$posIdx = 0;

		foreach($linkedProducts as $lnkIdx => $currLinkedProd) {

			if ($currLinkedProd->getLinkType() == 'associated') {
				$posIdx++;

				if ($linkedProducts[ $lnkIdx ]->getPosition() != $posIdx) {
					$linkedProducts[ $lnkIdx ]->setPosition( $posIdx );
				}

			}

		}
		
		//echo 'assocProducts: ' . "\n";
		//print_r($assocProducts);

		/*
			foreach ( $linkedProducts as $currLinkedProd ) {
				echo 'currSku = ' . $currLinkedProd->getSku() . "\n";
				echo 'curr Link ProductSku = ' . $currLinkedProd->getLinkedProductSku() . "\n";
				echo 'currLinkType = ' . $currLinkedProd->getLinkType() . "\n";
				echo 'currPos = ' . $currLinkedProd->getPosition() . "\n";
			}
		*/

		
		if ($delGrpProdIfNoAssocProds) {

			if (count($assocProducts) == 0) {
				$keepGrpProd = false;
			}

		}

		
		if ($updGrpProd) {
			$grpProdSku = $grpProduct->getSku();
			
			if ($keepGrpProd) {

				generalLib::mesg('Saving grouped product sku \'' .  $grpProdSku . '\'');
				$grpProduct->setProductLinks($linkedProducts);
				
				try {
					generalLib::mesg('Saving grouped product sku \'' .  $grpProdSku . '\'');
					$grpProduct->save();
				}
					
				catch(Exception $e) {
					generalLib::error($e->getMessage());
					generalLib::error('Grouped product sku \'' . $grpProdSku . '\' not saved');
					$rc = false;
				}

			} else {

				try {
					generalLib::mesg('deleting grouped product sku \'' . $grpProdSku . '\'');
					$grpProduct->delete();
				}
					
				catch(Exception $e) {
					generalLib::error($e->getMessage());
					generalLib::error('Grouped product sku \'' . $grpProdSku . '\' not deleted');
					$rc = false;
				}

			}

		} else {
			generalLib::mesg('No grouped product updates to apply');
		}
		
		//die();
		return $rc;
	}


	//###########################
	static public function getGroupProdIdForSimpleProdId($simpleProdId) {
		$groupProdId = false;

		//$parentIds = Mage::getResourceSingleton('catalog/product_type_configurable')->getParentIdsByChild($product->getId());
		//$parentIds = Mage::getResourceSingleton('catalog/product_type_configurable')->getParentIdsByChild($product->getId());

		//$parentIds = Mage::getResourceSingleton('catalog/product_link')
		$parentIds = Mage::getResourceSingleton('catalog/product_link')
                   ->getParentIdsByChild($simpleProdId, Mage_Catalog_Model_Product_Link::LINK_TYPE_GROUPED);

		if (isset($parentIds[0])) {
			$groupProdId = $parentIds[0];
		    //$product = Mage::getModel('catalog/product')->load($parentIds[0]);
		}

		return $groupProdId;
	}
	
	
	//###########################
	static public function setGrpProdWebSitesFromAssocProds($storeDets, $grpProdSku) {
		$rc = true;
		
		//$grpProduct = Mage::getModel('catalog/product')->loadByAttribute('sku', $grpProdSku);


		$grpProduct = self::$procObj->objectManager->get('Magento\Catalog\Model\Product')->loadByAttribute('sku', $grpProdSku);

		$grpProdId = $grpProduct->getId();
		
				
		//SET GROUP PRODUCT WEBSITE IDS, BASED ON THE WEBSITE'S ASSIGNED TO THE ASSOCIATED PRODUCTS
		$oldGrpProdWebSiteIds = $grpProduct->getWebsiteIds();
		
		$newGrpProdWebSiteIds = array();
		
		$currAssocProductsColl = self::$procObj->objectManager->create('\Magento\GroupedProduct\Model\Product\Type\Grouped')->getAssociatedProductCollection( $grpProduct );

		$currAssocProducts = $grpProduct->getTypeInstance(true)->getAssociatedProducts($grpProduct);

		$assocProdSkus = array();
		
		foreach ($currAssocProductsColl as $currAssocProduct) {

			$assocProdSkus[] =  $currAssocProduct->getSku();

		}

		//echo 'assocProdIds:' . "\n";
		//print_r($assocProdSkus);
		
		$assocProdColl = self::$procObj->objectManager->get('Magento\Catalog\Model\Product')
			->getCollection()
			->addAttributeToFilter('sku', array('in' => $assocProdSkus));

		foreach($assocProdColl as $prod) {
			$assocProdWebSiteIds = $prod->getWebsiteIds();
			//echo 'assocProdWebSiteIds for ' . $prod->getId() . ':' . "\n";
			
			foreach($assocProdWebSiteIds as $assocProdWebSiteId) {

				if (!in_array($assocProdWebSiteId, $newGrpProdWebSiteIds)) {
					$newGrpProdWebSiteIds[] = $assocProdWebSiteId;
					//echo 'adding website ' . $assocProdWebSiteId . ' to newGrpProdWebSiteIds' . "\n";
				}

			}
			
			$gotAllWebsites = true;
			
			foreach ($storeDets as $storeId => $storeDet) {

				if ( !in_array($storeDet->websiteId,  $newGrpProdWebSiteIds) ) {
					$gotAllWebsites = false;
					break;
				}

			}
			
			if ($gotAllWebsites) {
				//echo 'got all web sites' . "\n";
				break;
			}
		}
		
		if (!sort($oldGrpProdWebSiteIds, SORT_NUMERIC)) {
			generalLib::error('Cannot sort old group product \'' . $grpProdId . '\' website ids');
		}
		
		if (!sort($newGrpProdWebSiteIds, SORT_NUMERIC)) {
			generalLib::error('Cannot sort new group product \'' . $grpProdId . '\' website ids');
		}

		//echo 'oldGrpProdWebSiteIds:' . "\n";
		//print_r($oldGrpProdWebSiteIds);

		//echo 'newGrpProdWebSiteIds:' . "\n";
		//print_r($newGrpProdWebSiteIds);
		
		$addedWebsiteIds = array_diff($newGrpProdWebSiteIds, $oldGrpProdWebSiteIds);
		//echo 'addedWebsiteId:' . "\n";
		//print_r($addedWebsiteIds);
		
		$deletedWebsiteIds = array_diff($oldGrpProdWebSiteIds, $newGrpProdWebSiteIds);
		//echo 'deletedWebsiteIds:' . "\n";
		//print_r($deletedWebsiteIds);
		
		if ( (count($addedWebsiteIds) > 0) || (count($deletedWebsiteIds) > 0) ) {

			foreach($addedWebsiteIds as $addedWebsiteId) {
				generalLib::mesg('Adding website id ' . '\'' . $addedWebsiteId . '\' to group product \'' . $grpProdId . '\'');
			}
			
			foreach($deletedWebsiteIds as $deletedWebsiteId) {
				generalLib::mesg('Deleting website id ' . '\'' . $deletedWebsiteId . '\' from group product \'' . $grpProdId . '\'');
			}
								
			$grpProduct->setWebsiteIds( $newGrpProdWebSiteIds );	
			
			try {
				generalLib::mesg('Saving grouped product sku \'' .  $grpProdId . '\'');
				$grpProduct->save();
			}
				
			catch(Exception $e) {
				generalLib::error($e->getMessage());
				generalLib::error('Grouped product sku \'' . $grpProdSku . '\' not saved');
				$rc = false;
			}
		}
		
		return $rc;
	}
}

?>
