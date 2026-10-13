<?php

class magXlsxExportProcessor {
	public $procConfig;
	public $exportXlsx;


	//###########################	
	public function __construct($procConfig) {
		$this->procConfig = $procConfig;

		if (method_exists('magXlsxExportCustomProcessor', 'setCustomConfig')) {
			magXlsxExportCustomProcessor::setCustomConfig($this, 'Products');
		}

		$this->procConfig->alphabet = range('A', 'Z');

		$this->procConfig->sheetIdx = -1;

	}


	//###########################	
	public function exportXlsxFile($feedType) {

		$this->exportXlsx = new  excelLib();
		$this->exportXlsx->createExcelSpreadSheet();

		if ($feedType == 'Products' || $feedType == 'ALL') {
			$this->exportProductData();
		}
		
		if ($feedType == 'Categories' || $feedType == 'ALL') {
			$this->exportCategoryData();
		}

		if ($feedType == 'Images' || $feedType == 'ALL') {
			$this->exportImageData();
		}

		$this->exportXlsx->autoSizeSheets();

		$this->exportXlsx->saveSpreadSheet( $this->procConfig->localXlsxOutboxDir . '/magExport-' . date('Ymd_His', magentoGeneral::getLocalTimeStamp()) . '.xlsx', 'Excel2007');

		//$this->exportXlsx->saveSpreadSheet('/home/dancegea/dancegear.co.uk/scripts/' . date('Ymd_His', magentoGeneral::getLocalTimeStamp()) . '.xlsx', 'Excel2007');
	}


	//###########################
	private function exportProductData() {
		$rc = true;

		$this->exportXlsx->createExcelSheet(++$this->procConfig->sheetIdx, 'Products');

		$this->procConfig->rowNum = 1;

		$this->exportXlsx->updExcelCell( 'A', $this->procConfig->rowNum, 'Sku');

		$this->exportXlsx->updExcelCell( 'B', $this->procConfig->rowNum, 'Store');

		$this->exportXlsx->updExcelCell( 'C', $this->procConfig->rowNum, 'Product Type');

		$this->exportXlsx->updExcelCell( 'D', $this->procConfig->rowNum, 'Parent Sku');

		$this->exportXlsx->updExcelCell( 'E', $this->procConfig->rowNum, 'Product Name');

		$this->exportXlsx->updExcelCell( 'F', $this->procConfig->rowNum, 'Description');

		$this->exportXlsx->updExcelCell( 'G', $this->procConfig->rowNum, 'Short Description');

		$this->exportXlsx->updExcelCell( 'H', $this->procConfig->rowNum, 'Meta Title');

		$this->exportXlsx->updExcelCell( 'I', $this->procConfig->rowNum, 'Meta Description');

		$this->exportXlsx->updExcelCell( 'J', $this->procConfig->rowNum, 'Manufacturer');

		$this->exportXlsx->updExcelCell( 'K', $this->procConfig->rowNum, 'Color~DD-C');

		$this->exportXlsx->updExcelCell( 'L', $this->procConfig->rowNum, 'Ribbon~DD-CO');

		$this->exportXlsx->updExcelCell( 'M', $this->procConfig->rowNum, 'Top Ribbon~DD-CO');

		$this->exportXlsx->updExcelCell( 'N', $this->procConfig->rowNum, 'Middle Ribbon~DD-CO');

		$this->exportXlsx->updExcelCell( 'O', $this->procConfig->rowNum, 'Bottom Ribbon~DD-CO');

		$this->exportXlsx->updExcelCell( 'P', $this->procConfig->rowNum, 'Odd Net Colour~DD-CO');

		$this->exportXlsx->updExcelCell( 'Q', $this->procConfig->rowNum, 'Even Net Colour~DD-CO');

		$this->exportXlsx->updExcelCell( 'R', $this->procConfig->rowNum, 'Size~DD-C');

		$this->exportXlsx->updExcelCell( 'S', $this->procConfig->rowNum, 'Width~DD-C');

		$this->exportXlsx->updExcelCell( 'T', $this->procConfig->rowNum, 'Waist~DD-C');

		$this->exportXlsx->updExcelCell( 'U', $this->procConfig->rowNum, 'Length~DD-C');

		$this->exportXlsx->updExcelCell( 'V', $this->procConfig->rowNum, 'Material~DD-C');

		$this->exportXlsx->updExcelCell( 'W', $this->procConfig->rowNum, 'Activity~MD');

		$this->exportXlsx->updExcelCell( 'X', $this->procConfig->rowNum, 'Weight');

		$this->exportXlsx->updExcelCell( 'Y', $this->procConfig->rowNum, 'Taxable');

		$this->exportXlsx->updExcelCell( 'Z', $this->procConfig->rowNum, 'Manage Stock');

		$this->exportXlsx->updExcelCell( 'AA', $this->procConfig->rowNum, 'Back Orders');

		$this->exportXlsx->updExcelCell( 'AB', $this->procConfig->rowNum, 'Price');

		$this->exportXlsx->updExcelCell( 'AC', $this->procConfig->rowNum, 'Enabled');

		$this->exportXlsx->updExcelCell( 'AD', $this->procConfig->rowNum, 'Size Chart~TX');

		$this->exportXlsx->updExcelCell( 'AE', $this->procConfig->rowNum, 'Estimated Despatch time (days)');

		$this->procConfig->rowNum++;


		$prodColl = Mage::getModel("catalog/product")
			->getCollection()
			->addAttributeToFilter('type_id', array('eq' => 'configurable'))
			//->addAttributeToFilter('sku', array('eq' => 'ADAM'))
			//->addAttributeToFilter('sku', array('eq' => 'ANITA-A'))
			//->addAttributeToFilter('sku', array('eq' => '7539B'))
			//->addAttributeToFilter('sku', array('eq' => 'DARC-C-X'))
			;

		foreach($prodColl as $aProdColl) {


			$this->getProductForType( $aProdColl, '' );


			$assocProducts = array();

			//GET CURRENT LIST OF ASSOCIATED PRODUCTS FOR THE CONFIGURABLE PRODUCT

			$currAssocProductsMultiArr = Mage::getModel('catalog/product_type_configurable')->getChildrenIds( $aProdColl->getEntityId() );

			//PROCESS SIMPLE PRODUCTS LINKED TO CONFIGURABLE PRODUCTS
			foreach ($currAssocProductsMultiArr as $currAssocProductsArr) {

				foreach ($currAssocProductsArr as $prodId) {
					$assocProducts[] = $prodId;
				}

			}

			$prodColl = Mage::getModel("catalog/product")
				->getCollection()
				->addAttributeToFilter('type_id', array('eq' => 'simple'))
				->addAttributeToFilter('entity_id', array('in' => array( $assocProducts )))
				;


			$configProdSku = $aProdColl->getSku();

			foreach($prodColl as $aProdColl) {
				$this->getProductForType( $aProdColl, $configProdSku );
				//die();

				//if ($this->procConfig->rowNum >= 100) {
					//break;
				//}
			}

			//if ($this->procConfig->rowNum >= 100) {
				//break;
			//}
		}


		$prodColl = Mage::getModel("catalog/product")
			->getCollection()
			->addAttributeToFilter('type_id', array('eq' => 'simple'));


		foreach($prodColl as $aProdColl) {
			$ConfigProdId = magentoConfigurableProducts::getConfigProdIdForSimpleProdId( $aProdColl->getEntityId() );

			//ONLY PROCESS SIMPLE PRODUCTS NOT LINKED TO CONFIGURABLE PRODUCTS
			if (!$ConfigProdId) {
				$this->getProductForType( $aProdColl, '' );

				//if ($this->procConfig->rowNum >= 100) {
					//break;
				//}
			}
		}
	}


	//###########################
	private function getProductForType( $aProdColl, $configProdSku ) {
		$rc = true;

    	$productXslxRec = new stdClass();

    	//echo "prodColl:\n";

    	//print_r($aProdColl->getData());
    	
    	$productXslxRec->sku = $aProdColl->getSku();

    	generalLib::mesg('processing product for product sku \'' . $productXslxRec->sku  . '\'');

    	$product = magentoProd::getMagProduct($productXslxRec, 'sku', 'sku', 'magId', true);

	    if (!$product) {
			$rc = false;
	    	return $rc;
	    }

		$this->exportXlsx->updExcelCell('A', $this->procConfig->rowNum, $productXslxRec->sku);


	    $magProdType = $product->getTypeId();

	    switch($magProdType) {
	    	case Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE:
	    			$productXslxRec->product_type = 'C';
	    			break;


	    	case Mage_Catalog_Model_Product_Type::TYPE_GROUPED:
	    			$productXslxRec->product_type = 'G';
	    			break;

	    	case Mage_Catalog_Model_Product_Type::TYPE_SIMPLE:
	    			$productXslxRec->product_type = 'S';
	    			break;


			default:	generalLib::warn('Invalid product type \'' . $magProdType . '\' for SKU \'' . $sku . '\' - skipping');
						$rc = false;
		}


		if (!$rc) {
			return $rc;
		}

		$this->exportXlsx->updExcelCell('C', $this->procConfig->rowNum, $productXslxRec->product_type);


		if ($configProdSku) {
			$this->exportXlsx->updExcelCell('D', $this->procConfig->rowNum, $configProdSku);
		}


		if ($rc) {
			$rc = $this->exportCommonProductAttrs($product, $productXslxRec);
		}

		//echo 'size = ' . $productXslxRec->size . "\n";

		if (method_exists('magXlsxExportCustomProcessor', 'processCustomExportProductLogic')) {

			$rc = magXlsxExportCustomProcessor::processCustomExportProductLogic($this->exportXlsx, $product, $productXslxRec);

			if (!$rc) {
				return $rc;
			}
        }

		$this->procConfig->rowNum++;
	}


	//###########################
	private function exportCommonProductAttrs($product, $productXslxRec) {

		$productXslxRec->product_name = $product->getName();

		$this->exportXlsx->updExcelCell('E', $this->procConfig->rowNum, $productXslxRec->product_name);


		$productXslxRec->description = $product->getDescription();

		$productXslxRec->description = str_replace('<br />', "\n", $productXslxRec->description );

		$this->exportXlsx->updExcelCell('F', $this->procConfig->rowNum, $productXslxRec->description);


		$productXslxRec->short_description = $product->getShortDescription();

		$productXslxRec->short_description = str_replace('<br />', "\n", $productXslxRec->short_description );

		$this->exportXlsx->updExcelCell('G', $this->procConfig->rowNum, $productXslxRec->short_description);


		$productXslxRec->prodMetaTitle = $product->getMetaTitle();

		$this->exportXlsx->updExcelCell('H', $this->procConfig->rowNum, $productXslxRec->prodMetaTitle);


		$productXslxRec->prodMetaDesc = $product->getMetaDescription();

		$this->exportXlsx->updExcelCell('I', $this->procConfig->rowNum, $productXslxRec->prodMetaTitle);


		$productXslxRec->manufacturer = $product->getAttributeText('manufacturer');

		if ( !empty($productXslxRec->manufacturer) ) {
			$this->exportXlsx->updExcelCell('J', $this->procConfig->rowNum, $productXslxRec->manufacturer);
		}

		$prodMaterial = $product->getMaterial();

		$this->exportXlsx->updExcelCell('V', $this->procConfig->rowNum, $prodMaterial);


		$productXslxRec->prodActivities = $product->getAttributeText('activity');

		if ( is_array($productXslxRec->prodActivities) ) {
			$productXslxRec->prodActivities = implode(',', $productXslxRec->prodActivities);
		}

		if ( !empty($productXslxRec->prodActivities) ) {
			$this->exportXlsx->updExcelCell('W', $this->procConfig->rowNum, $productXslxRec->prodActivities);
		}

		$productXslxRec->prodWeight = $product->getWeight();

		if ($productXslxRec->prodWeight != '0') {
			$this->exportXlsx->updExcelCell('X', $this->procConfig->rowNum, $productXslxRec->prodWeight);
		}

		$magTaxClassId = $product->getTaxClassId();	

		if ($magTaxClassId == '2') {
			$productXslxRec->prodTax = 'Y';
		} else {
			$productXslxRec->prodTax = '';
		}

		$this->exportXlsx->updExcelCell('Y', $this->procConfig->rowNum, $productXslxRec->prodTax);


		$stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product);

		$magManageStock = $stockItem->getUseConfigManageStock();

		if ($magManageStock) {
			$productXslxRec->prodManStock = 'Y';
		} else {
			$productXslxRec->prodManStock = '';
		}

		$this->exportXlsx->updExcelCell('Z', $this->procConfig->rowNum, $productXslxRec->prodManStock);


		$magBackOrders = $stockItem->getData('backorders');

		if ($magBackOrders == '1') {
			$productXslxRec->prodBackOrders = 'Y';
		} elseif ($magBackOrders == '2') {
			$productXslxRec->prodBackOrders = 'Y-N';
		}

		$this->exportXlsx->updExcelCell('AA', $this->procConfig->rowNum, $productXslxRec->prodBackOrders);


		$productXslxRec->prodPrice = $product->getPrice();

		$this->exportXlsx->updExcelCell('AB', $this->procConfig->rowNum, $productXslxRec->prodPrice);


		$magProdStatus = $product->getStatus();

		if ($magProdStatus == Mage_Catalog_Model_Product_Status::STATUS_ENABLED) {
			$productXslxRec->prodEnabled = 'Y';
		} else {
			$productXslxRec->prodEnabled = '';
		}

		$this->exportXlsx->updExcelCell('AC', $this->procConfig->rowNum, $productXslxRec->prodEnabled);


		$productXslxRec->prodSizeChart = $product->getSizeChart();

		$this->exportXlsx->updExcelCell('AD', $this->procConfig->rowNum, $productXslxRec->prodSizeChart);

	}


	//###########################
	private function exportCategoryData() {
		$this->exportXlsx->createExcelSheet(++$this->procConfig->sheetIdx, 'Categories');

		$this->procConfig->rowNum = 1;

		$this->exportXlsx->updExcelCell( 'A', $this->procConfig->rowNum, 'Sku');

		for($idx=1; $idx <= 10; $idx++) {
			$this->exportXlsx->updExcelCell( $this->procConfig->alphabet[ $idx ], $this->procConfig->rowNum, 'Category Path ' . $idx);
		}

		$this->procConfig->rowNum++;

		//echo 'brand cat id = ' . $this->procConfig->brandsBaseCatId . "\n";

		$this->procConfig->rowNum++;

		$prodColl = Mage::getModel("catalog/product")
			->getCollection()
			->addAttributeToFilter('type_id', array('in' => array( 'configurable', 'simple' )))
			;

		foreach($prodColl as $aProdColl) {
			$catXslxRec = new stdClass();

			$catXslxRec->sku = $aProdColl->getSku();

			generalLib::mesg('processing categories for product sku \'' . $catXslxRec->sku  . '\'');

		    $product = Mage::getModel('catalog/product')->loadByAttribute('sku', $catXslxRec->sku);

		    if (!$product) {
		    	continue;
		    }
        
        	$pathArray = array();

        	$collection1 = $product->getCategoryCollection()
            	->setStoreId(Mage::app()->getStore()->getId())
            	->addAttributeToSelect('path')
            	->addAttributeToSelect('is_active');
            

        	foreach($collection1 as $cat1) {
            	$pathIds = explode('/', $cat1->getPath());
            	//print_r($pathIds);

            	//EXCLUDE BRAND CATEGORIES
            	if ( in_array($this->procConfig->brandsBaseCatId, $pathIds)) {
            		continue;
            	}

            	$collection = Mage::getModel('catalog/category')->getCollection()
                	->setStoreId(Mage::app()->getStore()->getId())
	                ->addAttributeToSelect('name')
	                ->addAttributeToSelect('is_active')
	                ->addFieldToFilter('entity_id', array('in' => $pathIds));

           		$pathByName = '';

	            foreach($collection as $cat) {

	            	if ($cat->getLevel() > 1) {

	            		if ($pathByName != '') {
	            			$pathByName .= '>';
	            		}

	                	$pathByName .= $cat->getName();
	                }
	            }
            
            	$pathArray[] = $pathByName;

        	}

        	//print_r($pathArray);

        	if (count($pathArray) > 0) {
				$this->exportXlsx->updExcelCell('A', $this->procConfig->rowNum, $catXslxRec->sku);

				
				$idx = 1;

				//print_r($pathArray);

				foreach($pathArray as $catPath) {
					$this->exportXlsx->updExcelCell( $this->procConfig->alphabet[ $idx ], $this->procConfig->rowNum, $catPath);
					$idx++;
				}

				$this->procConfig->rowNum++;

				//if ($this->procConfig->rowNum >= 100) {
						//break;
				//}
			}
		}
	}


	//###########################
	private function exportImageData() {
		$this->exportXlsx->createExcelSheet(++$this->procConfig->sheetIdx, 'Images');

		$this->procConfig->rowNum = 1;

		$this->exportXlsx->updExcelCell( 'A', $this->procConfig->rowNum, 'Sku');

		$this->exportXlsx->updExcelCell( 'B', $this->procConfig->rowNum, 'Hero Image');

		for($idx=2; $idx <= 25; $idx++) {
			$this->exportXlsx->updExcelCell( $this->procConfig->alphabet[ $idx ], $this->procConfig->rowNum, 'Image ' . $idx);
		}

		$this->procConfig->rowNum++;

		$prodColl = Mage::getModel("catalog/product")
			->getCollection()
			->addAttributeToFilter('type_id', array('in' => array('configurable', 'simple')))
			//->addAttributeToFilter('sku', array('eq' => 'B80BK'))
			->addAttributeToFilter('small_image',array('notnull'=>'','neq'=>'no_selection'))
			;

		foreach($prodColl as $aProdColl) {
			$imgXlsxRec = new stdClass();

			$imgXlsxRec->sku = $aProdColl->getSku();
			//echo 'sku = ' . $imgXlsxRec->sku . "\n";

			generalLib::mesg('processing images for product sku \'' . $imgXlsxRec->sku  . '\'');

			$product = magentoProd::getMagProduct($imgXlsxRec, 'sku', 'sku', 'magId', true);

		    if (!$product) {
		    	continue;
		    }


		    $magImgNames = array();

			foreach ($product->getMediaGalleryImages() as $magImg) {
				$magImgFullPath = $magImg->getPath();

				$magImgNames[] =  basename($magImgFullPath);
			}

			//print_r($magImgNames);

			if (count($magImgNames) > 0) {
				$this->exportXlsx->updExcelCell('A', $this->procConfig->rowNum, $imgXlsxRec->sku);

				//echo $magImgFullPath."\n";

				$idx = 1;

				foreach($magImgNames as $magImgName) {
					$this->exportXlsx->updExcelCell( $this->procConfig->alphabet[ $idx ], $this->procConfig->rowNum, $magImgName);
					$idx++;
				}

				$this->procConfig->rowNum++;

				//if ($this->procConfig->rowNum >= 100) {
						//break;
				//}
			}

		}
	}

}

?>
