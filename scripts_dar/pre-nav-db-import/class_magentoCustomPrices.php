<?php
class magentoCustomPrices {
	static private $currDate;
	static private $currDateTStamp;
	static private $procObj;
	static private $postCustomPriceAppliedFunc = 'postAppliedCustomPrice';
	static private $postExpiredCustomPriceFunc = 'postExpiredCustomPrice';


	//###########################
	static public function priceConfig($procObj) {
		self::$procObj = $procObj;
		self::setCurrDate();
	}


	//###########################
	public static function setCurrDate() {
		self::$currDate = date('Y-m-d');

		self::$currDateTStamp = strtotime( date('Y-m-d') );

		//TEMP
		//self::$currDateTStamp = strtotime( '2016-05-01' );
	}
	
	
	//###########################
	public static function processCustomPrice($actionType, $product, $discountData) {
		$rc = true;
		$genExtId = false;

		if ( isset($discountData->priceAttr) ) {
			$priceInfo = ' Price Attr \'' . $discountData->priceAttr . '\'';
		} else {
			$priceInfo = ' Price Qty \'' . $discountData->priceQty . '\'';
		}

		generalLib::mesg('Processing discount for Store ID \'' . $discountData->storeId . '\' Website ID \'' . $discountData->websiteId . 
							'\' Item No \'' . $discountData->productNo . '\'' . ' Cust No \'' . $discountData->customerNo . '\'' . $priceInfo . ':');

		print_r($discountData);

		if ($discountData->startDate == '0001-01-01') {
			$discountData->startDate = date('Y-m-d');
		}

		if ($discountData->endDate == '0001-01-01') {
			$discountData->endDate = '2100-01-01';
		}

		if ( !isset($discountData->priceAttr) ) {

			//TIER PRICING MUST START FROM 1 IN MAGENTO
			if ($discountData->priceQty == '0' ) {
				$discountData->priceQty = '1';	
			}

		}

		$startDateFuture = (strtotime($discountData->startDate) > self::$currDateTStamp);
		$endDateExp = ( strtotime($discountData->endDate) < self::$currDateTStamp );

		//echo 'endDateExp = ' . $endDateExp . "\n";
		//print_r($discountData);
		//die();

		$allowMultipleDateRanges = false;

		if (isset($discountData->extId)) {

			if ($discountData->extId != '') {
				$allowMultipleDateRanges = true;
			}

		}

		if ($allowMultipleDateRanges) {
			if ($discountData->extId != 'GENERATE') {
				$oldTrkDisc = self::getExtDiscountDetails( $discountData->extId );
			} else {
				$discountData->extId = '';
				$genExtId = true;
				$oldTrkDisc = null;
			}

		} else {
			$oldTrkDisc = self::getDiscountDetails($discountData->stockId, $discountData->websiteId, $discountData->productNo, $discountData->customerNo, $discountData->priceQty);
		}

		//$retArr = self::chkActiveDiscs('18', $product, $discountData);

		//print_r($retArr);
		//die();

		if ($oldTrkDisc == null) {

			if ($actionType == 'D') {
				generalLib::mesg('Cannot delete track discount for Store ID \'' . $discountData->storeId . '\' Website ID \'' . $discountData->websiteId .
							'\' Item No \'' . $discountData->productNo . '\'' . ' Cust No \'' . $discountData->customerNo . $priceInfo . 
							' - Discount not found');
				//RETURN TRUE AS A TRACK DISCOUNT RECORD COULD HAVE BEEN DELETED AS IT EXPIRED, AND NOW A DELETION REQUEST HAS COME IN, SO ALTHOUGH IT HAS
				//ALREADY BEEN DELETED, IT CAN STILL BE FLAGGED AS DELETED
				//$rc = false;
				return $rc;
			}

			if ($endDateExp) {
				generalLib::mesg('Cannot create track discount for Store ID \'' . $discountData->storeId . '\' Website ID \'' . $discountData->websiteId .
							'\' Item No \'' . $discountData->productNo . '\'' . ' Cust No \'' . $discountData->customerNo . $priceInfo . 
							' - Discount already expired on \'' . $discountData->endDate . '\'');

				$rc = -2;
				return $rc;
			}
			
			/*
			if ($allowMultipleDateRanges) {
				if (self::gotDiscDateOverLap('insert', $discountData)) {
					$rc = false;
					return $rc;
				}
			}
			*/
			
			//CREATE TRACKDISCOUNT RECORD

			$magCustomPrice = self::$procObj->objectManager->create('StarDigital\CustomPrices\Model\Customprices');

			$magCustomPrice->setExtId( $discountData->extId );
			$magCustomPrice->setStoreId( $discountData->storeId );
			$magCustomPrice->setWebsiteId( $discountData->websiteId );
			$magCustomPrice->setProductNo( $discountData->productNo );
			$magCustomPrice->setCustomerNo( $discountData->customerNo );
			$magCustomPrice->setSource( $discountData->source );
			$magCustomPrice->setStartDate( $discountData->startDate );
			$magCustomPrice->setEndDate( $discountData->endDate );
			$magCustomPrice->setLastUpdatedDate( self::$currDate );

			if ( isset($discountData->priceAttr) ) {

				$magCustomPrice->setPriceAttr( $discountData->priceAttr );
				
			} else {

				$magCustomPrice->setPriceQty( $discountData->priceQty );

			}

			$magCustomPrice->setPrice( $discountData->price );
			
							
			//generalLib::mesg(print_r($trkDisc, true));
			$priceMesg = 'Store ID \'' . $discountData->storeId . '\' Website ID \'' . $discountData->websiteId .
							'\' Item No \'' . $discountData->productNo . '\'' . ' Cust No \'' . $discountData->customerNo . $priceInfo . 
							' Dates (' . $discountData->startDate . ' to ' . $discountData->endDate . ')';

			try {
				generalLib::mesg('Creating custom price for ' . $priceMesg);

				$magCustomPrice->save();
			}
			
			catch(Exception $e) {
				generalLib::error($e->getMessage());
				generalLib::error('custom price for ' . $priceMesg . ' not saved');
				$rc = false;
			}

			if ($rc) {
				$trackId = $magCustomPrice->getId();
			
				generalLib::mesg('Created track discount - tracking id \'' . $trackId . '\'');
				
				if ($genExtId) {					

					$magCustomPrice = self::$procObj->objectManager->get('StarDigital\CustomPrices\Model\Customprices')->load( $trackId );

					if ( $magCustomPrice ) {
						$magCustomPrice->setExtId( $trackId );

						try {
							generalLib::mesg('Updating custom price external id to \'' . $trackId . '\'');
						}

						catch(Exception $e) {
							generalLib::error($e->getMessage());
							generalLib::error('Failed to update custom price for ID \'' . $trackId . '\'');
							$rc = false;
						}
					} else {
						generalLib::error('Failed to load custom price for ID \'' . $trackId . '\'');
						$rc = false;
					}

				}

				if ($startDateFuture) {
					$rc = self::setDiscActiveInd($trackId, 'N', $discountData);
				} else {
					$retArr = self::chkActiveDiscs($trackId, $product, $discountData);

					if ($retArr['rc']) {

						if (!$retArr['updProd']) {
											
							generalLib::warn('No attempt made to create price for Magento Store ID \'' . $discountData->storeId . '\' Website ID \'' . $discountData->websiteId .
											'\' Item No \'' . $discountData->productNo . '\'' . ' Cust No \'' . $discountData->customerNo . $priceInfo);
						}
					}
				}
			}
			
			return $rc;
		}
		

		if ($actionType == 'D') {
			generalLib::mesg('Deleting track discount for Store ID \'' . $discountData->storeId . '\' Website ID \'' . $discountData->websiteId .
								'\' Item No \'' . $discountData->productNo . '\'' . ' Cust No \'' . $discountData->customerNo . $priceInfo . 
								' - Discount expired on \'' . $discountData->endDate . '\'');
								
			$rc = self::deleteAttr( $oldTrkDisc->getId() );
		
			//CHECK FOR ANY OTHER ACTIVE DISCOUNTS FOR THIS ITEM NO/CUST NO/TIER QTY COMBINATION - IF FOUND, CANNOT DELETE MAGENTO TIER PRICE - COMMENTED OUT
			//if ($allowMultipleDateRanges) {
				//if (!self::gotActiveDiscount($discountData)) {

					$retArr = self::processMagDiscount('D', $product, $discountData);

					if ($retArr['rc']) {
						$retArr = self::chkInActiveDiscs($product, $discountData);

						$rc = $retArr['rc'];
					}

				//}	
			//}

			return $rc;
		}
		
		$updPrice = false;

		$startDateUpd = false;

		$endDateUpd = false;

		//AS THE DATES ARE PART OF THE LOOKUP, THIS CODE IS NO LONGER APPLICABLE
		if ($oldTrkDisc->getStartDate() != $discountData->startDate) {
			$oldTrkDisc->setStartDate( $discountData->startDate );
			generallib::mesg('setting start date to \'' . $discountData->startDate . '\'');
			$startDateUpd = true;
			$updPrice = true;
		}
		
		if ($oldTrkDisc->getEndDate() != $discountData->endDate) {
			$oldTrkDisc->setEndDate( $discountData->endDate );
			generallib::mesg('setting end date to \'' . $discountData->endDate . '\'');
			$endDateUpd = true;
			$updPrice = true;
		}
		
		
		//echo "discount data\n";
		//print_r($discountData);

			//echo "here1\n";

		if ($oldTrkDisc->getPrice() != $discountData->price) {
			$oldTrkDisc->setPrice( $discountData->price );
			generallib::mesg('setting price to \'' . $discountData->price . '\'');
			$updPrice = true;
		}

		//echo "oldTrkDisc\n";
		//print_r($oldTrkDisc->getData());

		//echo "updTrkDisc\n";
		//print_r($updTrkDisc);
		/*
		if ($allowMultipleDateRanges  && ( isset($updTrkDisc['start_date']) || isset($updTrkDisc['end_date']) ) ) {
			if (self::gotDiscDateOverLap('update', $discountData)) {
				$rc = false;
				return $rc;
			}
		}
		*/

		//TEMP - ALLOWS MAGENTO TO BE UPDATED, IF THE CUSTOM PRICE RECORD IS ALREADY UPDATED
		//$updPrice = true;
		
		if (!$updPrice) {
			generalLib::mesg('No updates to apply for Store ID \'' . $discountData->storeId . '\' Website ID \'' . $discountData->websiteId .
							'\' Item No \'' . $discountData->productNo . '\'' . ' Cust No \'' . $discountData->customerNo . '\'' . $priceInfo);
			return $rc;
		}
		
		//IF THE SAME TIER PRICE IS RECEIVED MORE THAN ONCE ON THE SAME DAY WITH THE SAME DATES, ALWAYS APPLY THE HIGHER PRICE AND REJECT ANY OTHERS WHICH ARE LOWER
		/*
		if ( isset($updTrkDisc['tier_price']) ) {
			if ( ($updTrkDisc['tier_price'] < $oldTrkDisc['tier_price']) && ($oldTrkDisc['last_updated_date'] == self::$currDate)) {
				generalLib::mesg('Tier price \'' . $updTrkDisc['tier_price'] . '\' is lower than the tier price \'' . $oldTrkDisc['tier_price'] . '\' already received today - aborting import');
				return $rc;
			}
		}
		*/
		
		$procMagDisc = false;

		
		if ( $updPrice ) {
			$oldTrkDisc->setLastUpdatedDate( self::$currDate );

			$mesg = 'Store ID \'' . $discountData->storeId . '\' Website ID \'' . $discountData->websiteId .
						'\' Item No \'' . $discountData->productNo . '\'' . ' Cust No \'' . $discountData->customerNo . $priceInfo;
			
			try {
				generalLib::mesg('Updating custom price for ' . $mesg);

				$oldTrkDisc->save();
			}
			
			catch(Exception $e) {
				generalLib::error($e->getMessage());
				generalLib::error('custom price for ' . $mesg . ' not saved');
				$rc = false;
				return $rc;
			}

		
			//IF DISCOUNT IS EITHER IN THE FUTURE OR HAS EXPIRED - REMOVE TIER PRICE FROM MAGENTO
			//IF EXPIRED - REMOVE FROM TRACKDISCOUNT TABLE
			if ( ( $startDateUpd  && $startDateFuture ) ||
				 ( $endDateUpd && $endDateExp ) ) {
				
				if ($startDateFuture) {
					$rc = self::setDiscActiveInd( $oldTrkDisc->getId(), 'N', $discountData );

					if (!$rc) {
						return $rc;
					}
				} else {

					generalLib::mesg('Deleting custom price for Store ID \'' . $discountData->storeId . '\' Website ID \'' . $discountData->websiteId .
								'\' Item No \'' . $discountData->productNo . '\'' . ' Cust No \'' . $discountData->customerNo . $priceInfo . 
								' - Discount expired on \'' . $discountData->endDate . '\'');
								
					$rc = self::deleteAttr( $oldTrkDisc->getId() );

					if (!$rc) { 
						return $rc;
					}

				}

				//$delMagDisc = true;

				//CHECK FOR ANY OTHER ACTIVE DISCOUNTS FOR THIS ITEM NO/CUST NO/TIER QTY COMBINATION - IF FOUND, CANNOT DELETE MAGENTO TIER PRICE
				/*
				if ($allowMultipleDateRanges) {
					if (self::gotActiveDiscount($discountData)) {
						$delMagDisc = false;
					}
				}
				*/
			
				//if ($delMagDisc) {

					$retArr = self::processMagDiscount('D', $product, $discountData);

					//echo "after prof mag Disc\n";
					//print_r($retArr);

					$retArr = self::chkInActiveDiscs($product, $discountData);

					$rc = $retArr['rc'];

					//echo 'aft chk rc = ' . $rc . "\n";
				//}

				$procMagDisc = true;
			}
		}
		

		if ( (!$procMagDisc) && (!$startDateFuture) ) {

			$retArr = self::processMagDiscount('U', $product, $discountData);

			if ($retArr['updProd'] && $retArr['rc']) {

				if ($oldTrkDisc->getActive() == 'N') {
					$rc = self::setDiscActiveInd( $oldTrkDisc->getId(), 'Y', $discountData );
				}
			}
		}


		return $rc;
	}
	

	//###########################
	/*
	public static function gotActiveDiscount($discountData) {
		$activeDisc = false;

		$trkDiscColl = Mage::getModel('trackdiscount/trackdiscount')
						->getCollection()
						->addFieldToFilter('store_id', array('eq' => $discountData->storeId))
						->addFieldToFilter('website_id', array('eq' => $discountData->websiteId))
						->addFieldToFilter('item_no', array('eq' => $discountData->productNo))
						->addFieldToFilter('customer_no', array('eq' => $discountData->customerNo))
						->addFieldToFilter('price_qty', array('eq' => $discountData->priceQty))
						->addFieldToFilter('active', array('eq' => 'Y'));

		foreach ($trkDiscColl as $trkDisc) {

			if ( $trkDisc->ext_id != '' && ( $trkDisc->ext_id != $discountData->extId ) ) {
				$activeDisc = true;
				break;
			}

		}

		return($activeDisc);
	}
	*/


	//###########################
	private static function setDiscActiveInd($trackId, $activeInd, $discountData) {
		$rc = true;

		if ( isset($discountData->priceAttr) ) {
			$priceInfo = ' Price Attr \'' . $discountData->priceAttr . '\'';
		} else {
			$priceInfo = ' Price Qty \'' . $discountData->priceQty . '\'';
		}

		$discount = self::$procObj->objectManager->get('StarDigital\CustomPrices\Model\Customprices')->load($trackId);

        if ($discount) {

        	$discount->setActive( $activeInd );

        	$mesg = 'track id \'' . $trackId . '\' Store ID \'' . $discountData->storeId . 
						'\' Website ID \'' . $discountData->websiteId .
						'\' Item No \'' . $discountData->productNo . '\'' . ' Cust No \'' . $discountData->customerNo . '\'' . $priceInfo . 
						' Dates (' . $discountData->startDate . ' to ' . $discountData->endDate . ') - active \'' . $activeInd . '\'';

			try {
				generalLib::mesg('Updating custom price for ' . $mesg);
				$discount->save();
			}

			catch(Exception $e) {
				generalLib::error($e->getMessage());
				generalLib::error('Cannot update custom price for ' . $mesg);
				$rc = false;
			}

		} else {
			generalLib::error('Cannot update custom price for ID \'' . $trackId . '\' - does not exist');
			$rc = false;
		}

		return $rc;
	}


	//###########################
	public static function chkActiveDiscs($trkDiscId, $product, $discountData) {
		//IT IS NOW POSSIBLE TO HAVE OVERLAPPING DISCOUNTS
		//WHEN ADDING A DISCOUNT, CHECK FOR OTHER ACTIVE DISCOUNTS.
		//THE DISCOUNT WITH THE LATEST START DATE TAKES PRECEDENCE, SO IF THERE IS AN ACTIVE DISCOUNT WITH AN
		//EARLIER START DATE THEN THE NEW DISCOUNT, IT WILL BE MADE IN-ACTIVE IN FAVOUR OF THE NEW DISCOUNT.
		//HOWEVER, IF THE NEW DISCOUNT HAS AN EARLIER START DATE THEN AN EXISTING ACTIVE DISCOUNT, THE 
		//EXISTING DISCOUNT WILL TAKE PRECEDENCE.

		$rc = true;
		$updProd = false;

		$discStartDate = strtotime($discountData->startDate);

		$discEndDate = strtotime($discountData->endDate);

		//echo 'StartDate = ' . $discountData->startDate . "\n";
		//echo 'discStartDte = ' . $discStartDate . "\n";
		//print_r($discountData);

		$trkActiveDiscColl = self::$procObj->objectManager->get('StarDigital\CustomPrices\Model\Customprices')
						->getCollection()
						->addFieldToFilter('store_id', array('eq' => $discountData->storeId))
						->addFieldToFilter('website_id', array('eq' => $discountData->websiteId))
						->addFieldToFilter('product_no', array('eq' => $discountData->productNo))
						->addFieldToFilter('customer_no', array('eq' => $discountData->customerNo));

		if ( isset( $discountData->priceAttr ) ) {
			$trkActiveDiscColl->addFieldToFilter('price_attr', array('eq' => $discountData->priceAttr) );
		} else {
			$trkActiveDiscColl->addFieldToFilter('price_qty', array('eq' => $discountData->priceQty));
		}

		$trkActiveDiscColl->addFieldToFilter('active', array('eq' => 'Y'));
		$trkActiveDiscColl->setOrder('id', 'ASC');

		$setNewDiscActive = true;
		$delOldDiscActive = false;

		foreach ($trkActiveDiscColl as $trkActiveDisc) {
			//echo "trackdiscount record\n";
			//print_r($trkActiveDisc->getData());

			//echo 'active start date = ' . $trkActiveDisc->getCustomerStartDate() . "\n";

			$activeDiscStartDate = strtotime( $trkActiveDisc->getCustomerStartDate() );

			$activeDiscEndDate = strtotime( $trkActiveDisc->getCustomerEndDate() );

			//echo 'activeDiscStartDate = ' . $activeDiscStartDate . "\n";

			if ( ( $discStartDate > $activeDiscStartDate ) || ( $discStartDate == $activeDiscStartDate && $discEndDate < $activeDiscEndDate ) ) {
				$priceAttr = $trkActiveDisc->getPriceAttr();

				$currActiveDiscountData = new Stdclass();
				$currActiveDiscountData->storeId = $trkActiveDisc->getStoreId();
				$currActiveDiscountData->websiteId = $trkActiveDisc->getWebsiteId();
				$currActiveDiscountData->productNo = $trkActiveDisc->getProductNo();
				$currActiveDiscountData->customerNo = $trkActiveDisc->getCustomerNo();
				$currActiveDiscountData->startDate = $trkActiveDisc->getStartDate();
				$currActiveDiscountData->endDate = $trkActiveDisc->getEndDate();

				if (!empty( $priceAttr ) ) {
					$currActiveDiscountData->priceAttr = $priceAttr;
				} else {
					$currActiveDiscountData->priceQty = $trkActiveDisc->getPriceQty();
				}

				$currActiveDiscountData->price = $trkActiveDisc->getPrice();

				//echo "discount to delete from magento:\n";
				//print_r($currActiveDiscountData);


				//echo "bef deletion tier prices\n";

				//$prodTierPrices = $product->getData('tier_price');
				//print_r($prodTierPrices);

				$retArr = self::processMagDiscount('D', $product, $currActiveDiscountData);

				//echo "after deletion\n";
				//echo 'track id = ' . $trkActiveDisc->getId() . "\n";
				//echo 'rc = ' . $retArr['rc'] . "\n";

				if ($retArr['rc']) {
					$rc = self::setDiscActiveInd( $trkActiveDisc->getId(), 'N', $currActiveDiscountData);
					$delOldDiscActive = true;
				}
				
			} else {
				$setNewDiscActive = false;
			}

			if (!$rc) {
				break;
			}
		}

		//echo 'rc = ' . $rc . "\n";
		//echo 'setNewDiscActive = ' . $setNewDiscActive . "\n";
		//echo 'delOldDiscActive = ' . $delOldDiscActive . "\n";
		//die();

		if ($rc && $setNewDiscActive) {

			if ( $delOldDiscActive ) {
				$productId = $product->getId();
				unset($product);

				$product = self::$procObj->objectManager->create('Magento\Catalog\Model\Product')->setStoreId( $discountData->storeId )->load( $productId );
				//echo "after re-read - tier prices\n";
				//$prodTierPrices = $product->getData('tier_price');
				//print_r($prodTierPrices);
			}

			$retArr = self::processMagDiscount('U', $product, $discountData);

			if ($retArr['updProd']) {
						
				if ($retArr['rc']) {
					$rc = self::setDiscActiveInd($trkDiscId, 'Y', $discountData);	
				} else {
					$rc = $retArr['rc'];
				}

			}

			$updProd = $retArr['updProd'];
		}

		return array('rc' => $rc, 'updProd' => $updProd);
	}


	//###########################
	public static function chkInActiveDiscs($product, $discountData) {

		//IT IS NOW POSSIBLE TO HAVE OVERLAPPING DISCOUNTS
		//WHEN DELETING AN ACTIVE DISCOUNT, CHECK WHETHER THERE ARE ANY INACTIVE DISCOUNTS, WHICH HAVE NOT EXPIRED
		//THE DISCOUNT WITH THE LATEST START DATE WILL BE MADE ACTIVE

		$rc = true;

		$updProd = false;

		$chkDate = date('Y-m-d');
		//TEMP
		//$chkDate = '2016-07-26';


		$trkInactiveDiscColl = self::$procObj->objectManager->get('StarDigital\CustomPrices\Model\Customprices')
						->getCollection()
						->addFieldToFilter('store_id', array('eq' => $discountData->storeId))
						->addFieldToFilter('website_id', array('eq' => $discountData->websiteId))
						->addFieldToFilter('product_no', array('eq' => $discountData->productNo))
						->addFieldToFilter('customer_no', array('eq' => $discountData->customerNo))
						->setOrder('id', 'ASC')
						;

		if ( isset( $discountData->priceAttr ) ) {
			$trkInactiveDiscColl->addFieldToFilter('price_attr', array('eq' => $discountData->priceAttr) );
		} else {
			$trkInactiveDiscColl->addFieldToFilter('price_qty', array('eq' => $discountData->priceQty));
		}

		$trkInactiveDiscColl->addFieldToFilter('active', array('eq' => 'N'));
		$trkInactiveDiscColl->addFieldToFilter('end_date', array('from' => $chkDate));
		$trkInactiveDiscColl->setOrder('id', 'ASC');

		$latestInactiveStartDate = 0;
		$latestInActiveTtkId = 0;
		$latestInActDiscountData = new stdClass();

		foreach ($trkInactiveDiscColl as $trkInactiveDisc) {
			//echo "inactive discount:\n";
			//print_r($trkInactiveDisc->getData());

			$inactiveDiscStartDate = strtotime( $trkInactiveDisc->getStartDate() );

			if ($latestInactiveStartDate == 0 || ( $inactiveDiscStartDate > $latestInactiveStartDate ) ) {
				$priceAttr = $trkInactiveDisc->getPriceAttr();

				if ( $priceAttr != $discountData->priceAttr ) {
					continue;
				}

				$latestInactiveStartDate = $inactiveDiscStartDate;
				$latestInActiveTtkId = $trkInactiveDisc->getId();

				$latestInActiveDiscountData = new Stdclass();
				$latestInActiveDiscountData->storeId = $trkInactiveDisc->getStoreId();
				$latestInActiveDiscountData->websiteId = $trkInactiveDisc->getWebsiteId();
				$latestInActiveDiscountData->productNo = $trkInactiveDisc->getProductNo();
				$latestInActiveDiscountData->customerNo = $trkInactiveDisc->getCustomerNo();
				$latestInActiveDiscountData->startDate = $trkInactiveDisc->getStartDate();
				$latestInActiveDiscountData->endDate = $trkInactiveDisc->getEndDate();

				if ( !empty($priceAttr) ) {
					$latestInActiveDiscountData->priceAttr = $priceAttr;
				} else {
					$latestInActiveDiscountData->priceQty = $trkInactiveDisc->getPriceQty();
				}

				$latestInActiveDiscountData->price = $trkInactiveDisc->getPrice();
			}
		}

		if ($latestInActiveTtkId != 0) {

			//echo "discount being made active: \n";
			//print_r($latestInActiveDiscountData);

			$magProdId = $product->getId();
			unset($product);

			$product = self::$procObj->objectManager->create('Magento\Catalog\Model\Product')->load($magProdId);

			$retArr = self::processMagDiscount('U', $product, $latestInActiveDiscountData);

			if ($retArr['updProd']) {
						
				if ($retArr['rc']) {
					$rc = self::setDiscActiveInd($latestInActiveTtkId, 'Y', $latestInActiveDiscountData);	
				} else {
					$rc = $retArr['rc'];
				}

			}

			$updProd = $retArr['updProd'];
		}

		//return $rc;
		return array('rc' => $rc, 'updProd' => $updProd);
	}


	//###########################
	/*
	public static function gotDiscDateOverLap($discAction, $discountData) {
		$gotOverlap = false;

		$trkDiscColl = Mage::getModel('trackdiscount/trackdiscount')
						->getCollection()
						->addFieldToFilter('store_id', array('eq' => $discountData->storeId))
						->addFieldToFilter('website_id', array('eq' => $discountData->websiteId))
						->addFieldToFilter('item_no', array('eq' => $discountData->productNo))
						->addFieldToFilter('customer_no', array('eq' => $discountData->customerNo))
						->addFieldToFilter('price_qty', array('eq' => $discountData->priceQty));
	
		//print_r($discountData);

		$discDataStartTStamp = strtotime($discountData->startDate);
		$discDataEndTStamp = strtotime($discountData->endDate);
		
		foreach ($trkDiscColl as $trkDisc) {
			$trkDiscStartDate = $trkDisc->getCustomerStartDate();
			$trkDiscEndDate = $trkDisc->getCustomerEndDate();

			$trkDiscStartTStamp = strtotime( $trkDiscStartDate );
			$trkDiscEndTStamp = strtotime( $trkDiscEndDate );
			
			//print_r($trkDisc->getData());
			
			//IF THIS DISCOUNT ALREADY EXISTS DO NOT CHECK THE NEW DISCOUNT RECORD AGAINST IT, AS IT'S BEING REPLACED WITH THIS DISCOUNT DATA
			if ( $trkDisc->ext_id != '' && ( $trkDisc->ext_id != $discountData->extId ) ) {
				if ( ($discDataStartTStamp <= $trkDiscEndTStamp) && ($trkDiscStartTStamp <= $discDataEndTStamp) ) {
					generalLib::warn('Cannot ' . $discAction . ' track discount for Store ID \'' . $discountData->storeId . '\' Website ID \'' . $discountData->websiteId .
							'\' Item No \'' . $discountData->productNo . '\'' . ' Cust No \'' . $discountData->customerNo . 
							'\' Tier Qty \'' . $discountData->priceQty . '\' - Date range overlap ( ' . $discountData->startDate . ' to ' . $discountData->endDate . 
							' with existing ' . $trkDiscStartDate . ' to ' . $trkDiscEndDate . ' )');
					
					$gotOverlap = true;
				}
			}
		}

		return $gotOverlap;
	}
	*/

	//###########################
	private static function processMagDiscount($actionType, $product, $discountData) {

		if (isset($discountData->priceAttr)) {
			$retArr = self::processMagentoSpecialPrice($actionType, $product, $discountData);

		} else {
			$retArr = self::processMagentoTierPrice($actionType, $product, $discountData);

			//$prodTierPrices1 = $product->getData('tier_price');
			//echo "product tier data\n";
			//print_r($prodTierPrices1);

			//$prodTierPrices2 = $retArr['product']->getData('tier_price');
			//echo "retarr product tier data\n";
			//print_r($prodTierPrices2);
		}

		if ($retArr['rc']) {
			$product = $retArr['product'];

			//echo "before " . self::$postCustomPriceAppliedFunc . "check\n";

			if (method_exists(self::$procObj, self::$postCustomPriceAppliedFunc)) {
				$retArr = self::$procObj->{ self::$postCustomPriceAppliedFunc }($actionType, $retArr['product'], $discountData, $retArr['updProd']);
            }

        } //else {
        	//echo "not got to check\n";
        //}

		//echo 'updProd = ' . $retArr['updProd'] . "\n";
		//echo 'rc = ' . $retArr['rc'] . "\n";
		//print_r($retArr);
		//die();

       if ($retArr['updProd'] && $retArr['rc']) {
			
			try {

				$retArr['product']->save();
				generalLib::mesg('Product saved');
				$retArr['rc'] = true;

		  	} 

		  	catch(Exception $e) {

				generalLib::error('Cannot save product');
				generalLib::error($e->getMessage());
				$retArr['rc'] = false;

		  	}

		} else {
			generalLib::mesg('No magento product update to apply');
		}

        return $retArr;
    }


	//###########################
	private static function processMagentoSpecialPrice($actionType, $product, $specialPriceData) {
		$rc = true;
		$updProd = false;

		$oldSpecialPrice = $product->getData($specialPriceData->priceAttr);

		if ($actionType == 'D') {
			$newSpecialPrice = '';
		} else {
			$newSpecialPrice = $specialPriceData->price;
		}

		//echo 'oldSpecialPrice = ' . $oldSpecialPrice . "\n";
		//echo 'newSpecialPrice = ' . $newSpecialPrice . "\n";

		if ($oldSpecialPrice != $newSpecialPrice) {
			$product->setData($specialPriceData->priceAttr, $newSpecialPrice);

			generalLib::mesg('Setting special price attr \'' . $specialPriceData->priceAttr . '\' to \'' . $newSpecialPrice . '\'');
			$updProd = true;
		} else {
			generalLib::mesg('No magento special price update to apply');
		}

		if ($specialPriceData->priceAttr == 'special_price') {
			$oldSpecialFromDate = $product->getSpecialFromDate();

			if ($actionType == 'D') {
				$newSpecialFromDate = '';
			} else {
				$newSpecialFromDate = $specialPriceData->startDate;
			}

			//echo 'oldSpecialFromDate = ' . $oldSpecialFromDate . "\n";
			//echo 'newSpecialFromDate = ' . $newSpecialFromDate . "\n";

			if ($oldSpecialFromDate != $newSpecialFromDate) {
				$product->setSpecialFromDate( $newSpecialFromDate );

				if ($newSpecialFromDate != '') {
					$product->setSpecialFromDateIsFormated(true);
				} else {
					$product->setSpecialFromDateIsFormated(false);
				}

				generalLib::mesg('Setting special from date to \'' . $newSpecialFromDate . '\'');
				$updProd = true;
			}
		}
		
		return array( 'updProd' => $updProd, 'rc' => $rc, 'product' => $product);
	}


	//###########################
	public static function processMagentoTierPrice($actionType, $product, $tierPriceData) {
		$rc = true;
		$updProd = false;
		$foundTierPrice = false;
							
		if (!($custGroupId = magentoCust::getCustGroupId($tierPriceData->customerNo)))  {
			$rc = false;
			
			return array( 'updProd' => $updProd, 'rc' => $rc);
		}
		
		//echo 'custGroupId = ' . $custGroupId . "\n";

		$prodSku = $product->getSku();

		$prodTierPrices = $product->getData('tier_price');
		
		//print_r($tierPriceData);
		//echo "product tier prices before:\n";
		//print_r($prodTierPrices);
		//die();
		
		foreach ($prodTierPrices as $prodTierPriceKey => $prodTierPrice) {

			if ($prodTierPrice['cust_group'] == $custGroupId && $prodTierPrice['price_qty'] == $tierPriceData->priceQty) {
				
				if ($actionType == 'D') { 
					unset($prodTierPrices[$prodTierPriceKey]);
					$updProd = true;
				} else {
					if ($prodTierPrices[$prodTierPriceKey]['price'] != $tierPriceData->price) {
						$prodTierPrices[$prodTierPriceKey]['price'] = $tierPriceData->price;
						$updProd = true;
					}
				}
				
				$foundTierPrice = true;
				break;
			}
		}

		//echo "product tier prices after:\n";
		//print_r($prodTierPrices);
		//die();
		
		if ($actionType == 'D') { 
			if ($foundTierPrice) {

				generalLib::mesg('Deleting magento tier price for Sku \'' . $prodSku . '\' Website ID \'' . $tierPriceData->websiteId . 
							'\' Item No \'' . $tierPriceData->productNo . '\'' . ' Cust No \'' . $tierPriceData->customerNo . 
							'\' Tier Qty \'' . $tierPriceData->priceQty);

			} else {

				generalLib::mesg('Cannot delete magento tier price for Sku \'' . $prodSku . '\' Website ID \'' . $tierPriceData->websiteId . 
							'\' Item No \'' . $tierPriceData->productNo . '\'' . ' Cust No \'' . $tierPriceData->customerNo . 
							'\' Tier Qty \'' . $tierPriceData->priceQty . ' - not found');

			}
		} else {

			if ($foundTierPrice) {

				if ($updProd) {
					generalLib::mesg('Updating magento tier price for Sku \'' . $prodSku . '\' Website ID \'' . $tierPriceData->websiteId . 
							'\' Item No \'' . $tierPriceData->productNo . '\'' . ' Cust No \'' . $tierPriceData->customerNo . 
							'\' Tier Qty \'' . $tierPriceData->priceQty . ' to \'' . $tierPriceData->price . '\'');
				}

			} else {

				$prodTierPrices[] = array (
							'website_id' => $tierPriceData->websiteId,
							'cust_group' => $custGroupId,
							'price_qty' => $tierPriceData->priceQty,
							'price' => $tierPriceData->price,
							);

				//print_r($prodTierPrices);
							
				generalLib::mesg('Adding magento tier price for Sku \'' . $prodSku . '\' Website ID \'' . $tierPriceData->websiteId . 
							'\' Item No \'' . $tierPriceData->productNo . '\'' . ' Cust No \'' . $tierPriceData->customerNo . 
							'\' Tier Qty \'' . $tierPriceData->priceQty . '\' with price set to \'' . $tierPriceData->price . '\'');
												
				$updProd = true;

			}
		}
		
		if ($updProd) {
			$product->setTierPrice($prodTierPrices);
		} else {
			generalLib::mesg('No magento tier price update to apply for Sku \'' . $prodSku . '\'');
		}
		
		//die();
		return array( 'updProd' => $updProd, 'rc' => $rc, 'product' => $product);
	}
	
	
	//###########################
	public static function scanExpiredDisc() {
		$discountData = new stdClass();
		
		//SCAN FOR EXPIRED TIER PRICES - END DATE < TODAY'S DATE - DELETE THEM FROM THE TRACKDISCOUNT TABLE AND MAGENTO TIER PRICES
		$chkDate = date('Y-m-d', strtotime('- 1 day'));
		//TEMP
		//$chkDate = '2016-04-30';
		//$chkDate = '2016-05-29';
		//$chkDate = '2016-07-25';	//-1 DAY - YESTERDAY
		//$chkDate = '2016-07-31';	//-1 DAY - YESTERDAY
		 
		$expDiscColl = self::$procObj->objectManager->get('StarDigital\CustomPrices\Model\Customprices')
                        ->getCollection()
                        ->addFieldToFilter('end_date', array('to' => $chkDate))
                        //TEMP
                        //->addFieldToFilter('item_no' , array('eq' => 'A03300-lon'))
                        ;
		
		//$prodObj = new stdClass();

		foreach ($expDiscColl as $expiredDisc) {
			generalLib::mesg('');
			generalLib::mesg('====');
		
			$trkDiscId = $expiredDisc->getId();

			print_r($expiredDisc->getData());

			//$discount = Mage::getModel('trackdiscount/trackdiscount')->load($trkDiscId);
			//print_r($discount->getData());
			//die();
		
			
			generallib::mesg('Processing expired discount id \'' . $trkDiscId . '\' :');
			print_r($expiredDisc->getData());
			//continue;
			//die();
			
			$discountData->productNo = $expiredDisc->getProductNo();

			//$prodObj->sku = $discountData->productNo;

			//print_r($prodObj);
		
			$product = false;

			$productId = self::$procObj->objectManager->create('Magento\Catalog\Model\Product')->getIdBySku( $discountData->productNo );

			if ($productId) {
				$product = self::$procObj->objectManager->create('Magento\Catalog\Model\Product')->setStoreId( $expiredDisc->getStoreId() )->load( $productId );
			}

			if (!$product) {
				generalLib::warn('Cannot find product for sku \'' . $discountData->productNo . '\' and store id \'' . $expiredDisc->getStoreId() . '\' - skipping discount');
				continue;
			}

			$discountData->extId = $expiredDisc->getExtId();
			$discountData->storeId = $expiredDisc->getStoreId();
			$discountData->websiteId = $expiredDisc->getWebsiteId();
			$discountData->productNo = $expiredDisc->getProductNo();
			$discountData->customerNo = $expiredDisc->getCustomerNo();
			$discountData->source = $expiredDisc->getSource();

			//HAVE TO DO THIS, AS CANNOT USE empty() AND GET FIELD AT THE SAME TIME - PHP <5.5
			$priceAttr = $expiredDisc->getPriceAttr();

			if ( !empty( $priceAttr ) ) {
				$discountData->priceAttr = $expiredDisc->getPriceAttr();

				$priceInfo = ' Price Attr \'' . $discountData->priceAttr . '\'';
			} else {
				$discountData->priceQty = $expiredDisc->getPriceQty();

				$priceInfo = ' Price Qty \'' . $discountData->priceQty . '\'';
			}

			$discountData->price = $expiredDisc->getPrice();


			//want to run as one transaction
			try {
				$retArr = self::processMagDiscount('D', $product, $discountData);
			
				if ( ( $retArr['updProd'] && $retArr['rc'] ) || ( !$retArr['updProd'] ) ) {
										
					generalLib::mesg('Deleting track discount for Tracking id \'' . $trkDiscId . '\' Store ID \'' . $discountData->storeId . '\' Website ID \'' . $discountData->websiteId .
						'\' Item No \'' . $discountData->productNo . '\'' . ' Cust No \'' . $discountData->customerNo . '\'' . $pricInfo . 
						' - Discount expired on \'' . $expiredDisc->getEndDate() . '\'');
					
					//TEMP - FOR TESTING - KEEP OLD DISCOUNTS AROUND
					/*
					*/
					$rc = self::deleteAttr($trkDiscId);

					if ($rc) {
				
			            if (method_exists(self::$procObj, self::$postExpiredCustomPriceFunc)) {
			                    $rc = self::$procObj->{ self::$postExpiredCustomPriceFunc }($discountData);
			            }

			            //echo "bef chkInActiveDisc\n";
			            //print_r($discountData);

			            $retArr = self::chkInActiveDiscs($product, $discountData);

					}

				}

			} catch(Exception $e){
				generalLib::error($e->getMessage());
		  	}		

			//die();
		}
	}
	

	//###########################
	public static function scanUnusedCustomerGroups($custGroupExceptions = array()) {
		//$custGroupExceptions = array('General', 'Wholesale' , 'Retailer', 'SALES_PERSON', 'NOT LOGGED IN', 'SHOWROOM');

		//SCAN CUSTOMER GROUPS WHICH NO LONGER HAVE ANY TIER PRICES - DELETE UNUSED ONES AND RE-ASSIGN THE CUSTOMER BACK TO THE 'GENERAL' CUSTOMER GROUP
		$custGroups = self::$procObj->objectManager->create('\Magento\Customer\Model\Group')->getCollection();
		
		foreach($custGroups as $custGroup) {
			$customerNo = $custGroup->getCustomerGroupCode();

			if (in_array($customerNo, $custGroupExceptions)) {
				continue;
			}
			
			 $collection = self::$procObj->objectManager->get('StarDigital\CustomPrices\Model\Customprices')
                        ->getCollection()
                        ->addFieldToFilter('customer_no', array('eq' => $customerNo));

			if ($collection->getSize() == 0) {
				 generalLib::mesg('Deleting customer group \'' . $customerNo . '\'');
				  
				try {
				  	$custGroup->delete();
					generalLib::mesg('Customer group deleted');
				} catch(Exception $e){
					generalLib::error($e->getMessage());
					generalLib::mesg('Cannot delete customer group');
				}
			}
		}
	}
	
	
	//###########################
	public static function scanInactiveDisc() {
		//SCAN FOR DISCOUNTS THAT HAVE NOT BEEN ACTIVATED YET AND HAVE A CURRENT START DATE
		
		$chkDate = date('Y-m-d');
		//TEMP
		//$chkDate = '2016-05-01';
		//$chkDate = '2016-05-30';
		//$chkDate = '2016-07-18';
		//$chkDate = '2016-07-26';
		
		$inactiveDiscColl = self::$procObj->objectManager->get('StarDigital\CustomPrices\Model\Customprices')
                        ->getCollection()
                        ->addFieldToFilter('active', array('eq' => 'N'))
						->addFieldToFilter('start_date', array('to' => $chkDate))
						//TEMP
                        //->addFieldToFilter('item_no' , array('eq' => 'A03300-lon'))
						;
		
		//$prodObj = new stdClass();

		foreach ($inactiveDiscColl as $inactiveDisc) {
			generalLib::mesg('');
			generalLib::mesg('====');
			
			$trkDiscId = $inactiveDisc->getId();
			
			generallib::mesg('Processing inactive discount id \'' . $trkDiscId . '\' :');
			
			$discountData = new stdClass();
			$discountData->productNo = $inactiveDisc->getProductNo();
			
			//$prodObj->sku = $discountData->productNo;
			print_r($inactiveDisc->getData());
			//continue;

			//print_r($prodObj);

			$product = false;

			$productId  = self::$procObj->objectManager->get('Magento\Catalog\Model\Product')->getIdBySku( $discountData->productNo );

			if ($productId) {
				$product = self::$procObj->objectManager->create('Magento\Catalog\Model\Product')->setStoreId( $inactiveDisc->getStoreId() )->load( $productId );

			}

			if (!$product) {
				generalLib::warn('Cannot find product for sku \'' . $discountData->productNo . '\' and store id \'' . $inactiveDisc->getStoreId() . '\' - skipping discount');
				continue;
			}
		
			//echo "in in active\n";
			//$prodTierPrices = $product->getData('tier_price');
			//print_r($prodTierPrices);
			
			$discountData->extId = $inactiveDisc->getExtId();
			$discountData->storeId = $inactiveDisc->getStoreId();
			$discountData->websiteId = $inactiveDisc->getWebsiteId();
			$discountData->productNo = $inactiveDisc->getProductNo();
			$discountData->customerNo = $inactiveDisc->getCustomerNo();
			$discountData->source = $inactiveDisc->getSource();
			$discountData->startDate = $inactiveDisc->start_date;
			$discountData->endDate = $inactiveDisc->end_date;

			//HAVE TO DO THIS, AS CANNOT USE empty() AND GET FIELD AT THE SAME TIME - PHP <5.5

			$priceAttr = $inactiveDisc->getPriceAttr();

			if ( !empty( $priceAttr ) ) {
				$discountData->priceAttr = $inactiveDisc->getPriceAttr();
			} else {
				$discountData->priceQty = $inactiveDisc->getPriceQty();
			}
			
			$discountData->price = $inactiveDisc->getPrice();
				
			$retArr = self::chkActiveDiscs($trkDiscId, $product, $discountData);
		}
	}


	//###########################
	public static function getExtDiscountDetails($extId) {
		$discRec = NULL;

		//echo 'ext id = ' . $extId . "\n";

		$collection = self::$procObj->objectManager->get('StarDigital\CustomPrices\Model\Customprices')
								->getCollection()
								->addFieldToFilter('ext_id', $extId);

        foreach ($collection as $discount) {
                $discRec = $discount;
        }

		return $discRec;
	}


	//###########################
    public static function getDiscountDetails($storeId, $websiteId, $productNo, $customerNo, $priceQty) {
            $priceRec = NULL;

            // If storeId is blank or NULL we return NULL.
            if ($storeId === '' || $storeId == NULL) {
                    return $priceRec;
            }

            // If item_no is blank or NULL we return NULL.
            if ($websiteId === '' || $websiteId == NULL) {
                    return $priceRec;
            }

            // If product_no is blank or NULL we return NULL.
            if ($productNo === '' || $productNo == NULL) {
                    return $priceRec;
            }

            // If customer_no is blank or NULL we return NULL.
            if ($customerNo === '' || $customerNo == NULL) {
                    return $priceRec;
            }

            // If price_qty is blank or NULL we return NULL.
            if ($priceQty === '' || $priceQty == NULL) {
                    return $priceRec;
            }

            // Let's search the table customerxmlattr for the email address.
            $collection = self::$procObj->objectManager->get('StarDigital\CustomPrices\Model\Customprices')
								->getCollection()
								->addFieldToFilter('store_id', array('eq' => $storeId))
								->addFieldToFilter('website_id', array('eq' => $websiteId))
								->addFieldToFilter('product_no', array('eq' => $productNo))
								->addFieldToFilter('customer_no', array('eq' => $customerNo))
								->addFieldToFilter('price_qty', array('eq' => $priceQty));

            foreach ($collection as $discount) {
                    $priceRec = $discount;
            }

            return $priceRec;
    }


    //###########################
    public static function deleteAttr($id) {
        $rc = true;

        $discount = self::$procObj->objectManager->get('StarDigital\CustomPrices\Model\Customprices')->load($id);

        if ($discount) {

			try {
				generalLib::mesg('Deleting custom price for ID \'' . $id . '\'');
				$discount->delete();
			}

			catch(Exception $e) {
				generalLib::error($e->getMessage());
				generalLib::error('custom price for ID \'' . $id . '\' not deleted');
				$rc = false;
			}

		} else {
			generalLib::error('Cannot delete custom price for ID \'' . $id . '\' - does not exist');
		}

        return $rc;
	}
	
}
?>
