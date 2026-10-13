<?php

class darWsBespokeConfProcessor extends darWsProcessor {
	public $bespConfProcConfig;

	public function __construct($objectManager) {

		$this->bespConfProcConfig = new stdClass();

		//CABLE CODE MAGENTO FIELDS
		$this->bespConfProcConfig->cableMagAttrs = array('cable_code' => 'cable_code', 'cable_name' => 'cable_name');

	    //CABLE CODE PRICE MAGENTO FIELDS
		$this->bespConfProcConfig->cablePriceMagAttrs = array('dhl_partnum' => 'dhl_partnum', 'cable_code' => 'cable_code', 'cable_price' => 'cable_price');

        //GLASS CODE MAGENTO FIELDS
		$this->bespConfProcConfig->glassMagAttrs = array('glass_code' => 'glass_code', 'glass_name' => 'glass_name');

		//GLASS CODE PRICE MAGENTO FIELDS
		$this->bespConfProcConfig->glassPriceMagAttrs = array('dhl_partnum' => 'dhl_partnum', 'glass_code' => 'glass_code', 'glass_price' => 'glass_price');


		//INNER PAINT CODE MAGENTO FIELDS
		$this->bespConfProcConfig->innerPaintMagAttrs = array('paint_code' => 'paint_code', 'paint_name' => 'paint_name');

		//INNER PAINT CODE PRICE MAGENTO FIELDS
		$this->bespConfProcConfig->innerPaintPriceMagAttrs = array('dhl_partnum' => 'dhl_partnum', 'paint_code' => 'paint_code', 'paint_price' => 'paint_price');


		//OUTER PAINT MAGENTO FIELDS
		$this->bespConfProcConfig->outerPaintMagAttrs = array('paint_code' => 'paint_code', 'paint_name' => 'paint_name');

		//OUTER PAINT CODE PRICE MAGENTO FIELDS
		$this->bespConfProcConfig->outerPaintPriceMagAttrs = array('dhl_partnum' => 'dhl_partnum', 'paint_code' => 'paint_code', 'paint_price' => 'paint_price');


		//BRAID MAGENTO FIELDS
		$this->bespConfProcConfig->braidMagAttrs = array('braid_code' => 'braid_code', 'braid_name' => 'braid_name');

		//BRAID PRICE MAGENTO FIELDS
		$this->bespConfProcConfig->braidPriceMagAttrs = array('shade_partnum' => 'shade_partnum', 'braid_code' => 'braid_code', 'braid_price' => 'braid_price');


		//FRINGE MAGENTO FIELDS
		$this->bespConfProcConfig->fringeMagAttrs = array('fringe_code' => 'fringe_code', 'fringe_name' => 'fringe_name');

		//FRINGE PRICE MAGENTO FIELDS
		$this->bespConfProcConfig->fringePriceMagAttrs = array('shade_partnum' => 'shade_partnum', 'fringe_code' => 'fringe_code', 'fringe_price' => 'fringe_price');


		//SHADE SILK COLOUR CUSTOM ATTRIBUTES
		$this->bespConfProcConfig->shadeSilkColourMagAttrs = array('id' => 'dar_id', 'colour_name' => 'colour_name', 'active' => 'active', 
																	'colour_image' => 'colour_image', 'displayorder' => 'display_order');

		$this->bespConfProcConfig->shadeSilkColourCustomAttrs = array('colour_code' => 'T');

		$this->bespConfProcConfig->shadeSilkColourCustomMappings = array( 'colour_code' => 'colour_code');

		//SHADE SILK COLOUR PRICE MAGENTO FIELDS
		$this->bespConfProcConfig->shadeSilkColourPriceMagAttrs = array('shade_partnum' => 'shade_partnum', 'silkcolour_code' => 'colour_code', 'silkcolour_price' => 'colour_price');


		//SHADE RANGE STYLE MAGENTO ATTRIBUTES
		$this->bespConfProcConfig->styleMagAttrs = array('style_code' => 'style_code', 'style_name' => 'style_name');


		//ACCESSORY MAGENTO FIELDS
		$this->bespConfProcConfig->accessoryMagAttrs = array('shade_partnum' => 'shade_partnum', 'accessory_type' => 'accessory_type', 'accessory_partnum' => 'accessory_sku');


		//DEFAULT SHADE MAGENTO FIELDS
		$this->bespConfProcConfig->defaultShadeMagAttrs = array('Item_No' => 'dhl_partnum', 'Shade_Code' => 'shade_code', 'Shade_Type' => 'shade_type', 'Shade_Range' => 'shade_range');

		//NOTE \\ SO STRING CAN CONTAIN A BACK SLASH
		$this->bespConfProcConfig->bespConfBaseModel = 'StarDigital\BespokeConfigurator\Model\\';

		parent::__construct($objectManager);
	}


	//###########################
	public function processWsFeed() {
		parent::processWsFeed();
	}


	//###########################	
	public function processDHL_CableWebService($actionType, $dhlCableRec) {

		return $this->processBespokeConfCodeWebService($actionType, $dhlCableRec, 'Cable', 'cable_code', $this->bespConfProcConfig->cableMagAttrs);

	}


	//###########################
	public function processDHL_Cable_PricesWebService($actionType, $dhlCablePriceRec) {		

		print_r($dhlCablePriceRec);
		//die();

		return $this->processBespokeConfPriceWebService($actionType, $dhlCablePriceRec, 'Cableprice', 'cable_price', 'dhl_partnum', 'cable', 'cable_code', 'Cable', $this->bespConfProcConfig->cablePriceMagAttrs);

	}


	//###########################	
	public function processDHL_GlassWebService($actionType, $dhlGlassRec) {

		return $this->processBespokeConfCodeWebService($actionType, $dhlGlassRec, 'Glass', 'glass_code', $this->bespConfProcConfig->glassMagAttrs);
	
	}


	//###########################
	public function processDHL_Glass_PricesWebService($actionType, $dhlGlassPriceRec) {		

		return $this->processBespokeConfPriceWebService($actionType, $dhlGlassPriceRec, 'Glassprice', 'glass_price', 'dhl_partnum', 'glass', 'glass_code', 'Glass', $this->bespConfProcConfig->glassPriceMagAttrs);

	}


	//###########################	
	public function processDHL_Inner_PaintWebService($actionType, $dhlInnerPaintRec) {

		return $this->processBespokeConfCodeWebService($actionType, $dhlInnerPaintRec, 'Innerpaint', 'paint_code', $this->bespConfProcConfig->innerPaintMagAttrs);
	
	}


	//###########################
	public function processDHL_Inner_Paint_PricesWebService($actionType, $dhlInnerPaintPriceRec) {		

		return $this->processBespokeConfPriceWebService($actionType, $dhlInnerPaintPriceRec, 'Innerpaintprice', 'paint_price', 'dhl_partnum', 'innerpaint', 'paint_code', 'Inner', $this->bespConfProcConfig->innerPaintPriceMagAttrs);

	}


	//###########################	
	public function processDHL_Outer_PaintWebService($actionType, $dhlOuterPaintRec) {

		return $this->processBespokeConfCodeWebService($actionType, $dhlOuterPaintRec, 'Outerpaint', 'paint_code', $this->bespConfProcConfig->outerPaintMagAttrs);
	
	}


	//###########################	
	public function processDHL_Outer_Paint_PricesWebService($actionType, $dhlOuterPaintPriceRec) {

		return $this->processBespokeConfPriceWebService($actionType, $dhlOuterPaintPriceRec, 'Outerpaintprice', 'paint_price', 'dhl_partnum', 'outerpaint', 'paint_code', 'Outer', $this->bespConfProcConfig->outerPaintPriceMagAttrs);
	
	}


	//###########################	
	public function processShade_BraidWebService($actionType, $shadeBraidRec) {

		return $this->processBespokeConfCodeWebService($actionType, $shadeBraidRec, 'Braid', 'braid_code', $this->bespConfProcConfig->braidMagAttrs);
	
	}


	//###########################	
	public function processShade_Braid_PricesWebService($actionType, $shadeBraidPriceRec) {

		return $this->processBespokeConfPriceWebService($actionType, $shadeBraidPriceRec, 'Braidprice', 'braid_price', 'shade_partnum', 'braid', 'braid_code', 'Braid', $this->bespConfProcConfig->braidPriceMagAttrs);
	
	}


	//###########################	
	public function processShade_FringeWebService($actionType, $shadeFringeRec) {

		return $this->processBespokeConfCodeWebService($actionType, $shadeFringeRec, 'Fringe', 'fringe_code', $this->bespConfProcConfig->fringeMagAttrs);
	
	}


	//###########################	
	public function processShade_Fringe_PricesWebService($actionType, $shadeFringePriceRec) {

		return $this->processBespokeConfPriceWebService($actionType, $shadeFringePriceRec, 'Fringeprice', 'fringe_price', 'shade_partnum', 'fringe', 'fringe_code', 'Fringe', $this->bespConfProcConfig->fringePriceMagAttrs);
	
	}


	//###########################
	public function processShade_Silk_Colour_PricesWebService($actionType, $colourPriceRec) {		

		$colourPriceRec->colour_code = $colourPriceRec->silkcolour_code;

		$colourPriceRec->colour_price = $colourPriceRec->silkcolour_price;

		return $this->processBespokeConfPriceWebService($actionType, $colourPriceRec, 'Shadesilkcolourprice', 'colour_price', 'shade_partnum', 'silkcolour', 'colour_code', 'Silk Colour', $this->bespConfProcConfig->shadeSilkColourPriceMagAttrs);
	}


	//###########################
	public function processBespokeConfCodeWebService($actionType, $codeRec, $codeModel, $codeMagAttr, $codeMagAttrs) {

		$retArr = array();

		$rc = true;

		generalLib::mesg('');
		generalLib::mesg('====');

		print_r($codeRec);

		$codeMagModel = $this->bespConfProcConfig->bespConfBaseModel . $codeModel;

		$collection = $this->objectManager->create( $codeMagModel )
											->getCollection()
											->addFieldToFilter($codeMagAttr, $codeRec->$codeMagAttr);

         if ($actionType == 'D') {
			if ( $collection->getSize() > 0 ) {

				try {
					generalLib::mesg('deleting ' . $codeModel . ' \'' . $codeRec->$codeMagAttr . '\'');
					$codeMagRec = $collection->getFirstItem();
					$codeMagRec->delete();
				} 
				
				catch(Exception $e){
					generalLib::error($e->getMessage());
					$rc = false;
				}

			} else {
				generalLib::warn('Cannot delete ' . $codeModel . ' \'' . $codeRec->$codeMagAttr . '\' - does not exist');
			}

		} else {

			if ( $collection->getSize() == 0 ) {

	        	generalLib::mesg('Creating ' . $codeModel . ' \'' . $codeRec->$codeMagAttr . '\'');
		    	$codeMagRec = $this->objectManager->create( $codeMagModel );
	        } else {

	        	generalLib::mesg('Updating ' . $codeModel . ' \'' . $codeRec->$codeMagAttr . '\'');
				$codeMagRec = $collection->getFirstItem();

	        }
			
			$updAttrs = magentoImport::processMagAttrs($codeMagRec, $codeRec, $codeMagAttrs);

			if ($updAttrs) {

				try {
					generalLib::mesg('Saving ' . $codeModel . ' \'' . $codeRec->$codeMagAttr . '\'');
					$codeMagRec->save();
				}
				
				catch(Exception $e) {
					generalLib::error($e->getMessage());
					generalLib::error($codeModel . ' \'' . $codeRec->$codeMagAttr . '\' not saved');
					$rc = false;
				}
						
			} else {
				generalLib::mesg('No ' . $codeModel . ' updates to apply');
			}
		}

		$retArr['rc'] = $rc;

		return $retArr;
	}


	//###########################
	public function processBespokeConfPriceWebService($actionType, $priceRec, $priceModel, $priceMagAttr, $partNumMagAttr, $codeModel, $codeMagAttr, $customOptName, $priceMagAttrs) {

		$retArr = array();

		$rc = true;

		generalLib::mesg('');
		generalLib::mesg('====');

		print_r($priceRec);

		$product = $this->getProductForPartnum($priceRec->$partNumMagAttr, true);
		
		if (!$product) {

			$rc = false;
			$retArr['rc'] = $rc;
			return $retArr;

		}

		$priceMagModel = $this->bespConfProcConfig->bespConfBaseModel . $priceModel;

		$collection = $this->objectManager->create( $priceMagModel )
						->getCollection()
						->addFieldToFilter($partNumMagAttr, $priceRec->$partNumMagAttr)
						->addFieldToFilter($codeMagAttr, $priceRec->$codeMagAttr);

        if ($actionType == 'D') {

			if ( $collection->getSize() > 0 ) {

				try {

					generalLib::mesg('deleting ' . $codeModel . ' \'' . $priceRec->$codeMagAttr . '\' price for part num \'' . $priceRec->$partNumMagAttr  . '\'');
					$priceMagRec = $collection->getFirstItem();
					$priceMagRec->delete();

				} 
				
				catch(Exception $e){

					generalLib::error($e->getMessage());
					$rc = false;

				}

			} else {

				generalLib::warn('Cannot delete ' . $codeModel . ' \'' . $priceRec->$codeMagAttr . '\' price for part num \'' . $priceRec->$partNumMagAttr  . '\' - does not exist');
			
			}

		} else {

	        if ( $collection->getSize() == 0 ) {

	        	generalLib::mesg('Creating ' . $codeModel . ' \'' . $priceRec->$codeMagAttr . '\' price for part num \'' . $priceRec->$partNumMagAttr . '\'');
		    	$priceMagRec = $this->objectManager->create( $priceMagModel );

	        } else {

	        	generalLib::mesg('Updating ' . $codeModel . ' \'' . $priceRec->$codeMagAttr . '\' price for part num \'' . $priceRec->$partNumMagAttr . '\'');
				$priceMagRec = $collection->getFirstItem();

	        }

			$updAttrs = magentoImport::processMagAttrs($priceMagRec, $priceRec, $priceMagAttrs);

			if ($updAttrs) {

				try {

					generalLib::mesg('Saving ' . $codeModel . ' \'' . $priceRec->$codeMagAttr . '\' price for part num \'' . $priceRec->$partNumMagAttr . '\'');
					$priceMagRec->save();

				}
				
				catch(Exception $e) {

					generalLib::error($e->getMessage());
					generalLib::error($codeModel . ' \'' . $priceRec->$codeMagAttr . '\' price not saved for part num \'' . $priceRec->$partNumMagAttr . '\'');
					$rc = false;

				}
						
			} else {

				generalLib::mesg('No ' . $priceModel . ' updates to apply for ' . $codeModel . ' \'' . $priceRec->$codeMagAttr . '\' price for part num \'' . $priceRec->$partNumMagAttr . '\'');

			}
		}


		if ($rc) {

			if (isset($this->procConfig->{ $codeModel . '_list' } [ $priceRec->$codeMagAttr ]) ) {

				//echo 'actionType = ' . $actionType . "\n";

				//echo 'customOptName = ' . $customOptName . "\n";

				//echo 'codeMagAttr = ' . $codeMagAttr . "\n";

				//echo 'priceMagAttr = ' . $priceMagAttr . "\n";

				$retArr = $this->buildShadeCustomOption($actionType, $product, $customOptName,  $priceRec->$codeMagAttr, $priceRec->$priceMagAttr);

				$rc = $retArr['rc'];

				if ($rc) {
					$rc = $this->chkProdCustOptions( $product->getId() );
				}

			} else {

				generalLib::warn('Undefined ' . $codeModel . ' \'' . $priceRec->$codeMagAttr . '\' - skipping');
				$rc = false;

			}

			if ($rc && $retArr['updProd']) {

				if (!isset( $this->procConfig->shadePartNumsToChk[ $priceRec->$partNumMagAttr ]) ) {

					$this->procConfig->shadePartNumsToChk[ $priceRec->$partNumMagAttr ] = $priceRec->$partNumMagAttr;

				}

			}
		}

		$retArr['rc'] = $rc;
		//die();

		return $retArr;
	}


	//###########################
	public function processShade_Silk_ColoursWebService($actionType, $shadeColourRec) {
		
		$rc = true;

		$retArr = array();
		
		generalLib::mesg('');
		generalLib::mesg('====');

		print_r($shadeColourRec);
		
		$silkColourModel = $this->bespConfProcConfig->bespConfBaseModel . 'Shadesilkcolours';

		$collection = $this->objectManager->create( $silkColourModel )
								->getCollection()
								->addFieldToFilter('dar_id', $shadeColourRec->id);
		
		if ($actionType == 'D') {

			if ( $collection->getSize() > 0 ) {

				try {
					generalLib::mesg('deleting colour for dar id \'' . $shadeColourRec->id . '\'');
					$silkColourMagRec = $collection->getFirstItem();
					$silkColourMagRec->delete();
				}
	
				catch (Exception $e) {
					generalLib::error($e->getMessage());
					generalLib::error('Silk colour not deleted for dar id \'' . $shadeColourRec->id . '\'');
					$rc = false;
				}

			} else {

				generalLib::warn('Cannot delete - no silk colour record for dar id \'' . $shadeColourRec->id . '\' - does not exist');

			}
			

			$retArr['rc'] = $rc;

			return $retArr;

		} else {

			if ( $collection->getSize() == 0 ) {

	        	generalLib::mesg('Creating silk colour for dar id \'' . $shadeColourRec->id . '\'');

	        	$silkColourMagRec = $this->objectManager->create( $silkColourModel );

	        } else {

	        	generalLib::mesg('Updating silk colour for dar id \'' . $shadeColourRec->id . '\'');
				$silkColourMagRec = $collection->getFirstItem();

	        }
			
			$updAttrs = false;

			$updColourRec = magentoImport::processMagAttrs($silkColourMagRec, $shadeColourRec, $this->bespConfProcConfig->shadeSilkColourMagAttrs);

			if ($updColourRec && ( !$updAttrs ) ) {
				$updAttrs = true;
			}


			$updColourRec = magentoImport::processCustomAttrs($this, $silkColourMagRec, $shadeColourRec, $this->bespConfProcConfig->shadeSilkColourCustomAttrs, $this->bespConfProcConfig->shadeSilkColourCustomMappings);

			if ($updColourRec && ( !$updAttrs ) ) {
				$updAttrs = true;
			}

			if ($updAttrs) {

				try {
					generalLib::mesg('Saving silk colour for dar id \'' . $shadeColourRec->id . '\'');
					$silkColourMagRec->save();
				}
				
				catch(Exception $e) {
					generalLib::error($e->getMessage());
					generalLib::error('Silk colour for dar id \'' . $shadeColourRec->id . '\' not saved');
					$rc = false;
				}
						
			} else {
				generalLib::mesg('No silk colour updates to apply for dar id \'' . $shadeColourRec->id . '\'');
			}

		}
		
		$retArr['rc'] = $rc;

		return $retArr;
	}


	//###########################
	public function processDHL_Default_ShadesWebService($actionType, $defaultShadeRec) {
		
		$rc = true;

		$retArr = array();
		
		generalLib::mesg('');
		generalLib::mesg('====');

		print_r($defaultShadeRec);
		
		$defaultShadeModel = $this->bespConfProcConfig->bespConfBaseModel . 'Defaultshades';

		$collection = $this->objectManager->create( $defaultShadeModel )
								->getCollection()
								->addFieldToFilter('dhl_partnum', $defaultShadeRec->Item_No);
		
		if ($actionType == 'D') {

			if ( $collection->getSize() > 0 ) {

				try {
					generalLib::mesg('Deleting default shade for item no \'' . $defaultShadeRec->Item_No . '\'');
					$defaultShadeMagRec = $collection->getFirstItem();
					$defaultShadeMagRec->delete();
				}
	
				catch (Exception $e) {
					generalLib::error($e->getMessage());
					generalLib::error('Default shade not deleted for item no \'' . $defaultShadeRec->Item_No . '\'');
					$rc = false;
				}

			} else {

				generalLib::warn('Cannot delete - no default shade record for item no  \'' . $defaultShadeRec->Item_No . '\' - does not exist');

			}
			

			$retArr['rc'] = $rc;

			return $retArr;

		} else {

			if ( $collection->getSize() == 0 ) {

	        	generalLib::mesg('Creating default shade for item no \'' . $defaultShadeRec->Item_No . '\'');

	        	$defaultShadeMagRec = $this->objectManager->create( $defaultShadeModel );

	        } else {

	        	generalLib::mesg('Updating default shade for item no \'' . $defaultShadeRec->Item_No . '\'');
				$defaultShadeMagRec = $collection->getFirstItem();

	        }
			
			$updAttrs = magentoImport::processMagAttrs($defaultShadeMagRec, $defaultShadeRec, $this->bespConfProcConfig->defaultShadeMagAttrs);

			if ($updAttrs) {

				try {
					generalLib::mesg('Saving default shade for item no \'' . $defaultShadeRec->Item_No . '\'');
					$defaultShadeMagRec->save();
				}
				
				catch(Exception $e) {
					generalLib::error($e->getMessage());
					generalLib::error('Default shade for item no \'' . $defaultShadeRec->Item_No . '\' not saved');
					$rc = false;
				}
						
			} else {
				generalLib::mesg('No default shade updates to apply for item no \'' . $defaultShadeRec->Item_No . '\'');
			}

		}
		
		$retArr['rc'] = $rc;

		return $retArr;
	}


	//###########################
	public function processShade_StylesWebService($actionType, $styleRec) {
		$rc = true;

		$retArr = array();

		generalLib::mesg('');
		generalLib::mesg('====');

		print_r($styleRec);

		$styleModel = $this->bespConfProcConfig->bespConfBaseModel . 'Style';

		$collection = $this->objectManager->create( $styleModel )
								->getCollection()
								->addFieldToFilter('style_code', $styleRec->style_code);

		//$collection = Mage::getModel('shadestyles/shadestyle')->getCollection()
                //->addFieldToFilter('style_code', $styleRec->style_code);

         if ($actionType == 'D') {

			if ( $collection->getSize() > 0 ) {
				try {
					generalLib::mesg('deleting style code \'' . $styleRec->style_code . '\'');
					$styleMagRec = $collection->getFirstItem();
					$styleMagRec->delete();
				} 
				
				catch(Exception $e){
					generalLib::error($e->getMessage());
					$rc = false;
				}
			} else {
				generalLib::warn('Cannot delete style code \'' . $styleRec->style_code . '\' - does not exist');
			}

		} else {

			if ( $collection->getSize() == 0 ) {

	        	generalLib::mesg('Creating style code \'' . $styleRec->style_code . '\'');

	        	$styleMagRec = $this->objectManager->create( $styleModel );

	        } else {

	        	generalLib::mesg('Updating style code \'' . $styleRec->style_code . '\'');
				$styleMagRec = $collection->getFirstItem();

	        }
			
			$updAttrs = magentoImport::processMagAttrs($styleMagRec, $styleRec, $this->bespConfProcConfig->styleMagAttrs);

			if ($updAttrs) {
				try {
					generalLib::mesg('Saving style code \'' . $styleRec->style_code . '\'');
					$styleMagRec->save();
				}
				
				catch(Exception $e) {
					generalLib::error($e->getMessage());
					generalLib::error('Style \'' . $styleRec->style_code . '\' not saved');
					$rc = false;
				}
						
			} else {
				generalLib::mesg('No style updates to apply for style code \'' . $styleRec->style_code . '\'');
			}
		}

		$retArr['rc'] = $rc;

		return $retArr;
	}


	//###########################
	public function processShade_AccessoryWebService($actionType, $shadeAccessoryRec) {
		$rc = true;

		generalLib::mesg('');
		generalLib::mesg('====');

		print_r($shadeAccessoryRec);
				
		$product = $this->getProductForPartnum($shadeAccessoryRec->shade_partnum, true);
		
		if (!$product) {
			$rc = false;
			return $rc;
		}

		$accessoryMagModel = $this->bespConfProcConfig->bespConfBaseModel . 'Accessory';

		$collection = $this->objectManager->create( $accessoryMagModel )
								->getCollection()
								->addFieldToFilter('shade_partnum', $shadeAccessoryRec->shade_partnum)
								->addFieldToFilter('accessory_type', $shadeAccessoryRec->accessory_type)
                						->addFieldToFilter('accessory_sku', $shadeAccessoryRec->accessory_partnum);

        if ($actionType == 'D') {

			if ( $collection->getSize() > 0 ) {

				try {
					generalLib::mesg('deleting accessory \'' . $shadeAccessoryRec->accessory_partnum . '\' for shade part num \'' . $shadeAccessoryRec->shade_partnum . '\' and type \'' . $shadeAccessoryRec->accessory_type . '\'');
					$accessoryMagRec = $collection->getFirstItem();
					$accessoryMagRec->delete();
				} 
				
				catch(Exception $e){
					generalLib::error($e->getMessage());
					$rc = false;
				}

			} else {
				generalLib::warn('Cannot delete accessory \'' . $shadeAccessoryRec->accessory_partnum . '\' for shade part num \'' . $shadeAccessoryRec->shade_partnum . '\' and type \'' . $shadeAccessoryRec->accessory_type . '\' - does not exist');
			}

		} else {

	        if ( $collection->getSize() == 0 ) {

	        	generalLib::mesg('Creating accessory \'' . $shadeAccessoryRec->accessory_partnum . '\' for shade part num \'' . $shadeAccessoryRec->shade_partnum . '\' and type \'' . $shadeAccessoryRec->accessory_type . '\'');
		    	$accessoryMagRec = $this->objectManager->create( $accessoryMagModel );

	        } else {

	        	generalLib::mesg('Updating accessory \'' . $shadeAccessoryRec->accessory_partnum . '\' for shade part num \'' . $shadeAccessoryRec->shade_partnum . '\' and type \'' . $shadeAccessoryRec->accessory_type . '\'');
				$accessoryMagRec = $collection->getFirstItem();

	        }
			
			$updAttrs = magentoImport::processMagAttrs($accessoryMagRec, $shadeAccessoryRec, $this->bespConfProcConfig->accessoryMagAttrs);

			if ($updAttrs) {

				try {
					generalLib::mesg('Saving accessory \'' . $shadeAccessoryRec->accessory_partnum . '\' for type \'' . $shadeAccessoryRec->accessory_type . '\'');
					$accessoryMagRec->save();
				}
				
				catch(Exception $e) {
					generalLib::error($e->getMessage());
					generalLib::error('Accessory \'' . $shadeAccessoryRec->accessory_partnum . '\' for type \'' . $shadeAccessoryRec->accessory_type . '\' not saved for shade part num \'' . $shadeAccessoryRec->shade_partnum . '\'');
					$rc = false;
				}
						
			} else {
				generalLib::mesg('No accessory updates to apply for \'' . $shadeAccessoryRec->accessory_partnum . '\' for type \'' . $shadeAccessoryRec->accessory_type . '\'');
			}
		}

		return $rc;
	}
	
}

?>
