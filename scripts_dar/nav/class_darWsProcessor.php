<?php

/*
SUGGESTIONS:
CONCATENATE THE PRODUCT DESCRIPTION INTO ONE LINE, PUTTING HTML LINE BREAKS BETWEEN EACH ONE '<br />'
*/

class darWsProcessor {
	public $dbObj;
	public $objectManager;
	public $procConfig;

	//###########################
	public function __construct($objectManager) {
		global $argv;

		$this->objectManager = $objectManager;

		$this->objectManager->get('Magento\Framework\Registry')->register('isSecureArea', true);

		$this->objectManager->get('Magento\Framework\App\State')->setAreaCode(\Magento\Framework\App\Area::AREA_ADMINHTML);

		$this->procConfig = new stdClass();


		$progConfigObj = new progConfig();

		$this->procConfig = $progConfigObj->progConfig;

		$this->procConfig->prodAction = $this->objectManager->create('\Magento\Catalog\Model\ResourceModel\Product\Action');

		$this->procConfig->action = isset($argv[2]) ? $argv[2] : 'ALL';

		$this->procConfig->feedType = isset($argv[3]) ? $argv[3] : 'ALL';		//POSSIBLE VALUES: PRODUCT, CUSTOMER, CATEGORY, ORDER, ALL

		magentoAttrImport::attrConfig($this);

		magentoProd::prodConfig($this);

		magentoImport::importConfig($this);

		magentoGroupProducts::grpProdConfig($this);

		magentoCustomPrices::priceConfig($this);

		magentoCust::CustConfig($this);

		magentoCustomOpts::customOptsConfig( $this );

		magentoGeneral::genConfig( $this );

		magentoCache::cacheConfig( $this );
		//print_r(PDO::getAvailableDrivers());
		//die();

		generalLib::setLineBrk("\n");
		//generalLib::setLineBrk("<br />");

		if (!is_dir($this->procConfig->localLogDir)) {
			if (!mkdir($this->procConfig->localLogDir)) {
				generalLib::term(1, 'Cannot create directory ' . $this->procConfig->localLogDir);
			}
		}


		$logFilename = $this->procConfig->localLogDir . '/dar_' . $this->procConfig->feedType . '-' . date('Ymd_His', magentoGeneral::getLocalTimeStamp());

		generalLib::setGeneralLogFile($logFilename . '-general.log', false);
		generalLib::setErrorLogFile($logFilename . '-error.log', true, false);
		generalLib::setWarnLogFile($logFilename  . '-warn.log', true, false);

		//generalLib::setErrorLogEmailDetails($this->procConfig->errorMailSubject, $this->procConfig->errorMailFrom, $this->procConfig->errorMailTo);
		//generalLib::setWarnLogEmailDetails($this->procConfig->warnMailSubject, $this->procConfig->warnMailFrom, $this->procConfig->warnMailTo);

		$this->procConfig->extCatId = 'id';
		$this->procConfig->extParentCatId = 'parent';


		//CATEGORY MAGENTO FIELDS
		$this->procConfig->catMagAttrs = array('description' => 'description', 'active' => 'is_active');


		//CATEGORY CUSTOM FIELDS
		$this->procConfig->catCustomAttrs = array('id' => 'T', 'colour' => 'T', 'parent' => 'T');


		//CATEGORY MAGENTO NAME FIELD
		$this->procConfig->catMagNameAttr = array('category' => 'name');

		//CATEGORY CUSTOM FIELD MAPPINGS
		$this->procConfig->catCustomAttrMappings = array('id' => 'dar_cat_id', 'colour' => 'dar_colour_code', 'parent' => 'dar_parent_cat_id');

		magentoCat::setMagCatIdFld($this);

		if ( ( $rootCatPath = magentoCat::setCatPath('root', $this->procConfig->rootCatID) ) != false) {
			$this->procConfig->rootStoreCatId = $this->procConfig->rootCatID;
			$this->procConfig->rootStoreCatPath = $rootCatPath;
		} else {
			generalLib::term(1,'');
		}

		$feedWebServices = array();
		$feedWebServices['ATTRIBUTES'] = array('Attribute_Set_List', 'Attribute_Group_List', 'Attribute_List', 'Product_Attribute');
		//$feedWebServices['CATEGORIES'] = array('Cat_Categories');
		$feedWebServices['CUSTOMERS'] = array('Users', 'W_Addresses');
		$feedWebServices['LEAFLETS'] = array('IL_Document');
		$feedWebServices['LOCAL_STOCKISTS'] = array('Local_Stockists');
		$feedWebServices['ORDERS'] = array('MagentoOrderHeader');

		//DO NOT PROCESS PRODUCT CATEGORIES SO AS NOT TO OVVERIDE NICK'S SPREADSHEET CATEGORIES
		$feedWebServices['PRODUCTS'] = array('Stock', 'Price_List', /* 'Product_Category', */ 'Part_Related', 'Shade_Ranges', 'Shade_Liner_Prices', 'Store_Data');

		$feedWebServices['BESPOKE_SHADES'] = array('Shade_Silk_Colours', 'Shade_Silk_Colour_Prices', 'Shade_Styles', 'Shade_Braid', 'Shade_Braid_Prices', 'Shade_Fringe', 'Shade_Fringe_Prices',
									'Shade_Accessory', 'DHL_Cable', 'DHL_Cable_Prices', 'DHL_Glass', 'DHL_Glass_Prices', 'DHL_Inner_Paint', 'DHL_Inner_Paint_Prices',
									'DHL_Outer_Paint', 'DHL_Outer_Paint_Prices'
									);
		//$feedWebServices['PRODUCTS'] = array('pricelist', 'xrrp', 'productcategory', 'part_related', 'shadesilkcolours', 'storedata');

		//$feedWebServices['SHADES'] = array('shadesilkcolours', 'shaderanges');
		$feedWebServices['XWEBLAMPS'] = array('X_Web_Lamp_Category', 'X_Web_Lamp');

		//THESE ARE USED FOR BESPOKE IMPORTS - NOT REGULAR CRONTAB IMPORTS
		$feedWebServices['ADDRESSES'] = array('W_Addresses');
		$feedWebServices['ATTRIBUTE_SET'] = array('Attribute_Set_List');
		$feedWebServices['ATTRIBUTE_GROUP'] = array('Attribute_Group_List');
		$feedWebServices['ATTRIBUTE'] = array('Attribute_List');
		$feedWebServices['CABLE'] = array('DHL_Cable');
		$feedWebServices['CABLE_PRICES'] = array('DHL_Cable_Prices');
		$feedWebServices['PART_RELATED'] = array('Part_Related');
		$feedWebServices['GLASS'] = array('DHL_Glass');
		$feedWebServices['GLASS_PRICES'] = array('DHL_Glass_Prices');
		$feedWebServices['INNER_PAINT'] = array('DHL_Inner_Paint');
		$feedWebServices['INNER_PAINT_PRICES'] = array('DHL_Inner_Paint_Prices');
		$feedWebServices['OUTER_PAINT'] = array('DHL_Outer_Paint');
		$feedWebServices['OUTER_PAINT_PRICES'] = array('DHL_Outer_Paint_Prices');
		$feedWebServices['PRICE_LIST'] = array('Price_List');
		$feedWebServices['PRODUCT_ATTRIBUTE'] = array('Product_Attribute');
		$feedWebServices['PRODUCT_CATEGORY'] = array('Product_Category');
		$feedWebServices['SHADE_RANGE'] = array('Shade_Ranges');
		$feedWebServices['ACCESSORIES'] = array('Shade_Accessory');

		$feedWebServices['BRAID'] = array('Shade_Braid');
		$feedWebServices['BRAID_PRICES'] = array('Shade_Braid_Prices');

		$feedWebServices['FRINGE'] = array('Shade_Fringe');
		$feedWebServices['FRINGE_PRICES'] = array('Shade_Fringe_Prices');

		$feedWebServices['SHADE_LINER_PRICES'] = array('Shade_Liner_Prices');
		$feedWebServices['SILK_COLOURS'] = array('Shade_Silk_Colours');
		$feedWebServices['SILK_COLOUR_PRICES'] = array('Shade_Silk_Colour_Prices');
		$feedWebServices['STYLES'] = array('Shade_Styles');
		$feedWebServices['STOCK'] = array('Stock');
		$feedWebServices['STOCK_LEVEL'] = array('Stock_Level');
		$feedWebServices['STORE_DATA'] = array('Store_Data');
		$feedWebServices['USERS'] = array('Users');
		$feedWebServices['XWEBLAMP'] = array('X_Web_Lamp');
		$feedWebServices['XWEBLAMP_CATEGORY'] = array('X_Web_Lamp_Category');

		$this->procConfig->feedWebServices = $feedWebServices;

		//CUSTOMER MAGENTO FIELDS
		$this->procConfig->custMagAttrs = array('accTitle' => 'prefix', 'firstname' => 'firstname', 'lastname' => 'lastname', 'email' => 'email');


		//CUSTOMER CUSTOM ATTRIBUTES
		$this->procConfig->custCustomAttrs = array(/* 'cust' => 'T', */ 'userid' => 'T', 'cdisc' => 'T', 'conhold' => 'B',
												'features' => 'T', 'is_active' => 'B', 'downloadimages' => 'B', 'contract' => 'B', 'validateduser' => 'B',
												'weborder' => 'B', 'downloadfiles' => 'B', 'priority' => 'T', 'b2c' => 'B',
												'autohold' => 'B', 'dhl_permission' => 'B', 'customer_friendly' => 'B', 'proforma' => 'B', 'bespoke' => 'B',
												'adcurrency' => 'T', 'capltype' => 'T', 'maincontact' => 'T', 'cdefdeladd' => 'T'
 											);

		//CUSTOMER ADDRESS ATTRIBUTES
		$this->procConfig->custAddrAttrs = array('addritem' => 'custom_addr_id', 'custname' => 'company', 'firstname' => 'firstname', 'lastname' => 'lastname',
														'address1' => 'street_1', 'address2' => 'street_2', 'address3' => 'city',
														'address4' => 'region', 'postcode' => 'postcode', 'adcountry' => 'country_id',
														'adtelephone' => 'telephone', 'advatcat' => 'advatcat',
														);


		//LEAFLET CUSTOM FIELDS
		$this->procConfig->leafletMagAttrs = array('file_non_eng' => 'file_non_eng', 'file_eng' => 'file_eng');


		//PRODUCT MAGENTO FIELDS
		$this->procConfig->stockMagSkuAttr = array('partnum' => 'sku');


		//TEMP
		/*
		$this->procConfig->stockMagDescAttr = array('partdesc' => 'description');
		$this->procConfig->stockMagShortDescAttr = array('partdesc' => 'short_description');
		*/


		//PRODUCT MAGENTO PRICE FIELD
		$this->procConfig->stockPriceMagAttr = array('price' => 'price');


		//PRODUCT MAGENTO PRODUCT NAME FIELD
		$this->procConfig->stockMagNameAttr = array('partdesc' => 'name');


		//PRODUCT MAGENTO PRODUCT META TITLE FIELD
		$this->procConfig->stockMagMetaTitleAttr = array('metatitle' => 'meta_title');


		//PRODUCT CUSTOM FIELDS
		$this->procConfig->stockCustomAttrs = array('custref' => 'T', 'xpallet' => 'B', 'restricted' => 'B', 'leaflet_only' => 'B',
													'shade_range_basecode' => 'T', 'item_status' => 'T', 'blocked_for_discount' => 'B',
													'buying_restriction_code' => 'T', 'product_brand' => 'T');


		//PRODUCT VAT CUSTOM FIELD
		$this->procConfig->stockCustomVatAttr = array('vatcode' => 'T');


		//PRODUCT SHADE RANGE PRODUCT CUSTOM FIELD
		$this->procConfig->stockCustomShadeRangeAttr = array('shade_range_product' => 'B');


		//PRODUCT XRRP MAGENTO FIELDS

		$this->procConfig->stockMagPackSizeAttr = array('packsize' => 'packsize');

		$this->procConfig->stockXrrpMagAttrs = array('srp' => 'srp', 'rrp' => 'rrp');

		$this->procConfig->stockLevelCustomAttrs = array('material' => 'T');

		$this->procConfig->stockLevelMagAttrs = array('stavailability' => 'material');

		//PRODUCT XPROMO CUSTOM FIELDS
		$this->procConfig->xpromoMagAttrs = array('plpromotioncode' => 'xpromo_code', 'plpromotiondescription' => 'xpromo_desc', 'plbrkqty' => 'xpromo_qty',
													'xpromotype' => 'xpromo_type', 'plcode' => 'xpromo_cust', 'xpromofreeqty' => 'xpromo_free_qty');

		$this->procConfig->xpromoCustomAttrs = array('plfromdate' => 'D', 'pltodate' => 'D');

		$this->procConfig->xpromoCustomAttrMappings = array('plfromdate' => 'xpromo_from', 'pltodate' => 'xpromo_to');

		//PRODUCT XPROMOPART CUSTOM FIELDS
		$this->procConfig->xpromoPartMagAttrs = array('plpromotioncode' => 'xpromo_code', 'plpart' => 'xpromo_part', 'plunitprice' => 'xpromo_unit_price');



		//PRODUCT XWEBLAMPCATEGORY MAGENTO FIELDS
		$this->procConfig->xWebLampCategoryMagAttrs = array('categoryid' => 'general_cat_id');


		//PRODUCT XWEBLAMP MAGENTO FIELDS
		$this->procConfig->xWebLampMagAttrs = array('nprice' => 'price');

		//PRODUCT XWEBLAMP CUSTOM FIELDS
		$this->procConfig->xWebLampCustomAttrs = array('lightoutput' => 'T', 'dimensions' => 'T', 'colourtemp' => 'T',
														'dimmable' => 'B', 'packsize' => 'T', 'web_lamp_product' => 'B');


		$this->procConfig->xWebLampGroupIdMagAttr = array('lampgroupid' => 'lamp_group_id');


		//SHADE RANGES PRODUCT MAGENTO ATTRIBUTES
		$this->procConfig->shaderangesMagAttrs = array('shade_partnum' => 'sku', 'sort_order' => 'shade_sort_order', 'product_desc' => 'shade_description');


		//SHADE RANGES PRODUCT CUSTOM ATTRIBUTES
		$this->procConfig->shaderangesCustomAttrs = array('glass_baffle' => 'B', 'shade_carrier' => 'B', 'required_baffle' => 'T');


		//SHADE RANGE PRODUCT MAGENTO ATTRIBUTE
		$this->procConfig->shaderangeTypeMagAttr = array('range_type' => 'shade_range_type');

		//SHADE STYLE PRODUCT MAGENTO ATTRIBUTES
		$this->procConfig->shaderangeStyleMagAttr = array('shade_styles' => 'shade_styles');


		//SHADE RANGE TYPES
		$this->procConfig->shaderangeTypeNames = array('HARD' => 'Silk Laminated Shades', 'DHL' => 'David Hunt Lighting', 'SOFT' => 'Soft Silk Shades', 'DHLPF' => 'David Hunt Lighting Painted Finishes');


		//SHADE RANGE TYPE MAGENTO ATTRIBUTES
		$this->procConfig->shaderangesTypeMagAttrs = array('range_type' => 'range_type_code', 'range_type_label' => 'range_type_label', 'active' => 'enabled');


		//SHADE RANGE LINK MAGENTO ATTRIBUTES
		$this->procConfig->shaderangesLinkMagAttrs = array('shade_range_label' => 'label', 'range_type_id' => 'range_type');


		//SHADE RANGES PRODUCT CUSTOM TIER ATTRIBUTES
		$this->procConfig->shaderangesTierCustomAttrs = array('shade_twotone' => 'B', 'shade_threetier' => 'B');



		//STOCKIST CUSTOM ATTRIBUTES
		$this->procConfig->stockistMagAttrs = array('stockist_id' => 'stockist_id', 'name' => 'name', 'address1' => 'address1', 'address2' => 'address2', 'address3' => 'address3', 'county' => 'county', 'postcode' => 'postcode', 'telephone' => 'telephone',
													'longitude' => 'longitude', 'latitude' => 'latitude');


		//NOTE: DROPDOWN VALUES ARE SET BY MAGENTO ON CREATING THE OPTIONS DROPDOWN ATTRIBUTE, AND MAY NOT START AT 1, THEREFORE YOU MUST USE THE LABEL IN ORDER TO SET THE VALUE,
		//      AS THE VALUE CANNOT BE TRUSTED BETWEEN MAGENTO INSTALLATIONS
		$this->procConfig->shadeRangeOpts = magentoImport::setAttrOptions('catalog_product', 'shade_range_name');

		$this->procConfig->shadeBaffleOpts = magentoImport::setAttrOptions('catalog_product', 'shade_baffle');

		$this->procConfig->lampholderOpts = magentoImport::setAttrOptions('catalog_product', 'lampholder');

		$this->procConfig->glassOpts = magentoImport::setAttrOptions('catalog_product', 'glass');

		$this->procConfig->lamplifeOpts = magentoImport::setAttrOptions('catalog_product', 'lamplife');


		$this->procConfig->allowedSellingBrandOpts = magentoImport::setAttrOptions('customer', 'allowed_selling_brand');


		$this->procConfig->actionTypeUpdProcessed = '-';

		$this->procConfig->actionTypeDelProcessed = 'd';

		//echo Mage::getModel('eav/entity_attribute')->getBackendTypeByInput('text');
		//die();
		//magentoAttrImport::findAttr('john_attr');
		//magentoAttrImport::findAttrSet('Default','Prices');
		//die();

		//magentoAttrImport::processProdAttr('I', 'John Attr', 'john_attr', $values, $productTypes, $setInfo = -1);
		//magentoAttrImport::findAttr('john_attr');

		//die();

		$this->procConfig->currDateTStamp = strtotime( date('Y-m-d', time()));

		$this->procConfig->storeDets = magentoImport::getStoreDets();


		$this->procConfig->getStoreCodeDets = magentoImport::getStoreCodeDets();

		$this->procConfig->b2bStoreId = $this->procConfig->getStoreCodeDets[ 'b2b_store' ]->storeId;

		//print_r($this->procConfig->storeDets);

		//$this->procConfig->darToMagStoreMappings = array('1' => '2', '2' => '1', '3' => '3', '4' => '4', '5' => '5', '6' => '6', '7' => '7', '8' => '8', '9' => '9', '10' => '10', '11' => '11');

		$this->procConfig->numStockLevelsUpd = 0;



		//TEMP - TO BE RESTORED
		//magentoIndex::setMagIdxParams($this->procConfig->configType, $this->procConfig->maxIdxRetries, $this->procConfig->retryIdxDelay, $this->procConfig->lockDir);

		$this->procConfig->shadePartNumsToChk = array();

		$this->procConfig->checkRangeAndStyleTables = false;

		$this->procConfig->stockistModel = 'Fyb\StockistLocator\Model\Localstockists';

		$this->procConfig->xpromoModel = 'StarDigital\DarDiscounts\Model\Xpromo';

		$this->procConfig->xpromoPartModel = 'StarDigital\DarDiscounts\Model\Xpromopart';

		$productTaxClasses = $this->objectManager->create('Magento\Tax\Model\TaxClass\Source\Product');
		$this->procConfig->productTaxClasses = $productTaxClasses->getAllOptions(false);

		//NOTE \\ SO STRING CAN CONTAIN A BACK SLASH
		$this->procConfig->bespConfBaseModel = 'StarDigital\BespokeConfigurator\Model\\';

		// we unregister the current HTTP wrapper
		stream_wrapper_unregister('http');

		// we register the new HTTP wrapper
		stream_wrapper_register('http', 'projectServiceProviderNTLMStream') or die("Failed to register protocol");

		$this->procConfig->wsConn = new wsFuncs( $this );
		$this->procConfig->wsConn->setActionTypeProcessed('-', '+');

		$this->procConfig->wsAddlConn = new wsFuncs( $this );
		$this->procConfig->wsAddlConn->setActionTypeProcessed('-', '+');

		$this->procConfig->readProdCatWebService = 'Product_Category';

		$this->procConfig->readProdCatUrl = $this->procConfig->baseWsUrl . '/' . $this->procConfig->readProdCatWebService;


		$this->procConfig->readProdAttrWebService = 'Product_Attribute';

		$this->procConfig->readProdAttrUrl = $this->procConfig->baseWsUrl . '/' . $this->procConfig->readProdAttrWebService;


		$this->procConfig->braid_list = array();

		$this->procConfig->googleApiKey = $this->objectManager->get('\Magento\Framework\App\Config\ScopeConfigInterface')->getValue('stockistlocator/locatorconfiguration/access_token');

		$this->procConfig->mediaFolder = $this->objectManager->get('Magento\Framework\App\Filesystem\DirectoryList')->getPath('media');

		$this->procConfig->mediaFolderImgImport = $this->procConfig->mediaFolder . '/import/images';

		ini_set('allow_url_fopen', 1);
	}


	public function processAction() {
		global $argv;

		$gotAction = false;

		if ($this->procConfig->action == 'IMPORT' || $this->procConfig->action == 'ALL') {
			$this->processWsFeed();
			$gotAction = true;
		}

		if ($this->procConfig->action == 'SCAN' || $this->procConfig->action == 'ALL') {
			$this->processScanDbFeed();
			$gotAction = true;
		}

		if ($this->procConfig->action == 'EXPORT' || $this->procConfig->action == 'ALL') {
			$this->exportDbFeed();
			$gotAction = true;
		}

		if (!$gotAction) {
			generalLib::error('No action(s) specified');
		}

	}

	//###########################
	public function processWsFeed() {
		//print_r($this->procConfig->dbConfig);

		//CALLs TO AN EXTERNAL CLASS
		$actionTypeName = 'actiontype';
		$action = 'ReadMultiple';
		//$baseWsUrlVar = 'baseWsUrl' . '_' . $this->procConfig->storeId;

		if ( !magentoImport::gotMagFeedLock($this->procConfig->feedType, $this->procConfig->lockDir) ) {
			return;
		}

		$batchSize = 100;	//DEFAULT - CAN BE OVERWRITTEN FOR EACH WEB SERVICE (IF DESIRED)

		/*
		//TEMP - TO BE RESTORED
		$magFeedIdxLock = magentoIndex::getMagFeedIdxLock();

		if (!$magFeedIdxLock['gotLock']) {
			return;
		}
		*/

		$this->getReferenceValues( 'Shade_Silk_Colours', '*' );

		if ( count($this->procConfig->silkcolour_list) == 0 ) {
			generalLib::Error('Cannot define silk colour list');
			return;
		}


		$this->getReferenceValues( 'Shade_Braid', '*' );

		if ( count($this->procConfig->braid_list) == 0 ) {
			generalLib::Error('Cannot define shade braid list');
			return;
		}


		$this->getReferenceValues( 'Shade_Fringe', '*' );

		if ( count($this->procConfig->fringe_list) == 0 ) {
			generalLib::Error('Cannot define shade fringe list');
			return;
		}


		$this->getReferenceValues( 'Shade_Liner', '*' );

		if ( count($this->procConfig->liner_list) == 0 ) {
			generalLib::Error('Cannot define shade liners');
			return;
		}

		//print_r($this->procConfig->liner_list);
		//die();

		$this->getReferenceValues( 'DHL_Cable', '*' );

		if ( count($this->procConfig->cable_list) == 0 ) {
			generalLib::Error('Cannot define DHL Cables');
			return;
		}


		$this->getReferenceValues( 'DHL_Glass', '*' );

		if ( count($this->procConfig->glass_list) == 0 ) {
			generalLib::Error('Cannot define DHL Glass list');
			return;
		}


		$this->getReferenceValues( 'DHL_Inner_Paint', '*' );

		if ( count($this->procConfig->innerpaint_list) == 0 ) {
			generalLib::Error('Cannot define DHL Inner Paint list');
			return;
		}


		$this->getReferenceValues( 'DHL_Outer_Paint', '*' );

		if ( count($this->procConfig->outerpaint_list) == 0 ) {
			generalLib::Error('Cannot define DHL Outer Paint list');
			return;
		}

		//TEMP
		//$this->chkShadeStyleAndRangeTypes();

		//print_r($this->procConfig->xweblampcat_list);
		//die();

		//WITHOUT THIS STATEMENT A 'Heterogeneous queries require the ANSI_NULLS and ANSI_WARNINGS options to be set for the connection. This ensures consistent query semantics. Enable these options and then reissue your query.' ERROR IS RETURNED.
		// In short, it means that if you're running a query against a Linked Server that isn't a SQL Server box, then more than likely you'll need to turn these two features on to standardize the way certain comparison operators are handled across different databases.
		//$dbObj->absDbQuery('SET ANSI_NULLS ON; SET ANSI_WARNINGS ON');

		if (!isset($this->procConfig->feedWebServices[ $this->procConfig->feedType ])) {
			generalLib::error('Feed type \'' . $this->procConfig->feedType . '\' not found');
		}

		foreach($this->procConfig->feedWebServices[ $this->procConfig->feedType ] as $funcFeedWebService) {
			$processFunc = 'process' . $funcFeedWebService . 'WebService';

			echo 'processFunc = ' . $processFunc . "\n";

			$url = $this->procConfig->baseWsUrl . '/' . $funcFeedWebService;

			echo 'url = ' . $url . "\n";

			if (method_exists($this, $processFunc)) {
				generalLib::mesg('Processing ' . $funcFeedWebService);
				$actionTypeName = 'actiontype';		//DEFAULT


				switch ($funcFeedWebService) {

					//NEEDS BOTH ATTRIBUTE SET ANF GROUP DETAILS
					case 'Attribute_List':

										$this->getReferenceValues( 'Attribute_Group_List', '-', 'grp_action_type' );

										if ( count($this->procConfig->attrgrp_list) == 0 ) {
											generalLib::Error('Cannot define Attribute Group list');
											return;
										}

					case 'Attribute_Group_List':

										$this->getReferenceValues( 'Attribute_Set_List', '-', 'set_action_type' );

										if ( count($this->procConfig->attrset_list) == 0 ) {
											generalLib::Error('Cannot define Attribute Set List');
											return;
										}

										//print_r($this->procConfig->attrset_list);
										//die();
										break;

					case 'Product_Attribute':
										$this->getReferenceValues( 'Attribute_List', '-', 'attr_action_type' );

										if ( count($this->procConfig->attr_list) == 0 ) {
											generalLib::Error('Cannot define Attribute list');
											return;
										}

										break;

					case 'X_Web_Lamp':
										$this->getReferenceValues( 'X_Web_Lamp_Category', '*' );

										if ( count($this->procConfig->xweblampcat_list) == 0 ) {
											generalLib::Error('Cannot define X Web Lamp Category list');
											return;
										}

										break;
				}


				switch($funcFeedWebService) {

					case 'Attribute_List':

								$actionTypeName = 'attr_action_type';

								$parameters = 	array('filter' => array(
													array( 'Field' => $actionTypeName, 'Criteria' => 'U|D' ),
													//array( 'Field' => $actionTypeName, 'Criteria' => '*' ),
													//array( 'Field' => 'attr_Id', 'Criteria' => '7' ),
													//array( 'Field' => $actionTypeName, 'Criteria' => '-' ),
													)
												);

								break;

					case 'Attribute_Group_List':

								$actionTypeName = 'grp_action_type';

								$parameters = 	array('filter' => array(
													array( 'Field' => $actionTypeName, 'Criteria' => 'U|D' ),
													//array( 'Field' => $actionTypeName, 'Criteria' => '*' ),
													//array( 'Field' => $actionTypeName, 'Criteria' => '-' ),
													)
												);

								break;

					case 'Attribute_Set_List':
								$actionTypeName = 'set_action_type';

								$parameters = 	array('filter' => array(
													array( 'Field' => $actionTypeName, 'Criteria' => 'U|D' ),
													//array( 'Field' => $actionTypeName, 'Criteria' => '*' ),
													//array( 'Field' => $actionTypeName, 'Criteria' => '-' ),
													)
												);

								break;

					case 'Cat_Categories':

								$parameters = 	array('filter' => array(
													array( 'Field' => $actionTypeName, 'Criteria' => 'U|D' ),
													//array( 'Field' => $actionTypeName, 'Criteria' => '-' ),
													)
												);

								break;

					case 'DHL_Cable':

								$parameters = 	array('filter' => array(
													array( 'Field' => $actionTypeName, 'Criteria' => 'U|D' ),
													//array( 'Field' => $actionTypeName, 'Criteria' => '-' ),
													//array( 'Field' => $actionTypeName, 'Criteria' => '*' ),
													)
												);

								break;

					case 'DHL_Cable_Prices':

								$parameters = 	array('filter' => array(
													array( 'Field' => $actionTypeName, 'Criteria' => 'U|D' ),
													//array( 'Field' => $actionTypeName, 'Criteria' => '-' ),
													//array( 'Field' => $actionTypeName, 'Criteria' => '*' ),
													)
												);

								break;

					case 'DHL_Glass':

								$parameters = 	array('filter' => array(
													array( 'Field' => $actionTypeName, 'Criteria' => 'U|D' ),
													//array( 'Field' => $actionTypeName, 'Criteria' => '-' ),
													//array( 'Field' => $actionTypeName, 'Criteria' => '*' ),
													)
												);

								break;

					case 'DHL_Glass_Prices':
								$parameters = 	array('filter' => array(
													array( 'Field' => $actionTypeName, 'Criteria' => 'U|D' ),
													//array( 'Field' => $actionTypeName, 'Criteria' => '-' ),
													//array( 'Field' => $actionTypeName, 'Criteria' => '*' ),
													)
												);

								break;

					case 'DHL_Inner_Paint':

								$parameters = 	array('filter' => array(
													array( 'Field' => $actionTypeName, 'Criteria' => 'U|D' ),
													//array( 'Field' => $actionTypeName, 'Criteria' => '-' ),
													//array( 'Field' => $actionTypeName, 'Criteria' => '*' ),
													)
												);

								break;

					case 'DHL_Inner_Paint_Prices':

								$parameters = 	array('filter' => array(
													array( 'Field' => $actionTypeName, 'Criteria' => 'U|D' ),
													//array( 'Field' => $actionTypeName, 'Criteria' => '-' ),
													//array( 'Field' => $actionTypeName, 'Criteria' => '*' ),
													)
												);

								break;

					case 'DHL_Outer_Paint':

								$parameters = 	array('filter' => array(
													array( 'Field' => $actionTypeName, 'Criteria' => 'U|D' ),
													//array( 'Field' => $actionTypeName, 'Criteria' => '-' ),
													//array( 'Field' => $actionTypeName, 'Criteria' => '*' ),
													)
												);

								break;

					case 'DHL_Outer_Paint_Prices':

								$parameters = 	array('filter' => array(
													array( 'Field' => $actionTypeName, 'Criteria' => 'U|D' ),
													//array( 'Field' => $actionTypeName, 'Criteria' => '-' ),
													//array( 'Field' => $actionTypeName, 'Criteria' => '*' ),
													)
												);

								break;

					case 'IL_Document':

								$parameters = 	array('filter' => array(
													array( 'Field' => $actionTypeName, 'Criteria' => 'U|D' ),
													//array( 'Field' => $actionTypeName, 'Criteria' => '-' ),
													//array( 'Field' => $actionTypeName, 'Criteria' => '*' ),
													)
												);

								break;

					case 'Local_Stockists':

								$parameters = 	array('filter' => array(
													array( 'Field' => $actionTypeName, 'Criteria' => 'U|D' ),
													//array( 'Field' => $actionTypeName, 'Criteria' => '-' ),
													//array( 'Field' => 'stockist_id', 'Criteria' => '856' ),
													)
												);

								break;

					case 'MagentoOrderHeader':

								$parameters = 	array('filter' => array(
													array( 'Field' => $actionTypeName, 'Criteria' => 'U' ),
													array( 'Field' => 'status', 'Criteria' => 'LIVE' ),
													)
												);

								break;

					case 'Part_Related':

								$parameters = 	array('filter' => array(
													array( 'Field' => $actionTypeName, 'Criteria' => 'U|D' ),
													//array( 'Field' => 'autoid', 'Criteria' => '4998' ),
													)
												);

								break;

					case 'Price_List':

								$parameters = 	array('filter' => array(
													array( 'Field' => $actionTypeName, 'Criteria' => 'U|D' ),
													//array( 'Field' => 'plpart', 'Criteria' => '001ANZ001' ),
													//array( 'Field' => 'pltype', 'Criteria' => 'customer' ),
													//array( 'Field' => 'plpart', 'Criteria' => 'ABA4750' ),
													)
												);

								break;

					case 'Product_Attribute':

								$actionTypeName = 'pattr_action_type';

								$parameters = 	array('filter' => array(
													array( 'Field' => $actionTypeName, 'Criteria' => 'U|D' ),
													//array( 'Field' => 'pattr_product_id', 'Criteria' => '001AGO001' )
													//array( 'Field' => 'pattr_product_id', 'Criteria' => 'ABA4750' )
													//array( 'Field' => 'pattr_product_id', 'Criteria' => 'STE03' )
													)
												);


								break;

					case 'Product_Category':

								$parameters = 	array('filter' => array(
													array( 'Field' => $actionTypeName, 'Criteria' => 'U|D' ),
													//array( 'Field' => 'productid', 'Criteria' => '001ANZ001|001ANZ002' ),
											)
										);

								break;

					case 'Shade_Ranges':

								$parameters = 	array('filter' => array(
													array( 'Field' => $actionTypeName, 'Criteria' => 'U|D' ),
													//array( 'Field' => $actionTypeName, 'Criteria' => '-' ),
													//array( 'Field' => $actionTypeName, 'Criteria' => '*' ),
													//array( 'Field' => 'shade_partnum', 'Criteria' => 'DRM3099' ),
													)
												);

								break;

					case 'Shade_Accessory':

								$parameters = 	array('filter' => array(
													array( 'Field' => $actionTypeName, 'Criteria' => 'U|D' ),
													//array( 'Field' => $actionTypeName, 'Criteria' => '-' ),
													//array( 'Field' => $actionTypeName, 'Criteria' => '*' ),
													)
												);

								break;


					case 'Shade_Braid':

								$parameters = 	array('filter' => array(
													array( 'Field' => $actionTypeName, 'Criteria' => 'U|D' ),
													//array( 'Field' => $actionTypeName, 'Criteria' => '-' ),
													//array( 'Field' => $actionTypeName, 'Criteria' => '*' ),
													)
												);

								break;

					case 'Shade_Braid_Prices':

								$parameters = 	array('filter' => array(
													array( 'Field' => $actionTypeName, 'Criteria' => 'U|D' ),
													//array( 'Field' => $actionTypeName, 'Criteria' => '-' ),
													//array( 'Field' => $actionTypeName, 'Criteria' => '*' ),
													)
												);

								break;

					case 'Shade_Fringe':

								$parameters = 	array('filter' => array(
													array( 'Field' => $actionTypeName, 'Criteria' => 'U|D' ),
													//array( 'Field' => $actionTypeName, 'Criteria' => '-' ),
													//array( 'Field' => $actionTypeName, 'Criteria' => '*' ),
													)
												);

								break;

					case 'Shade_Fringe_Prices':

								$parameters = 	array('filter' => array(
													array( 'Field' => $actionTypeName, 'Criteria' => 'U|D' ),
													//array( 'Field' => $actionTypeName, 'Criteria' => '-' ),
													//array( 'Field' => $actionTypeName, 'Criteria' => '*' ),
													)
												);

								break;

					case 'Shade_Liner_Prices':

								$parameters = 	array('filter' => array(
													array( 'Field' => $actionTypeName, 'Criteria' => 'U|D' ),
													//array( 'Field' => $actionTypeName, 'Criteria' => '-' ),
													//array( 'Field' => $actionTypeName, 'Criteria' => '*' ),
													//array( 'Field' => 'shade_partnum', 'Criteria' => 'TAP40' ),
													)
												);

								break;

					case 'Shade_Silk_Colours':

								$parameters = 	array('filter' => array(
													array( 'Field' => $actionTypeName, 'Criteria' => 'U|D' ),
													//array( 'Field' => $actionTypeName, 'Criteria' => '-' ),
													//array( 'Field' => $actionTypeName, 'Criteria' => '*' ),
													)
												);

								break;


					case 'Shade_Silk_Colour_Prices':

								$parameters = 	array('filter' => array(
													array( 'Field' => $actionTypeName, 'Criteria' => 'U|D' ),
													//array( 'Field' => $actionTypeName, 'Criteria' => '-' ),
													//array( 'Field' => $actionTypeName, 'Criteria' => '*' ),
													//array( 'Field' => 'shade_partnum', 'Criteria' => 'ABY' ),
													)
												);

								break;

					case 'Shade_Styles':

								$parameters = 	array('filter' => array(
													array( 'Field' => $actionTypeName, 'Criteria' => 'U|D' ),
													//array( 'Field' => $actionTypeName, 'Criteria' => '-' ),
													//array( 'Field' => $actionTypeName, 'Criteria' => '*' ),
													)
												);

								//$query = 'SELECT * FROM shadestyles WHERE actiontype in ("U", "D") ORDER BY style_code';
								break;


					case 'Stock':

								$parameters = 	array('filter' => array(
													array( 'Field' => $actionTypeName, 'Criteria' => 'U|D' ),
													//array( 'Field' => $actionTypeName, 'Criteria' => '-' ),
													//array( 'Field' => $actionTypeName, 'Criteria' => '-|U|D' ),
													//array( 'Field' => 'partnum', 'Criteria' => 'ADV4250-P' ),
													)
												);

								//NOTE: ONLY LOOKING FOR ACTION TYPE 'U', AS DELETIONS WILL BE HANDLED BY THE STORE DATA TABLE
								//$query = 'SELECT * FROM stock WHERE actiontype IN ("U", "D")';
								break;


					case 'Stock_Level':


								$parameters = 	array('filter' => array(
													array( 'Field' => $actionTypeName, 'Criteria' => 'U' ),
													//array( 'Field' => 'partnum', 'Criteria' => '001ANZ001' ),
													)
												);

								//NOTE: ONLY LOOKING FOR ACTION TYPE 'U', AS DELETIONS WILL BE HANDLED BY THE STORE DATA TABLE
								break;


					case 'Stocklevelcheck':

								$parameters = 	array('filter' => array(
													array( 'Field' => $actionTypeName, 'Criteria' => 'U' ),
													)
												);

								break;

					case 'Store_Data':

								$parameters = 	array('filter' => array(
													array( 'Field' => $actionTypeName, 'Criteria' => 'U|D' ),
													//array( 'Field' => 'dataid', 'Criteria' => '001ANZ001' ),
													)
												);

								break;

					case 'W_Addresses':

								$parameters = 	array('filter' => array(
													array( 'Field' => $actionTypeName, 'Criteria' => 'U|D' ),
													//array( 'Field' => $actionTypeName, 'Criteria' => '-|U|D' ),
													//array( 'Field' => 'addrcode', 'Criteria' => 'ACC06' ),
													//array( 'Field' => 'addritem', 'Criteria' => '1' ),
													)
												);

								break;

					case 'Users':
								$parameters = 	array('filter' => array(
													array( 'Field' => $actionTypeName, 'Criteria' => 'U|D' ),
													//array( 'Field' => $actionTypeName, 'Criteria' => '-|U|D' ),
													//array( 'Field' => 'userid', 'Criteria' => 'LYC01' ),
													//array( 'Field' => 'userid', 'Criteria' => 'DAR999' ),
													)
												);

								break;

					case 'X_Web_Lamp_Category':

								$parameters = 	array('filter' => array(
													array( 'Field' => $actionTypeName, 'Criteria' => 'U|D' ),
													//array( 'Field' => $actionTypeName, 'Criteria' => 'D' ),
													)
												);

								break;

					case 'X_Web_Lamp':
								$parameters = 	array('filter' => array(
													array( 'Field' => $actionTypeName, 'Criteria' => 'U|D' ),
													//array( 'Field' => $actionTypeName, 'Criteria' => '*' ),
													//array( 'Field' => 'partnum', 'Criteria' => 'BUL-GX53-LED-1' ),
													)
												);

								break;

					default:
							generalLib::error('No parameters set for the \'' . $funcFeedWebService . '\' web service');
							break;
				}


				//echo "bef processData\n";

				try {
					$this->procConfig->wsConn->processData($url, $action, $parameters, $batchSize, $funcFeedWebService, $processFunc, $actionTypeName);

				}
				catch(Exception $e){
						generalLib::error($e->getMessage());
				}

				if ($funcFeedWebService == 'Stock_Level' && $this->procConfig->numStockLevelsUpd > 0) {

					$this->procConfig->stockUpdated = date('l, d F h:i a', magentoGeneral::getLocalTimeStamp());

					$this->procConfig->lastStockUpdated = $this->objectManager->get('\Magento\Framework\App\Config\ScopeConfigInterface')->getValue('cronhistory/cronhistoryimport/laststockimport');

					//echo 'stockUpdated = ' . $this->procConfig->stockUpdated . "\n";

					//echo 'lastStockUpdated = ' . $this->procConfig->lastStockUpdated . "\n";


					if ($this->procConfig->stockUpdated != $this->procConfig->lastStockUpdated) {

						$configInterface = $this->objectManager->create('\Magento\Framework\App\Config\ConfigResource\ConfigInterface');

						foreach($this->procConfig->storeDets as $storeId => $storeData) {

							try {
								generalLib::mesg('Saving Last stock updated date - ' . $this->procConfig->stockUpdated . ' for store \'' . $storeId . '\'');
								$configInterface->saveConfig('cronhistory/cronhistoryimport/laststockimport', $this->procConfig->stockUpdated, 'stores', $storeId);
							}

							catch(Exception $e) {
								generalLib::error($e->getMessage());
								generalLib::error('Last stock updated date not saved');
							}

						}

						magentoCache::clearMainCaches( array('config') );
					}

				}

			} else {
				generalLib::error('Cannot process. No ' . $processFunc . ' function for feedwebservice ' . $funcFeedWebService);
			}
		}

		print_r($this->procConfig->shadePartNumsToChk);

		foreach( $this->procConfig->shadePartNumsToChk as $shadePartNumToChk) {
			$this->chkAndUpdShadeChildProducts($shadePartNumToChk);
		}

		//TEMP
		//$this->procConfig->checkRangeAndStyleTables = true;

		if ($this->procConfig->checkRangeAndStyleTables) {
			$this->chkShadeStyleAndRangeTypes();
		}

		magentoImport::delMagFeedLock();

		/*
		//TEMP - TO BE RESTORED
		if ($magFeedIdxLock['createdLock']) {
			magentoIndex::delMagIdxLock();
		}
		*/
	}


	//###########################
	private function getReferenceValues( $funcFeedWebService, $actionType, $actionTypeName = 'actiontype' ) {

		$url = $this->procConfig->baseWsUrl . '/' . $funcFeedWebService;

		//echo 'url = ' . $url . "\n";

		$processFunc = 'process' . $funcFeedWebService . 'Values';

		$parameters = 	array('filter' => array(
													//array( 'Field' => $actionTypeName, 'Criteria' => '-|U' ),
													array( 'Field' => $actionTypeName, 'Criteria' => $actionType ),
													)
												);

		$batchSize = 10;

		//print_r($parameters);

		try {
			$this->procConfig->wsConn->processData($url, 'ReadMultiple', $parameters, $batchSize, $funcFeedWebService, $processFunc, $actionTypeName);

		}

		catch(Exception $e){
				generalLib::error($e->getMessage());
		}
	}


	//###########################
	public function processShade_Silk_ColoursValues($actionType, $silkColourRec) {

		$retArr = array();

		//echo "in silk colour\n";
		$this->procConfig->silkcolour_list[ $silkColourRec->colour_code ] = $silkColourRec->colour_name;

		$rc = false;

		$retArr['rc'] = $rc;

		return $retArr;
	}


	//###########################
	public function processShade_BraidValues($actionType, $braidRec) {

		$retArr = array();

		//echo "in braid\n";
		$this->procConfig->braid_list[ $braidRec->braid_code ] = $braidRec->braid_name;

		$rc = false;

		$retArr['rc'] = $rc;

		return $retArr;
	}


	//###########################
	public function processShade_FringeValues($actionType, $fringeRec) {

		$retArr = array();

		//echo "in fringe\n";
		$this->procConfig->fringe_list[ $fringeRec->fringe_code ] = $fringeRec->fringe_name;

		$rc = false;

		$retArr['rc'] = $rc;

		return $retArr;
	}


	//###########################
	public function processShade_LinerValues($actionType, $linerRec) {
		//print_r($linerRec);
		//echo "in liner\n";

		$retArr = array();

		if ( isset($linerRec->liner_code) ) {
			$this->procConfig->liner_list[ $linerRec->liner_code ] = strtok( $linerRec->liner_name, ' ');
		}

		$rc = false;

		$retArr['rc'] = $rc;

		return $retArr;
	}


	//###########################
	public function processDHL_CableValues($actionType, $cableRec) {

		$retArr = array();

		//echo "in cable\n";
		$this->procConfig->cable_list[ $cableRec->cable_code ] = $cableRec->cable_name;

		$rc = false;

		$retArr['rc'] = $rc;

		return $retArr;
	}


	//###########################
	public function processDHL_GlassValues($actionType, $glassRec) {

		$retArr = array();

		//echo "in glass\n";
		$this->procConfig->glass_list[ $glassRec->glass_code ] = $glassRec->glass_name;

		$rc = false;

		$retArr['rc'] = $rc;

		return $retArr;
	}


	//###########################
	public function processDHL_Inner_PaintValues($actionType, $innerPaintRec) {

		$retArr = array();

		//echo "in inner paint\n";
		$this->procConfig->innerpaint_list[ $innerPaintRec->paint_code ] = $innerPaintRec->paint_name;

		$rc = false;

		$retArr['rc'] = $rc;

		return $retArr;
	}


	//###########################
	public function processDHL_Outer_PaintValues($actionType, $outerPaintRec) {

		$retArr = array();

		//echo "in outer paint\n";
		$this->procConfig->outerpaint_list[ $outerPaintRec->paint_code ] = $outerPaintRec->paint_name;

		$rc = false;

		$retArr['rc'] = $rc;

		return $retArr;
	}


	//###########################
	public function processX_Web_Lamp_CategoryValues($actionType, $xwebLampCatRec) {

		$retArr = array();

		//echo "in outer paint\n";
		$xWebLampCatObj = new stdClass();
		$xWebLampCatObj->lampgroupname = $xwebLampCatRec->lampgroupname;
		$xWebLampCatObj->categoryid = $xwebLampCatRec->categoryid;

		$this->procConfig->xweblampcat_list[ $xwebLampCatRec->lampgroupid ] = $xWebLampCatObj;

		$rc = false;

		$retArr['rc'] = $rc;

		return $retArr;
	}


	//###########################
	public function processAttribute_Set_ListValues($actionType, $attrSetRec) {

		$retArr = array();

		//echo "in outer paint\n";
		$attrSetObj = new stdClass();
		$attrSetObj->set_mag_id = $attrSetRec->set_mag_id;
		$attrSetObj->set_name = $attrSetRec->set_name;

		$this->procConfig->attrset_list[ $attrSetRec->set_id ] = $attrSetObj;

		$rc = false;

		$retArr['rc'] = $rc;

		return $retArr;
	}


	//###########################
	public function processAttribute_Group_ListValues($actionType, $attrGrpRec) {

		$retArr = array();

		//echo "in outer paint\n";
		$attrGrpRecObj = new stdClass();
		$attrGrpRecObj->grp_mag_id = $attrGrpRec->grp_mag_id;
		$attrGrpRecObj->grp_name = $attrGrpRec->grp_name;

		$this->procConfig->attrgrp_list[ $attrGrpRec->grp_id ] = $attrGrpRecObj;

		$rc = false;

		$retArr['rc'] = $rc;

		return $retArr;
	}


	//###########################
	public function processAttribute_ListValues($actionType, $attrRec) {

		$retArr = array();

		//echo "in outer paint\n";
		$attrRecObj = new stdClass();
		$attrRecObj->attr_code = $attrRec->attr_code;
		$attrRecObj->attr_type = $attrRec->attr_type;

		$this->procConfig->attr_list[ $attrRec->attr_Id ] = $attrRecObj;

		$rc = false;

		$retArr['rc'] = $rc;

		return $retArr;
	}


	//###########################
	public function processAttribute_Set_ListWebService($actionType, $attrSetRec) {

		$retArr = array();

		$rc = true;

		generalLib::mesg('');
		generalLib::mesg('====');

		print_r($attrSetRec);

		$retArr = magentoAttrImport::processProdAttrSet($actionType, $attrSetRec, 'Default');

		if ($retArr['rc'] && ( ($actionType == 'U' && $attrSetRec->set_mag_id == '') || $actionType == 'D') ) {

			$this->setWsRecMagId( $retArr['setId'], $attrSetRec, 'set_mag_id', $retArr);

		}

		return $retArr;
	}


	//###########################
	public function processAttribute_Group_ListWebService($actionType, $attrGrpRec) {

		$retArr = array();

		$rc = true;

		generalLib::mesg('');
		generalLib::mesg('====');

		$attrGrpRec->set_mag_id = $this->procConfig->attrset_list[ $attrGrpRec->grp_set_id ]->set_mag_id;

		$attrGrpRec->set_name = $this->procConfig->attrset_list[ $attrGrpRec->grp_set_id ]->set_name;


		print_r($attrGrpRec);

		$retArr = magentoAttrImport::processProdAttrGrp($actionType, $attrGrpRec);

		//print_r($retArr);

		if ($retArr['rc'] && ( ($actionType == 'U' && $attrGrpRec->grp_mag_id == '') || $actionType == 'D') ) {

			$this->setWsRecMagId( $retArr['grpId'], $attrGrpRec, 'grp_mag_id', $retArr);

		}

		return $retArr;
	}


	//###########################
	public function processAttribute_ListWebService($actionType, $attrRec) {

		$retArr = array();

		$rc = true;

		generalLib::mesg('');
		generalLib::mesg('====');

		$attrRec->set_mag_id = $this->procConfig->attrset_list[ $attrRec->attr_set_id ]->set_mag_id;

		$attrRec->set_name = $this->procConfig->attrset_list[ $attrRec->attr_set_id ]->set_name;

		$attrRec->grp_mag_id = $this->procConfig->attrgrp_list[ $attrRec->attr_grp_id ]->grp_mag_id;

		$attrRec->grp_name = $this->procConfig->attrgrp_list[ $attrRec->attr_grp_id ]->grp_name;

		print_r($attrRec);
		//die();

		$retArr = magentoAttrImport::processAttribute($actionType, $attrRec);

		if ($retArr['rc'] && ( ($actionType == 'U' && $attrRec->attr_mag_id == '') || $actionType == 'D') ) {

			$this->setWsRecMagId( $retArr['attrId'], $attrRec, 'attr_mag_id', $retArr);

		}

		return $retArr;
	}


	//###########################
	public function processProduct_AttributeWebService($actionType, $prodAttrRec) {

		$retArr = array();


		$rc = true;

		generalLib::mesg('');
		generalLib::mesg('====');

		$prodAttrRec->attr_code = $this->procConfig->attr_list[ $prodAttrRec->pattr_attr_id ]->attr_code;

		$prodAttrRec->attr_type = $this->procConfig->attr_list[ $prodAttrRec->pattr_attr_id ]->attr_type;

		if ( !isset( $prodAttrRec->ValueAsText ) ) {
			$prodAttrRec->ValueAsText = '';
		}

		$prodAttrRec->pattr_value = $prodAttrRec->ValueAsText;

		$prodAttrRec->pattr_product_id = trim($prodAttrRec->pattr_product_id);

		print_r($prodAttrRec);

		$rc = magentoAttrImport::processProdAttributes($actionType, $prodAttrRec);

		$retArr['rc'] = $rc;

		return $retArr;
	}


	//###########################
	public function getNumProdAttrs( $product ) {

		$parameters = 	array('filter' => array(
									array( 'Field' => 'pattr_product_id', 'Criteria' => $product->getSku() ),
									array( 'Field' => 'pattr_action_type', 'Criteria' => 'U|D' ),
									//TEMP
									//array( 'Field' => 'pattr_action_type', 'Criteria' => '-|U|D' ),
									)
								);

		//print_r($parameters);

		$this->procConfig->numProdAttrs = 0;

		$this->procConfig->wsAddlConn->processData($this->procConfig->readProdAttrUrl, 'ReadMultiple', $parameters, '1', $this->procConfig->readProdAttrWebService, 'processGetNumProdAttrs', '');

		//echo 'sku = ' . $product->getSku() . ' num prod attrs = ' . $this->procConfig->numProdAttrs . "\n";
		//die();

		return $this->procConfig->numProdAttrs;
	}


	//###########################
	public function updProdAttrs( $product ) {

		//THIS FUNCTION IS JUST NEEDED TO STOP AN ATTEMPT TO UPDATE FLAGS VIA A DATABASE CONNECTION
		$rc = true;

		return $rc;

	}


	//###########################
	public function processGetNumProdAttrs( $actionType, $prodAttrRec ) {

		$retArr = array();

		$this->procConfig->numProdAttrs++;

		$rc = false;

		$retArr['rc'] = $rc;

		return $retArr;
	}


	//###########################
	public function postProdAttrApplied($product, $prodAttrArr) {
		$rc = true;
		$updProd = false;

		//PRODUCT ATTRIBUTE 1 IS 'short_description'
		if ( in_array('1', $prodAttrArr) ) {
			$oldMetaDescription = $product->getMetaDescription();
			$newMetaDescription = str_replace('<br />', ' ', $product->getShortDescription() );
			$newMetaDescription = htmlspecialchars_decode( $newMetaDescription );
			$newMetaDescription = trim( $newMetaDescription );

			if ($oldMetaDescription != $newMetaDescription) {
				generalLib::mesg('Setting meta_description to \'' . $newMetaDescription . '\'');
				$product->setMetaDescription( $newMetaDescription );
				$updProd = true;
			}
		}

		return array(
						'rc' 		=> $rc,
                        'updProd'     => $updProd
                    );
	}


	//###########################
	public function processCat_CategoriesWebService($actionType, $catRec) {
		$rc = true;

		$retArr = array();

		print_r($catRec);

		generalLib::mesg('');
		generalLib::mesg('====');

		//$extCatId = $catRec->$this->procConfig->extCatId;
		$extCatId = $catRec->id;
		$magCatIdFld = $this->procConfig->catCustomAttrMappings[ $this->procConfig->extCatId ];

		$extCatParentIdFld = $catRec->{ $this->procConfig->extParentCatId };
		$magCatParentIdFld = $this->procConfig->catCustomAttrMappings[ $this->procConfig->extParentCatId ];

		$extCatParentId = $catRec->parent;

		//echo 'excatId var = ' . $this->procConfig->extCatId . "\n";
		//echo 'extCatId = ' . $extCatId . "\n";
		//echo 'extCatParentId = ' . $extCatParentId . "\n";
		//die();


		$category = magentoCat::getCategory( $magCatIdFld, $extCatId);

		if ($actionType == 'D') {
			if ($category) {
				try {
					generalLib::mesg('deleting category external cat id ' . $extCatId);
					$category->delete();
				}

				catch(Exception $e){
					generalLib::error($e->getMessage());
					$rc = false;
				}
			} else {
				generalLib::warn('Cannot delete external cat id ' . $extCatId . ' - does not exist');
			}

			$retArr['rc'] = $rc;

			return $retArr;
		}


		$updCat = false;


		if (!$category || magentoImport::processCustomAttrs($this, $category, $catRec, array($this->procConfig->extParentCatId => 'T'), $this->procConfig->catCustomAttrMappings)) {

			//echo 'extCatParentId = ' . $extCatParentId . "\n";

			if ( $extCatParentId == -1 ) {
				$parentMagPath = $this->procConfig->rootStoreCatPath;
				$parentMagCatId = $this->procConfig->rootStoreCatId;
			} else {
				$parentCategory = magentoCat::getCategory($magCatIdFld, $extCatParentId);

				if ($parentCategory != false) {
					$parentMagPath = $parentCategory->getPath();
					$parentMagCatId = $parentCategory->getId();
				} else {
					generalLib::warn('Cannot find parent external cat id ' . $extCatParentId . ' - aborting input');

					$rc = false;

					$retArr['rc'] = $rc;

					return $retArr;
				}
			}

			//echo 'parentMagPath = ' . $parentMagPath . "\n";
			//echo 'parentMagCatId = ' . $parentMagCatId . "\n";

			if ($category) {
				if ($category->getParentId() != $parentMagCatId) {
					$category->move($parentMagCatId, null);
					generalLib::mesg('Moving category to magento parent id \'' . $parentMagCatId . '\' (' . $extCatParentId . ')');
				}
			}

			$updCat = true;
		}


		if (!$category) {


			generalLib::mesg('Creating category for external cat id \'' . $extCatId . '\'');
			//die();

			$category = $this->objectManager->create('Magento\Catalog\Model\Category');
			//$categoryFactoryObj = $this->objectManager->get('Magento\Catalog\Model\CategoryFactory');
			//$category = $this->objectManager->create('Magento\Catalog\Model\CategoryFactory');
			$category->setAvailableSortBy('name');
			$category->setDefaultSortBy('name');
			$category->setDisplayMode('PRODUCTS');
			$category->setPath($parentMagPath);
			$category->setIncludeInMenu(1);
			$category->setIsAnchor(1);
			$updCat = true;

		} else {

			$category = $this->objectManager->create('Magento\Catalog\Model\Category')->load($category->getId());
			generalLib::mesg('Updating category for external cat id \'' . $extCatId . '\'');

		}


		$updAttrs = magentoImport::processMagAttrs($category, $catRec, $this->procConfig->catMagAttrs);

		if ($updAttrs && (!$updCat)) {
			$updCat = true;
		}

		if (magentoImport::processMagAttrs($category, $catRec, $this->procConfig->catMagNameAttr)) {
			$oldUrlKey = $category->getUrlKey();
			$newUrlKey =  $this->objectManager->create('Magento\Catalog\Model\Category')->formatUrlKey( $category->getName() );


			//echo 'oldUrlKey = ' . $oldUrlKey . "\n";
			//echo 'newUrlKey = ' . $newUrlKey . "\n";

			if ($oldUrlKey != $newUrlKey) {
				$category->setUrlKey( $newUrlKey );
				generalLib::mesg('Re-creating URL key');

				$category->setData('url_key_create_redirect', true);
				//generalLib::mesg('URL create redirect is disabled');

				$updCat = true;
			}
		}


		$updAttrs = magentoImport::processMagAttrs($category, $catRec, $this->procConfig->catMagAttrs);

		if ($updAttrs && (!$updCat)) {
			$updCat = true;
		}


		$updAttrs = magentoImport::processCustomAttrs($this, $category, $catRec, $this->procConfig->catCustomAttrs, $this->procConfig->catCustomAttrMappings);

		if ($updAttrs && (!$updCat)) {
			$updCat = true;
		}


		if ($updCat) {
			try {
				generalLib::mesg('saving category for external cat id \'' . $extCatId . '\' \''. $category->getDescription() . '\' parent id \'' . $category->getParentId() . '\' (' . $extCatParentId . ')');
				//print_r($category->getData());
				$category->save();
			} catch(Exception $e){
				generalLib::error($e->getMessage());
				$categoryID = false;
				$rc = false;
			}
		} else {
			generalLib::mesg('No category updates to apply');
		}

		$retArr['rc'] = $rc;

		/*
		if ($catRec->id == '2') {
			die();
		}
		*/

		return $retArr;
	}


	//###########################
	public function processIL_DocumentWebService($actionType, $leafletRec) {
		$rc = true;

		$retArr = array();

		generalLib::mesg('');
		generalLib::mesg('====');

		$product = $this->getProductForPartnum($leafletRec->sku, true);

		if (!$product) {
			$rc = false;

			$retArr['rc'] = $rc;

			return $retArr;
		}

		if ($actionType == 'D') {
			$leafletRec->file_eng = '';
			$leafletRec->file_non_eng = '';
		}

		$productSku = $product->getSku();

		$updAttrs = magentoImport::processMagAttrs($product, $leafletRec, $this->procConfig->leafletMagAttrs);

		if ($updAttrs) {

			try {
				generalLib::mesg('Saving product sku \'' . $productSku . '\'');
				$product->save();
			}

			catch(Exception $e) {
				generalLib::error($e->getMessage());
				generalLib::error('Product sku \'' . $prodSku . '\' not saved');
				$rc = false;
			}

			//die();

		} else {
			generalLib::mesg('No leaflet updates to apply for product sku \'' . $productSku . '\'');
		}

		$retArr['rc'] = $rc;

		return $retArr;
	}


	//###########################
	public function processLocal_StockistsWebService($actionType, $stockistRec) {
		$rc = true;

		$retArr = array();

		generalLib::mesg('');
		generalLib::mesg('====');

		//print_r($stockistRec);

		$stockistVars = get_object_vars($stockistRec);

		foreach($stockistVars as $stockField => $stockFieldVal) {
			$stockistRec->$stockField = trim($stockistRec->$stockField);
		}

		$stockistId = $stockistRec->stockist_id;

		$stockistMagRec = $this->objectManager->create( $this->procConfig->stockistModel )->load($stockistId, 'stockist_id');

		if ($actionType == 'D') {

			if ($stockistMagRec->getId() == '') {

				generalLib::warn('Cannot delete - no stockist record for stockist id: ' . $stockistId);
				$rc = false;

			} else {

				generalLib::mesg('Deleting stockist for stockist id ' . $stockistId);

				try {
					$stockistMagRec->delete();
					generalLib::mesg('Stockist deleted');
				}

				catch (Exception $e) {
					generalLib::error($e->getMessage());
					generalLib::error('Stockist not deleted');
					$rc = false;
				}

			}

			$retArr['rc'] = $rc;
			return $retArr;
		}


		if ($stockistMagRec->getId() == '') {

			generalLib::mesg('Creating stockist for stock id : \'' . $stockistId . '\'');
			$stockist = $this->objectManager->create( $this->procConfig->stockistModel );

		} else {
			generalLib::mesg('Updating stockist for stock id : \'' . $stockistId . '\'');
		}


		if ( isset( $stockistRec->postcode ) ) {
			$lonLat = $this->getLonLat($stockistRec->postcode);

			$stockistRec->longitude = $lonLat['lon'];
			$stockistRec->latitude = $lonLat['lat'];
		}

		$updAttrs = magentoImport::processMagAttrs($stockistMagRec, $stockistRec, $this->procConfig->stockistMagAttrs);

		if ($updAttrs) {

			try {
				generalLib::mesg('Saving stockist id \'' . $stockistId . '\'');
				$stockistMagRec->save();
			}

			catch(Exception $e) {
				generalLib::error($e->getMessage());
				generalLib::error('Stockist id \'' . $stockistId . '\' not saved');
				$rc = false;
			}

		} else {
			generalLib::mesg('No stockist updates for stockist id \'' . $stockistId . '\' to apply');
		}

		$retArr['rc'] = $rc;

		//die();

		return $retArr;
	}


	//###########################
	// Getting the Longitude and Latitude of each address
    private function getLonLat($address) {
        $points = array();

        if (strlen($address) < 5) {
            $address .= ' United Kingdom';
        }

        // Setting API Key

        //$this->procConfig->googleApiKey = $this->objectManager->get('\Magento\Framework\App\Config\ScopeConfigInterface')->getValue('stockistlocator/locatorconfiguration/access_token');
        //$apiKey = $this->_scopeConfig->getValue('stockistlocator/locatorconfiguration/access_token', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

        //ini_set('allow_url_fopen', 1);
        $url = 'https://maps.googleapis.com/maps/api/geocode/json?key=' . $this->procConfig->googleApiKey . '&address=' . str_replace(' ', '+', $address);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
        $response = json_decode(curl_exec($ch));

        if ($response->results) {
            $coordinates = $response->results[0]->geometry->location;

            $points['lon'] = $coordinates->lat;
            $points['lat'] = $coordinates->lng;
        } else {
            $points['lon'] = 0;
            $points['lat'] = 0;
        }

        return $points;
    }


	//###########################
	public function processPart_RelatedWebService($actionType, $partRelRec) {
		$rc = true;

		$retArr = array();

		generalLib::mesg('');
		generalLib::mesg('====');

		print_r($partRelRec);

		$product = $this->getProductForPartnum($partRelRec->partcode, true);

		if (!$product) {
			$rc = false;
			return $rc;
		}

		$productSku = $product->getSku();

		$linkedProductId = $this->objectManager->get('Magento\Catalog\Model\Product')->getIdBySku( $partRelRec->relatedpart );

		if ($linkedProductId) {
			$updProd = magentoProd::processLinkedProduct($actionType, $product, 'related', 'sku', 'magId', $linkedProductId);
		} else {
			generalLib::warn('Cannot find related product for part number \'' . $partRelRec->relatedpart . '\'');
			$updProd = false;
		}

		if ($updProd) {
			try {
				generalLib::mesg('Saving product sku \'' . $productSku . '\'');
				$product->save();
			}

			catch(Exception $e) {
				generalLib::error($e->getMessage());
				generalLib::error('Product sku \'' . $productSku . '\' not saved');
				$rc = false;
			}
		} else {
			generalLib::mesg('No related part updates to apply');
		}

		$retArr['rc'] = $rc;

		//die();

		return $retArr;
	}


	//###########################
	public function processMagentoOrderHeaderWebService($actionType, $orderRec) {
		$rc = true;

		$orderId = $orderRec->mordernum;

                $order = $this->objectManager->create('\Magento\Sales\Model\Order')->loadByIncrementId($orderId);

		if ($order) {
			$order->setDarOrderId( $orderRec->son );
			generalLib::mesg('Setting dar order id to \'' . $orderRec->son . '\'');

			$history = $order->addStatusHistoryComment('Dar order id set to ' . $orderRec->son . ' by the DAR DB Processor.', false);
			$history->setIsCustomerNotified(false);

			try {
				generalLib::mesg('Saving magento order id \'' . $orderId . '\'');
				$order->save();
			}

			catch(Exception $e) {
				generalLib::error($e->getMessage());
				generalLib::error('Order id \'' . $orderId . '\' not saved');
				$rc = false;
			}
		} else {
			generalLib::warn('Magento order  id \'' .  $orderId . '\' not found');
		}

		return $rc;
	}


	//###########################
	public function processPrice_ListWebService($actionType, $prodPrcPromRec) {
		$rc = true;

		$retArr = array();

		generalLib::mesg('');
		generalLib::mesg('====');

		print_r($prodPrcPromRec);

		$product = $this->getProductForPartnum($prodPrcPromRec->plpart, true);

		if (!$product) {
			$rc = false;

			$retArr['rc'] = $rc;

			return $retArr;
		}

		$prodSku = $product->getSku();

		generalLib::mesg('Processing product sku \'' . $prodSku . '\'');

		if ($prodPrcPromRec->pltype == '') {
			generalLib::warn('Blank price list type - aborting import');
			$rc = false;

			$retArr['rc'] = $rc;

			return $retArr;
		}

		switch( $prodPrcPromRec->pltype ) {
			case 'Group':

						if ( isset($prodPrcPromRec->pluniqueid) && $prodPrcPromRec->pluniqueid > 0 ) {

							$discountData = new stdClass();
							$discountData->extId = $prodPrcPromRec->pluniqueid;
							$discountData->websiteId = '0'; //ALL WEB SITES
							$discountData->storeId = '0'; //DEFAULT STORE
							$discountData->productNo = $prodSku;
							$discountData->customerNo = $prodPrcPromRec->plcode;
							$discountData->source = 'pricelist';
							$discountData->priceQty = $prodPrcPromRec->plbrkqty;
							$discountData->price = $prodPrcPromRec->plunitprice;
							$discountData->startDate = date('Y-m-d', strtotime($prodPrcPromRec->plfromdate));
							$discountData->endDate = date('Y-m-d', strtotime($prodPrcPromRec->pltodate));

							$rc = magentoCustomPrices::processCustomPrice($actionType, $product, $discountData);

						} else {

							generalLib::warn('Cannot import price for plcode \'' .  $prodPrcPromRec->plcode . '\' - pluniqueid is undefined or zero');

							$rc = false;
						}

						break;

			case 'Customer':

						$prodPrcPromRec->plpromotioncode = $prodPrcPromRec->plcode . '_' . date('dmY', strtotime($prodPrcPromRec->plfromdate));
						$prodPrcPromRec->xpromotype = 'CUST';
						$prodPrcPromRec->xpromofreeqty = '0';

						$retArrXpromo = $this->processXpromoRec($actionType, $prodPrcPromRec);

						$rc = $retArrXpromo['rc'];

						if ( $rc != false ) {
							$retArrXpromoPart = $this->processXpromopartRec($actionType, $prodPrcPromRec);

							$rc = $retArrXpromoPart['rc'];
						}

						break;


			case 'AllCustomers':

						$prodPrcPromRec->xpromotype = 'PRICE';
						$prodPrcPromRec->xpromocust = '';
						$prodPrcPromRec->xpromofreeqty = '0';

						$retArrXpromo = $this->processXpromoRec($actionType, $prodPrcPromRec);

						$rc = $retArrXpromo['rc'];

						if ( $rc != false ) {
							$retArrXpromoPart = $this->processXpromopartRec($actionType, $prodPrcPromRec);
						}

						break;

			default:
						generalLib::warn('Unrecognized pltype \'' . $prodPrcPromRec->pltype  . '\'');
						$rc = false;
						break;
		}

		//print_r($discountData);

		$retArr['rc'] = $rc;

		//die();

		return $retArr;

	}


		//###########################
	public function processXpromoRec($actionType, $xpromoRec) {

		$rc = true;

		$retArr = array();

		generalLib::mesg('');

		if ($xpromoRec->pltodate == '0001-01-01') {
			$xpromoRec->pltodate = '2100-01-01';
		}

		print_r($xpromoRec);

		$xpromoVars = get_object_vars($xpromoRec);

		foreach($xpromoVars as $xpromoField => $xpromoFieldVal) {
			$xpromoRec->$xpromoField = trim($xpromoRec->$xpromoField);
		}

		//print_r($xpromoRec);

		$xpromoCode = $xpromoRec->plpromotioncode;

		$endDateExp = ( strtotime($xpromoRec->pltodate) < $this->procConfig->currDateTStamp );

		$xpromoColl = $this->objectManager->create( $this->procConfig->xpromoModel )
                        ->getCollection()
                        ->addFieldToFilter('xpromo_code', $xpromoCode)
						->addFieldToFilter('xpromo_from', $xpromoRec->plfromdate);

		$xpromoMagRec = $this->objectManager->create( $this->procConfig->xpromoModel )->load($xpromoCode, 'xpromo_code');

		if ($xpromoMagRec->getId() == '' && $actionType == 'U' && $endDateExp) {
			generalLib::warn('Cannot create promo for code \'' . $xpromoCode .  ' - Promo already expired on \'' . $xpromoRec->pltodate . '\' - flagging promo for deletion');

			$retArr['actionType'] = 'D';

			return $retArr;
		}

		if ($actionType == 'D') {

			if ($xpromoMagRec->getId() == '') {
				generalLib::warn('Cannot delete - no xpromo record for promo code: ' . $xpromoCode);
				$rc = false;
			} else {
				generalLib::mesg('Deleting xpromo for promo code ' . $xpromoCode);

				try {
					$xpromoMagRec->delete();
					generalLib::mesg('Xpromo deleted');
				}

				catch (Exception $e) {
					generalLib::error($e->getMessage());
					generalLib::error('Xpromo not deleted');
					$rc = false;
				}
			}

			$retArr['rc'] = $rc;
			return $retArr;
		}

		if ($xpromoMagRec->getId() == '') {

			//$xpromo = Mage::getModel('darlightingdiscounts/xpromo');

			$xpromoMagRec = $this->objectManager->create( $this->procConfig->xpromoModel );

			generalLib::mesg('Creating xpromo for promo code \'' . $xpromoCode . '\'');

		} else {
			generalLib::mesg('Updating xpromo for promo code \'' . $xpromoCode . '\'');
		}

		$updPromo = false;


		$updAttrs = magentoImport::processMagAttrs($xpromoMagRec, $xpromoRec, $this->procConfig->xpromoMagAttrs);

		if ( $updAttrs ) {
			$updPromo = true;
		}

		$updAttrs = magentoImport::processCustomAttrs($this, $xpromoMagRec, $xpromoRec, $this->procConfig->xpromoCustomAttrs, $this->procConfig->xpromoCustomAttrMappings);

		if ( $updAttrs && ( !$updPromo) ) {
			$updPromo = true;
		}

		if ($updPromo) {

			try {

				generalLib::mesg('Saving promo code \'' . $xpromoCode . '\'');
				$xpromoMagRec->save();

			} catch(Exception $e) {

				generalLib::error($e->getMessage());
				generalLib::error('promo code not saved');
				$rc = false;

			}

		} else {
			generalLib::mesg('No promo updates to apply for promo code \'' . $xpromoCode . '\'');
		}

		$retArr['rc'] = $rc;

		return $retArr;
	}


	//###########################
	public function processXpromopartRec($actionType, $xpromoPartRec) {

		$rc = true;

		$retArr = array();

		generalLib::mesg('');

		print_r($xpromoPartRec);

		$xpromoCode = $xpromoPartRec->plpromotioncode;

		$xpromoPartMagRec = $this->objectManager->create( $this->procConfig->xpromoModel )->load($xpromoCode, 'xpromo_code');

		if ($xpromoPartMagRec->getId() == '' && $actionType == 'U') {
			generalLib::warn('Promo code \'' . $xpromoCode . ' does not exist - flagging promo part for deletion');
			$retArr['updWS_actiontype'] = 'D';

			return $retArr;
		}

		$xpromoPart = $xpromoPartRec->plpart;

		$xpromoPartColl = $this->objectManager->create( $this->procConfig->xpromoPartModel )
                        ->getCollection()
                        ->addFieldToFilter('xpromo_code', $xpromoCode )
						->addFieldToFilter('xpromo_part', $xpromoPart );

		if ($actionType == 'D') {

			if ( $xpromoPartColl->getSize() > 0 ) {

				try {
					generalLib::mesg('Deleting promo part \'' . $xpromoPart . '\' for promo code \'' . $xpromoCode . '\'');
					$xpromoPartMagRec = $xpromoPartColl->getFirstItem();
					$xpromoPartMagRec->delete();
					generalLib::mesg('promo part deleted');
				}

				catch (Exception $e) {
					generalLib::error($e->getMessage());
					$rc = false;
				}

			} else {

				generalLib::warn('Cannot delete - no promo part \'' . $xpromoPart . '\' record for promo code: \'' . $xpromoCode . '\'');
				$rc = false;

			}

			$retArr['rc'] = $rc;

			return $retArr;

		} else {

			if ( $xpromoPartColl->getSize() == 0 ) {

				generalLib::mesg('Creating promo part \'' . $xpromoPart . '\' for promo code \'' . $xpromoCode . '\'');
				$xpromoPartMagRec = $this->objectManager->create( $this->procConfig->xpromoPartModel );

			} else {

				generalLib::mesg('Updating promo part \'' . $xpromoPart . '\' for promo code \'' . $xpromoCode . '\'');
				$xpromoPartMagRec = $xpromoPartColl->getFirstItem();
			}

			$updPromoPart = magentoImport::processMagAttrs($xpromoPartMagRec, $xpromoPartRec, $this->procConfig->xpromoPartMagAttrs);

			if ( $updPromoPart ) {

				try {
					generalLib::mesg('Saving promo part \'' . $xpromoPart . '\' for promo code \'' . $xpromoCode . '\'');
					$xpromoPartMagRec->save();
				}

				catch(Exception $e) {
					generalLib::error($e->getMessage());
					generalLib::error('promo part not saved for promo part \'' . $xpromoPart . '\' and promo code \'' . $xpromoCode . '\'');
					$rc = false;
				}

			} else {
				generalLib::mesg('No promo part updates to apply for promo part \'' . $xpromoPart . '\' and promo code \'' . $xpromoCode . '\'');
			}

		}

		$retArr['rc'] = $rc;

		return $retArr;
	}


	//###########################
	public function processProduct_CategoryWebService($actionType, $prodCatRec) {
		$rc = true;

		$retArr = array();

		generalLib::mesg('');
		generalLib::mesg('====');

		print_r($prodCatRec);

		$product = $this->getProductForPartnum($prodCatRec->productid, true);

		if (!$product) {
			$rc = false;

			$retArr['rc'] = $rc;

			return $retArr;
		}

		//die();

		//TO DO - STANDARDISE THE NAMES AND VALUES FOR PROCESSING IN processProdCategories()
		$prodCatRec->prodCatTable = 'productcategory';
		$prodCatRec->prodCatIdName = 'productid';
		$prodCatRec->catIdName = 'categoryid';
		$prodCatRec->productId = $prodCatRec->productid;
		$prodCatRec->catId = $prodCatRec->categoryid;
		$prodCatRec->actionTypeName = 'actiontype';

		$rc = magentoCat::processProdCategories($actionType, $product, $prodCatRec);

		$retArr['rc'] = $rc;

		return $retArr;
	}


	//###########################
	public function getNumProdCats( $product ) {

		$parameters = 	array('filter' => array(
									array( 'Field' => 'productid', 'Criteria' => $product->getSku() ),
									array( 'Field' => 'actiontype', 'Criteria' => 'U|D' ),
									)
								);

		$this->procConfig->numProdCats = 0;

		$this->procConfig->wsAddlConn->processData($this->procConfig->readProdCatUrl, 'ReadMultiple', $parameters, '1', $this->procConfig->readProdCatWebService, 'processGetNumProdCats', '');

		echo 'sku = ' . $product->getSku() . ' num prod cats = ' . $this->procConfig->numProdCats . "\n";
		//die();

		return $this->procConfig->numProdCats;
	}


	//###########################
	public function updProdCats( $product ) {

		//THIS FUNCTION IS JUST NEEDED TO STOP AN ATTEMPT TO UPDATE FLAGS VIA A DATABASE CONNECTION
		$rc = true;

		return $rc;

	}


	//###########################
	public function processGetNumProdCats( $actionType, $prodCatRec ) {

		$retArr = array();

		$this->procConfig->numProdCats++;

		$rc = false;

		$retArr['rc'] = $rc;

		return $retArr;
	}


	//###########################
	public function processShade_RangesWebService($actionType, $shadeRec) {
		$rc = true;

		$retArr = array();

		generalLib::mesg('');
		generalLib::mesg('====');

		print_r($shadeRec);

		/*
		if ($shadeRec->shade_twotone == '0') {
			echo "two tone not set\n";
		}

		if ($shadeRec->shade_twotone == '1') {
			echo "two tone set\n";
		}

		if ($shadeRec->shade_threetier == '0') {
			echo "three tier not set\n";
		}

		if ($shadeRec->shade_threetier == '1') {
			echo "three tier set\n";
		}
		*/

		//die();

		if ( isset($shadeRec->shade_partnum) ) {

			$product = $this->getProductForPartnum($shadeRec->shade_partnum, true);

		} else {

			generalLib::warn('No part number for id \'' . $shadeRec->id . '\'');
			$product = false;

		}

		if (!$product) {
			$rc = false;

			$retArr['rc'] = $rc;

			return $retArr;
		}

		//NEED TO THINK ABOUT DELETING THE CUSTOM OPTIONS IF SET FOR DELETION

		$productId = $product->getId();
		$productSku = $product->getSku();

		$updProd = false;

		$updShadeRangeName = magentoImport::chkDropdownAttr($product, 'shade_range_name', (string) $shadeRec->shade_range_name, $this->procConfig->shadeRangeOpts);

		if ($updShadeRangeName && (!$updProd)) {
			$updProd = true;
		}


		//IF EMPTY, NAV DOES NOT INCLUDE IT IN THE RETURNED RECORD
		if ( !isset( $shadeRec->shade_baffle ) ) {
			$shadeRec->shade_baffle = '';
		}

		$updAttrs = magentoImport::chkDropdownAttr($product, 'shade_baffle', (string) $shadeRec->shade_baffle, $this->procConfig->shadeBaffleOpts);

		if ($updAttrs && (!$updProd)) {
			$updProd = true;
		}


		$updAttrs = magentoImport::processMagAttrs($product, $shadeRec, $this->procConfig->shaderangesMagAttrs);

		if ($updAttrs && (!$updProd)) {
			$updProd = true;
		}


		$updAttrs = magentoImport::processMagAttrs($product, $shadeRec, $this->procConfig->shaderangeStyleMagAttr);

		if ($updAttrs) {
			$this->procConfig->checkRangeAndStyleTables = true;

			if (!$updProd) {
				$updProd = true;
			}
		}


		$updAttrs = magentoImport::processCustomAttrs($this, $product, $shadeRec, $this->procConfig->shaderangesCustomAttrs);

		if ($updAttrs && (!$updProd)) {
			$updProd = true;
		}


		$oldCatIds = $product->getCategoryIds();

		if ( magentoImport::processCustomAttrs($this, $product, $shadeRec, array('categoryid' => 'T'), array('categoryid' => 'general_cat_id')) ) {
			$magCatIdFld = $this->procConfig->catCustomAttrMappings[ $this->procConfig->extCatId ];

			//echo 'magCatIdFld = ' . $magCatIdFld . "\n";
			$category = magentoCat::getCategory($magCatIdFld, $shadeRec->categoryid);

			if ($category) {
				generalLib::mesg('setting category id to \'' . $category->getId() . '\' (' . $shadeRec->categoryid . ')');
				$product->setCategoryIds( $category->getId() );
			} else {
				generalLib::warn('Cannot find category via external category id \'' . $shadeRec->categoryid . '\'');
				$product->setGeneralCatId( $oldCatIds );
				$rc = false;
			}

			$updProd = true;
		}


		if (magentoImport::processMagAttrs($product, $shadeRec, $this->procConfig->shaderangeTypeMagAttr)) {

			//THIS IS IMPRECISE, AS THE SHADERANGES TABLE HAS NO ONE DEFINITION FOR A SPECIFIC SHADE RANGE TYPE. THE RANGE TYPE IS SPECIFIED ON MULTIPLE RECORDS.
			//IT WOULD BE BETTER FOR THE SHADE RANGE TYPE (HARD, SOFT, DHL) AND ITS LABEL TO BE DEFINED IN ITS OWN TABLE AND REFERENCED VIA A UNIQUE ID FROM THE EXISTING SHADE RANGES TABLE

			$collection = $this->objectManager->create('StarDigital\BespokeConfigurator\Model\Rangetype')->getCollection()
				->addFieldToFilter('range_type_code', $shadeRec->range_type);

			 if ( $collection->getSize() == 0 ) {
	        	generalLib::mesg('Creating shade range type \'' . $shadeRec->range_type . '\' for shade part num \'' . $shadeRec->shade_partnum . '\'');
		    	$bsRangeTypeRec = $this->objectManager->create('StarDigital\BespokeConfigurator\Model\Rangetype');
		    	$updAc = true;
	        } else {
	        	generalLib::mesg('Updating shade range type \'' . $shadeRec->range_type . '\' for shade part num \'' . $shadeRec->shade_partnum . '\'');
				$bsRangeTypeRec = $collection->getFirstItem();
				$updAc = false;
	        }

	        $shadeRec->range_type_label = $this->procConfig->shaderangeTypeNames[ $shadeRec->range_type ];

	        $updAttrs = magentoImport::processMagAttrs($bsRangeTypeRec, $shadeRec, $this->procConfig->shaderangesTypeMagAttrs);

	        if ($updAttrs) {
				try {
					generalLib::mesg('Saving shade range type');
					$bsRangeTypeRec->save();
				}

				catch(Exception $e) {
					generalLib::error($e->getMessage());
					generalLib::error('Shade range type \'' . $shadeRec->range_type . '\' not saved for shade part num \'' . $shadeRec->shade_partnum . '\'');
					$rc = false;
				}

			} else {
				generalLib::mesg('No shade range type updates to apply');
			}

			$updShadeRangeType = true;

			$this->procConfig->checkRangeAndStyleTables = true;

			$updProd = true;
		} else {
			$updShadeRangeType = false;
		}

		//if ($updShadeRangeName || $updShadeRangeType) {
			//THIS IS IMPRECISE, AS THE SHADERANGES TABLE ALLOWS THE SAME SHADE RANGE TO HAVE DIFFERENT RANGE TYPES, ALTHOUGH THIS IS UNLIKELY
			//HOWEVER, TO BE SURE, THE SHADE RANGE NAME AND THE RANGE TYPE SHOULD BE IN A SEPARATE SHADE RANGE TABLE. THIS TABLE WOULD BE LINKED TO THE EXISTING SHADE RANGE TABLE (RE-NAMED SHADE RANGE PARTS) VIA A UNIQUE ID.
			//THE RANGE TYPE IN THE NEW SHADE RANGE TABLE WOULD ALSO BE A UNIQUE ID LINKING TO A NEW SHADE RANGE TYPE TABLE (SEE BELOW)

			$shadeRec->shade_range_label = ucfirst( strtolower($shadeRec->shade_range_name) );

			$collection = $this->objectManager->create('StarDigital\BespokeConfigurator\Model\Range')->getCollection()
				->addFieldToFilter('label', $shadeRec->shade_range_label);

			 if ( $collection->getSize() == 0 ) {
	        	generalLib::mesg('Creating shade range link \'' . $shadeRec->shade_range_label . '\' for shade part num \'' . $shadeRec->shade_partnum . '\'');
		    	$bsRangeRec = $this->objectManager->create('StarDigital\BespokeConfigurator\Model\Range');
		    	$updAc = true;
	        } else {
	        	generalLib::mesg('Updating shade range link \'' . $shadeRec->shade_range_label . '\' for shade part num \'' . $shadeRec->shade_partnum . '\'');
				$bsRangeRec = $collection->getFirstItem();
				$updAc = false;
	        }

	        $collection = $this->objectManager->create('StarDigital\BespokeConfigurator\Model\Rangetype')->getCollection()
				->addFieldToFilter('range_type_code', $shadeRec->range_type);

			if ( $collection->getSize() == 0 ) {
				generalLib::warn('Undefined shade range type \'' . $shadeRec->range_type . '\' - skipping update for \'' . $shadeRec->shade_partnum . '\'');
				$rc = false;
				return $rc;
			} else {
				$bsRangeTypeRec = $collection->getFirstItem();
				$shadeRec->range_type_id = $bsRangeTypeRec->getEntityId();

				$shadeRangeTypesArr = explode(',', $bsRangeRec->getRangeType());

				if (!in_array($shadeRec->range_type_id, $shadeRangeTypesArr)) {
					$shadeRangeTypesArr[] = $shadeRec->range_type_id;
				}

				sort($shadeRangeTypesArr);

				$shadeRec->range_type_id = implode(',', $shadeRangeTypesArr);
			}

			//print_r($bsRangeRec->getData());

	        $updAttrs = magentoImport::processMagAttrs($bsRangeRec, $shadeRec, $this->procConfig->shaderangesLinkMagAttrs);

			//print_r($bsRangeRec->getData());
			//die();

	        if ($updAttrs) {
				try {
					generalLib::mesg('Saving shade range link');
					$bsRangeRec->save();
				}

				catch(Exception $e) {
					generalLib::error($e->getMessage());
					generalLib::error('Shade range link \'' . $shadeRec->shade_range_label . '\' not saved for shade part num \'' . $shadeRec->shade_partnum . '\'');
					$rc = false;
				}

			} else {
				generalLib::mesg('No shade range type updates to apply');
			}
		//}


		if ($updProd) {
			try {
				generalLib::mesg('Saving product sku \'' . $productSku . '\'');
				$product->save();
			}

			catch(Exception $e) {
				$rc = false;
				generalLib::error($e->getMessage());
				generalLib::error('Product sku \'' . $productSku . '\' not saved');

			}
		} else {
			generalLib::mesg('No shade ranges updates to apply');
		}

		$product = $this->objectManager->create('Magento\Catalog\Model\Product')->load( $product->getId() );

		if ($rc) {

			$rc = $this->buildShadeRangeCustomOptions($product, $shadeRec);
			//die();

			if ($rc) {
				//TEMP
				$rc = $this->chkAndUpdShadeChildProducts($productSku);
			}
		}

		//echo 'rc = ' . $rc . "\n";

		$retArr['rc'] = $rc;

		//die();
		return $retArr;
	}


	//###########################
	public function chkShadeStyleAndRangeTypes() {

		$this->procConfig->rangeTypes = array();

		$rangeTypeModel = $this->procConfig->bespConfBaseModel . 'Rangetype';

		try {
			$rangeTypeColl = $this->objectManager->create( $rangeTypeModel )
							->getCollection();

		}

		catch(Exception $e) {
			generalLib::error($e->getMessage());
		}

		foreach ($rangeTypeColl as $rangeType) {
			$this->procConfig->rangeTypes[ $rangeType->getRangeTypeCode() ] = $rangeType->getEntityId();
		}


		$this->procConfig->styleRangeTypes = array();
		$this->procConfig->shadeRangeTypes = array();
		$this->procConfig->shadeRangeStyles = array();

		$funcFeedWebService = 'Shade_Ranges';


		$url = $this->procConfig->baseWsUrl . '/' . $funcFeedWebService;

		//echo 'url = ' . $url . "\n";

		$actionTypeName = 'actiontype';

		$processFunc = 'buildShadeStyleAndRangeTypeLists';

		$parameters = 	array('filter' => array(
													array( 'Field' => $actionTypeName, 'Criteria' => '-' ),
													)
												);

		$batchSize = 10;

		try {
			$this->procConfig->wsConn->processData($url, 'ReadMultiple', $parameters, $batchSize, $funcFeedWebService, $processFunc, $actionTypeName);

		}

		catch(Exception $e){
				generalLib::error($e->getMessage());
		}

		/*
		echo "range types:\n";
		print_r($this->procConfig->rangeTypes);

		echo "style range types:\n";
		print_r($this->procConfig->styleRangeTypes);

		echo "shade range types:\n";
		print_r($this->procConfig->shadeRangeTypes);

		echo "shade range styles:\n";
		print_r($this->procConfig->shadeRangeStyles);

		die();
		*/

		$styleModel = $this->procConfig->bespConfBaseModel . 'Style';

		foreach($this->procConfig->styleRangeTypes as $styleCode => $styleRangeTypes) {

			try {
				$styleColl = $this->objectManager->create( $styleModel )
								->getCollection()
								->addFieldToFilter('style_code', $styleCode);
			}

			catch(Exception $e) {
				generalLib::error($e->getMessage());
			}

			//echo 'style size = ' . $styleColl->getSize() . "\n";

            if ( $styleColl->getSize() > 0 ) {
	        	$styleMagRec = $styleColl->getFirstItem();

	        	sort($this->procConfig->styleRangeTypes[ $styleCode ]);

	        	$styleRangeTypes = implode(',', $this->procConfig->styleRangeTypes[ $styleCode ]);

				//echo 'old style range type = ' . $styleMagRec->getRangeType() . "\n";
				//echo 'new style range type = ' . $styleRangeTypes . "\n";

	        	if ($styleMagRec->getRangeType() != $styleRangeTypes) {
	        		generalLib::mesg('Updating range type to \''. $styleRangeTypes . '\' for style code \'' . $styleCode . '\'');

	        		$styleMagRec->setRangeType( $styleRangeTypes );

	        		try {
						generalLib::mesg('Saving style code');
						$styleMagRec->save();
					}

					catch(Exception $e) {
						generalLib::error($e->getMessage());
						generalLib::error('Style \'' . $styleCode . '\' not saved');
						$rc = false;
					}
	        	}

	        } else {
	        	generalLib::warn('Cannot find style code \'' . $styleCode . '\' - unable to update range type');
	        }
		}

		$rangeModel = $this->procConfig->bespConfBaseModel . 'Range';

		foreach($this->procConfig->shadeRangeStyles as $shadeRangeLabel => $shadeRangeStyles) {

			$rangeColl = $this->objectManager->create( $rangeModel )
								->getCollection()
								->addFieldToFilter('label', $shadeRangeLabel);

			 if ( $rangeColl->getSize() > 0 ) {
			 	$rangeMagRec = $rangeColl->getFirstItem();
			 	$updRange = false;

			 	sort($this->procConfig->shadeRangeStyles[ $shadeRangeLabel ]);

	        	$shadeRangeStyles = implode(',', $this->procConfig->shadeRangeStyles[ $shadeRangeLabel ]);

				//echo 'old style type = ' . $rangeMagRec->getStyleType() . "\n";
				//echo 'new style type = ' . $shadeRangeStyles . "\n";

	        	if ($rangeMagRec->getStyleType() != $shadeRangeStyles) {
	        		generalLib::mesg('Setting range \'' . $shadeRangeLabel . '\' to style type \''. $shadeRangeStyles . '\'');

	        		$rangeMagRec->setStyleType( $shadeRangeStyles );
	        		$updRange = true;
	        	}


	        	sort($this->procConfig->shadeRangeTypes[ $shadeRangeLabel ]);

	        	$shadeRangeTypes = implode(',', $this->procConfig->shadeRangeTypes[ $shadeRangeLabel ]);

				//echo 'old range type = ' . $rangeMagRec->getRangeType() . "\n";
				//echo 'new range type = ' . $shadeRangeTypes . "\n";

	        	if ($rangeMagRec->getRangeType() != $shadeRangeTypes) {
	        		generalLib::mesg('Setting range \'' . $shadeRangeLabel . '\' to range type \''. $shadeRangeTypes . '\'');

	        		$rangeMagRec->setRangeType( $shadeRangeTypes );
	        		$updRange = true;
	        	}

	        	if ($updRange) {

	        		try {
						generalLib::mesg('Saving range ' . $shadeRangeLabel);
						$rangeMagRec->save();
					}

					catch(Exception $e) {
						generalLib::error($e->getMessage());
						generalLib::error('Range \'' . $shadeRangeLabel . '\' not saved');
						$rc = false;
					}
	        	}

			 } else {
	        	generalLib::warn('Cannot find range \'' . $shadeRangeLabel . '\' - unable to update range and style type');
	        }
		}
	}


	//###########################
	public function buildShadeStyleAndRangeTypeLists($actionType, $shadeRec) {

		$retArr = array();

		//echo "got to buildShadeStyleAndRangeTypeLists\n";
		print_r($shadeRec);

		$shadeRangeLabel = ucfirst( strtolower( $shadeRec->shade_range_name ) );

		if ( !isset( $this->procConfig->shadeRangeStyles [ $shadeRangeLabel ] ) ) {
			$this->procConfig->shadeRangeStyles[ $shadeRangeLabel ] = array();
		}

		if ( !empty($shadeRec->shade_styles) ) {
			$styleCodes = explode(',', $shadeRec->shade_styles);

			foreach ($styleCodes as $styleCode) {

				if ( !isset( $this->procConfig->styleRangeTypes [ $styleCode ] ) ) {
					$this->procConfig->styleRangeTypes[  $styleCode ] = array();
				}

				if (!in_array($this->procConfig->rangeTypes[ $shadeRec->range_type ], $this->procConfig->styleRangeTypes[  $styleCode ]) ) {
					$this->procConfig->styleRangeTypes[  $styleCode ][] = $this->procConfig->rangeTypes[ $shadeRec->range_type ];
				}

				if (!in_array($styleCode, $this->procConfig->shadeRangeStyles[  $shadeRangeLabel ]) ) {
					$this->procConfig->shadeRangeStyles[  $shadeRangeLabel ][] = $styleCode;
				}

			}
		}


		if ( !isset( $this->procConfig->shadeRangeTypes[ $shadeRangeLabel ] ) ) {
			$this->procConfig->shadeRangeTypes[ $shadeRangeLabel ] = array();
		}

		if (!in_array($this->procConfig->rangeTypes[ $shadeRec->range_type ], $this->procConfig->shadeRangeTypes[  $shadeRangeLabel ]) ) {
			$this->procConfig->shadeRangeTypes[  $shadeRangeLabel ][] = $this->procConfig->rangeTypes[ $shadeRec->range_type ];
		}


		$rc = false;

		$retArr['rc'] = $rc;

		return $retArr;
	}


	//###########################
	public function chkAndUpdShadeChildProducts($shadeParentSku) {
		$rc = true;

		//FIND SKUS BEGINNING WITH THE SAME SHADE SKU - THESE MAY BE CHILD SHADE PRODUCTS, WHICH MAY NEED UPDATING


		$shadeChildCollection = $this->objectManager->create('Magento\Catalog\Model\Product')
										->getCollection()
										->addAttributeToFilter('type_id', 'simple')
										->addAttributeToFilter('shade_range_basecode', array('eq' => $shadeParentSku));

		foreach($shadeChildCollection as $shadeChild) {
			$shadeChildSku = $shadeChild->getSku();

			//EXCLUDE OTHER PARENT SHADE RANGE PRODUCTS - NO PARENT SHADE PRODUCTS SHOULD HAVE THE BASE CODE SET, BUT THIS IS A BACKUP CHECK
			if ($shadeChildSku != $shadeParentSku) {

				$shadeChildProduct = $this->objectManager->create('Magento\Catalog\Model\Product')->load( $shadeChild->getId() );

				if ($shadeChildProduct) {
					generalLib::mesg('');
					generalLib::mesg('====');
					generalLib::mesg('processing shade child \'' . $shadeChildSku . '\'');
					$rc = $this->checkAndUpdateShadeChild($shadeChildProduct);
				} else {
					generalLib::Warn('Cannot find shade child product for sku \'' . $shadeChildSku . '\'');
				}
			} else {
				generalLib::Warn('Parent shade product for sku \'' . $shadeParentSku . '\' has the base code set');
			}
		}

		return $rc;
	}


	//###########################
	public function processShade_Liner_PricesWebService($actionType, $shadeLinerPriceRec) {
		$rc = true;

		$retArr = array();

		/*
		if ($shadeLinerPriceRec->shade_partnum != 'ABY01') {
			return false;
		}
		*/

		generalLib::mesg('');
		generalLib::mesg('====');

		//TEMP
		/*
		if ( !isset($shadeLinerPriceRec->actiontype)) {
			$shadeLinerPriceRec->actiontype = 'U';
		}

		if ( $shadeLinerPriceRec->actiontype == 'd') {
			$rc = false;

			$retArr['rc'] = $rc;

			return $retArr;
		}
		*/


		print_r($shadeLinerPriceRec);

		if ( !isset($shadeLinerPriceRec->liner_code) ) {
			generalLib::warn('Undefined liner code - skipping');

			$rc = false;

			$retArr['rc'] = $rc;

			return $retArr;

		}

		$product = $this->getProductForPartnum($shadeLinerPriceRec->shade_partnum, true);

		if (!$product) {
			$rc = false;

			$retArr['rc'] = $rc;

			return $retArr;
		}

		$retArr = $this->buildShadeCustomOption($actionType, $product, 'Liner Colour',  $this->procConfig->liner_list[ $shadeLinerPriceRec->liner_code ], $shadeLinerPriceRec->liner_price);

		$rc = $retArr['rc'];

		//if ($rc && $retArr['updProd']) {

			if (!isset( $this->procConfig->shadePartNumsToChk[ $shadeLinerPriceRec->shade_partnum ]) ) {
				$this->procConfig->shadePartNumsToChk[ $shadeLinerPriceRec->shade_partnum ] = $shadeLinerPriceRec->shade_partnum;
			}

		//}

		$retArr['rc'] = $rc;

		return $retArr;
	}


	//###########################
	public function buildShadeCustomOption($actionType, &$product, $custOptTitle, $custOptValTitle, $custOptValPrice) {
		$rc = true;
		$retArr = array();

		$customOptionArr = array();
		$customOptionArr['title'] = $custOptTitle;
		$customOptionArr['type'] = 'radio';
		$customOptionArr['is_require'] = '0';
		print_r($customOptionArr);

		$customOptionValueArr = array();
		$customOptionValueArr['title'] = $custOptValTitle;
		$customOptionValueArr['price_type'] = 'fixed';
		$customOptionValueArr['price'] = $custOptValPrice;
		$customOptionValueArr['sku'] = '';

		print_r($customOptionValueArr);

		$updProd = MagentoCustomOpts::processCustomOption($actionType, $product, 'title', $customOptionArr, 'title', $customOptionValueArr);

		if ($updProd) {
			$rc = MagentoCustomOpts::saveCustomOptProd($product, $product->getSku(), true);
		}

		unset($customOptionArr);
		unset($customOptionValueArr);

		$retArr['rc'] = $rc;
		$retArr['updProd'] = $updProd;

		//die();
		return $retArr;
	}


	//###########################
	public function buildShadeRangeCustomOptions($product, $shadeRec) {
		$rc = true;
		$productSku = $product->getSku();

		//echo "shade rec:\n";
		//print_r($shadeRec);

		//CREATE OR UPDATE 'Partnum' CUSTOM OPTION
		$actionType = 'U';

		$customOptionArr = array();
		$customOptionArr['title'] = 'Partnum';
		$customOptionArr['type'] = 'field';
		$customOptionArr['price_type'] = 'fixed';
		$customOptionArr['is_require'] = '0';

		$customOptionValueArr = array();

		$updCustomOpts = MagentoCustomOpts::processCustomOption($actionType, $product, 'title', $customOptionArr, 'price_type', $customOptionValueArr);


		if ($updCustomOpts) {
			$rc = MagentoCustomOpts::saveCustomOptProd($product, $productSku, true);
		}


		//CREATE OR UPDATE 'Notes Field' CUSTOM OPTION
		$customOptionArr = array();
		$customOptionArr['title'] = 'Note';
		$customOptionArr['type'] = 'field';
		$customOptionArr['price_type'] = 'fixed';
		$customOptionArr['is_require'] = '0';

		$customOptionValueArr = array();

		$updCustomOpts = MagentoCustomOpts::processCustomOption($actionType, $product, 'title', $customOptionArr, 'price_type', $customOptionValueArr);


		if ($updCustomOpts) {
			$rc = MagentoCustomOpts::saveCustomOptProd($product, $productSku, true);
		}


		//CREATE OR UPDATE 'Tier x' CUSTOM OPTION(S)
		$oldTiers = array();

		foreach($this->procConfig->shaderangesTierCustomAttrs as $shaderangesTierCustomAttr => $shaderangesLinedCustomAttrType) {
			$oldTier = $product->getData($shaderangesTierCustomAttr);

			if ($oldTier != '') {
				$oldTiers[ $shaderangesTierCustomAttr ] = $oldTier;
			} else {
				$oldTiers[ $shaderangesTierCustomAttr ] = '0';
			}
		}

		//print_r($oldTiers);

		$actionType = 'U';

		$customOptionArr = array();
		$customOptionArr['title'] = 'Tier 1';
		$customOptionArr['type'] = 'field';
		$customOptionArr['price_type'] = 'fixed';
		$customOptionArr['is_require'] = '0';

		$customOptionValueArr = array();

		//print_r($customOptionArr);
		//print_r($customOptionValueArr);
		//die();

		//THERE WILL ALWAYS BE ONE TIER
		$updCustomOpts = MagentoCustomOpts::processCustomOption($actionType, $product, 'title', $customOptionArr, 'price_type', $customOptionValueArr);

		if ($updCustomOpts) {
			$rc = MagentoCustomOpts::saveCustomOptProd($product, $productSku, true);
		}


		//print_r($shadeRec);

		//echo "1\n";
		//echo 'twotone = ' . $product->getShadeTwotone() . "\n";
		//echo 'threetier = ' . $product->getShadeThreetier() . "\n";
		//die();

		if (magentoImport::processCustomAttrs($this, $product, $shadeRec, $this->procConfig->shaderangesTierCustomAttrs)) {

			$prodShadeTwoTone = $product->getShadeTwotone();

			$prodShadeThreeTier = $product->getShadeThreetier();

			//die();
			//IF SET TO THREE TIER - INCLUDE THE 2ND TIER TOO
			if ($shadeRec->shade_threetier == '1') {
				$shadeRec->shade_twotone = '1';
			}

			//IF THREE TIER IS UNSET, AND TWO TIER IS NOT SET - MAKE SURE THE 2ND TIER IS REMOVED
			if ($oldTiers[ 'shade_threetier' ] == '1' && $shadeRec->shade_threetier == '0' && $shadeRec->shade_twotone == '0') {
				$oldTiers[ 'shade_twotone' ] = '1';
				$shadeRec->shade_twotone = '0';
			}

			$tierIdx = 1;

			foreach($this->procConfig->shaderangesTierCustomAttrs as $shaderangesTierCustomAttr => $shaderangesLinedCustomAttrType) {
				$actionType = 'U';
				$tierIdx++;
				$oldTier = $oldTiers[ $shaderangesTierCustomAttr ];
				//echo 'oldTier = ' . $oldTier . "\n";

				if ($shadeRec->$shaderangesTierCustomAttr != '1' && $shadeRec->$shaderangesTierCustomAttr != '0') {
					$shadeRec->$shaderangesTierCustomAttr = '0';
				}

				//echo 'shade rec for ' . $shaderangesTierCustomAttr . ' = ' . $shadeRec->$shaderangesTierCustomAttr . "\n";

				if ($oldTier == '1' && $shadeRec->$shaderangesTierCustomAttr == '0') {
					$actionType = 'D';
				} else {
					if ($oldTier == '0' && $shadeRec->$shaderangesTierCustomAttr == '0') {
						continue;
					}
				}

				$customOptionArr['title'] = 'Tier ' . $tierIdx;

				//echo 'actionType = ' . $actionType . "\n";
				//echo 'title = ' . $customOptionArr['title'] . "\n";
				//print_r($customOptionArr);
				//die();

				$updCustomOpts = MagentoCustomOpts::processCustomOption($actionType, $product, 'title', $customOptionArr, 'sku', $customOptionValueArr);

				if ($updCustomOpts) {

					$rc = MagentoCustomOpts::saveCustomOptProd($product, $productSku, true);
				}
			}

			if ($rc) {

				$prodsUpd = array( $product->getId() ) ;

				$attrsUpd = array('shade_twotone' => $prodShadeTwoTone, 'shade_threetier' => $prodShadeThreeTier);

				try {
						generalLib::mesg('Saving Product sku \'' . $productSku . '\' shade two tone & three tier');

						$this->objectManager->create('\Magento\Catalog\Model\ResourceModel\Product\Action')->updateAttributes($prodsUpd, $attrsUpd, '0');
				}

				catch(Exception $e) {
					$rc = false;
					generalLib::error($e->getMessage());
					generalLib::error('Product sku \'' . $productSku . '\' shade two tone & three tier not saved');
				}

			}

		}

		//echo "2\n";
		//echo 'twotone = ' . $product->getShadeTwotone() . "\n";
		//echo 'threetier = ' . $product->getShadeThreetier() . "\n";
		//die();


		//echo "3\n";
		//echo 'twotone = ' . $product->getShadeTwotone() . "\n";
		//echo 'threetier = ' . $product->getShadeThreetier() . "\n";

		//if ($productSku == 'CYL0801') {
			//die();
		//}

		return $rc;
	}


	//###########################
	public function processStockWebService($actionType, $stockRec) {

		$retArr = array();

		$rc = true;

		generalLib::mesg('');
		generalLib::mesg('====');

		if (!isset($stockRec->shade_range_basecode) ) {
			$stockRec->shade_range_basecode = '';
		}

		print_r($stockRec);

		//$stockRec->partnum = 'JDL_STARDIGITAL2';
		$product = $this->getProductForPartnum($stockRec->partnum, false);

		if ($product) {
			$productId = $product->getId();
			$productSku = $product->getSku();
		} else {
			$productId = '';
			$productSku = $stockRec->partnum;
		}

		if ($actionType == 'D') {
			$rc = magentoProd::processProdDeletion($product, $stockRec->partnum);

			$retArr['rc'] = $rc;

			return $retArr;
		}

		$updProd = false;

		if (!$product) {
			generalLib::mesg('Creating product for sku : \'' . $productSku . '\'');

			$product = $this->objectManager->create('Magento\Catalog\Model\Product');

			$product->setAttributeSetId(4);
			$product->setVisibility(Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH);
			$product->setTaxClassId(0);
			$product->setStatus(\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED);
			$product->setTypeId(\Magento\Catalog\Model\Product\Type::TYPE_SIMPLE);
			$product->setWeight(0);
			$product->setCreatedAt(strtotime('now'));

			//THESE CAN BE OVERRIDEN BY PRODUCT ATTRIBUTE TABLE

			if ( !isset( $stockRec->partdesc ) ) {
				$stockRec->partdesc = 'TBA';
			}

			$product->setDescription($stockRec->partdesc);
			$product->setShortDescription($stockRec->partdesc);

			$stockRec->metaDescription = str_replace('<br />', ' ', $stockRec->partdesc);
			$stockRec->metaDescription = htmlspecialchars_decode( $stockRec->metaDescription );
			$stockRec->metaDescription = trim($stockRec->metaDescription);
			$product->setMetaDescription( $stockRec->metaDescription );
		} else {
			$productId = $product->getEntityId();
			$productSku = $product->getSku();

			generalLib::mesg('Updating product with SKU: ' . $productSku . ' product ID ' . $productId);
		}

		//TEMP
		/*
		if (magentoImport::processMagAttrs($product, $stockRec, $this->procConfig->stockMagDescAttr)) {
			$updProd = true;
		}

		if (magentoImport::processMagAttrs($product, $stockRec, $this->procConfig->stockMagShortDescAttr)) {
			$updProd = true;
		}
		*/

		if (magentoImport::processMagAttrs($product, $stockRec, $this->procConfig->stockMagSkuAttr)) {
			$skuUpdated = true;
			$updProd = true;
		} else {
			$skuUpdated = false;
		}


		if (magentoImport::processMagAttrs($product, $stockRec, $this->procConfig->stockMagNameAttr) || $skuUpdated) {
			$oldUrlKey = $product->getUrlKey();
			$newUrlKey = $product->getName() . '-' . $product->getSku();

			if ($oldUrlKey != $newUrlKey) {
				$product->setUrlKey( $newUrlKey );
				generalLib::mesg('Re-creating URL key');

				$product->setData('url_key_create_redirect', false);
				generalLib::mesg('URL create redirect is disabled');
			}

			$updProd = true;
		}

		if ( isset($stockRec->partdesc) ) {
			$stockRec->metatitle = htmlspecialchars_decode( $stockRec->partdesc );
			$stockRec->metatitle = trim($stockRec->metatitle);
		}

		if (magentoImport::processMagAttrs($product, $stockRec, $this->procConfig->stockMagMetaTitleAttr)) {
			$updProd = true;
		}

		/*
		if ($product->getMetaDescription() == '') {
			$stockRec->metaDescription = str_replace('<br />', ' ', $stockRec->partdesc);
			$stockRec->metaDescription = htmlspecialchars_decode( $stockRec->metaDescription );
			$stockRec->metaDescription = trim($stockRec->metaDescription);
			$product->setMetaDescription( $stockRec->metaDescription );
			generalLib::mesg('Setting meta_description to \'' . $stockRec->metaDescription . '\'');
			$updProd = true;
		}
		*/

		$updShadeRangeProduct = false;

		if (magentoImport::processCustomAttrs($this, $product, $stockRec, $this->procConfig->stockCustomShadeRangeAttr)) {

			$updShadeRangeProduct = true;

			/*
			if ($product->getShadeRangeProduct() == '1') {

				echo "setting stock data\n";
				//SHADE RANGE (BESPOKE) PRODUCTS ARE NOT STOCK CONTROLLED
				$product->setStockData(array(
						'manage_stock' => 1,
						'use_config_manage_stock' => 0,
						'is_in_stock' => 1,
						'qty' => 10
				));

				$updProd = true;

			} /* else {

				//ALL OTHER PRODUCTS ARE STOCK CONTROLLED
				$product->setStockData(array(
						'manage_stock' => 1,
						'use_config_backorders' => 1,
						'use_config_manage_stock' => 1,
						'is_in_stock' => 0
						'qty' => 0
				));

			}
			*/

		}


		if (magentoImport::processCustomAttrs($this, $product, $stockRec, $this->procConfig->stockCustomVatAttr)) {

			$magVatCode = 'UK-GBP-';

			if ($stockRec->vatcode != '') {

				$magVatCode .= $stockRec->vatcode;

				$gotTaxClass = false;

				foreach ($this->procConfig->productTaxClasses as $productTaxClass) {

					//print_r($productTaxClass);

					if ($productTaxClass['label'] == $magVatCode) {
						$product->setTaxClassId( $productTaxClass['value'] );
						$gotTaxClass = true;
						break;
					}

				}

				if (!$gotTaxClass) {
					$product->setTaxClassId(0);		//none
				}

			} else {
				$product->setTaxClassId(0);		//none
			}

			 $updProd = true;
		}


		$updAttrs = magentoImport::processMagAttrs($product, $stockRec, $this->procConfig->stockMagSkuAttr);

		if ($updAttrs && (!$updProd)) {
			$updProd = true;
		}

		$updAttrs = magentoImport::processCustomAttrs($this, $product, $stockRec, $this->procConfig->stockCustomAttrs);

		if ($updAttrs && (!$updProd)) {
			$updProd = true;
		}


		if (!$product->getWebLampProduct()) {
			$updAttrs = magentoImport::processMagAttrs($product, $stockRec, $this->procConfig->stockPriceMagAttr);

			if ($updAttrs && (!$updProd)) {
				$updProd = true;
			}
		}


		if ($product->getWebLampProduct() != '1') {

			$updAttrs = magentoImport::processMagAttrs($product, $stockRec, $this->procConfig->stockMagPackSizeAttr);

			if ( $updAttrs && ( !$updProd) ) {
				$updProd = true;
			}

		}

		$updAttrs = magentoImport::processMagAttrs($product, $stockRec, $this->procConfig->stockXrrpMagAttrs);

		if ( $updAttrs && ( !$updProd) ) {
			$updProd = true;
		}

		if ($stockRec->rrp == '0') {

			$rrp = $product->getPrice() * 2;

			$product->setRrp($rrp);

			generalLib::mesg('Setting RRP default to \'' . $rrp . '\'');
			$updProd = true;
		}

		if ($updProd) {
			try {
				generalLib::mesg('Saving product sku \'' . $productSku . '\'');
				$product->save();
			}

			catch(Exception $e) {
				$rc = false;
				generalLib::error($e->getMessage());
				generalLib::error('Product sku \'' . $productSku . '\' not saved');
			}

		} else {
			generalLib::mesg('No product updates to apply');
		}


		//RELOAD THE PRODUCT - VERY IMPORTANT, OTHERWISE A NEW CUSTOM OPTION WILL NOT BE RECOGNIZED FOR ADDITIONAL VALUES, AND WILL CREATE A NEW CUSTOM OPTION
		//EXCLUDE PARENT SHADE PRODUCTS FROM SHADE PARENT LOOKUP AND CUSTOMER OPTION COPYING

		if ($rc) {
			$shadeBaseCode = $product->getShadeRangeBasecode();

			//if ( $product->getShadeRangeProduct() == '0' && ( !empty( $product->getShadeRangeBasecode()) ) ) {
			if ( $product->getShadeRangeProduct() == '0' && ( !empty( $shadeBaseCode ) ) ) {

				if ($productId == '') {
					$productId = $product->getEntityId();
				}

 				$product = $this->objectManager->create('Magento\Catalog\Model\Product')->load( $productId );

				if ($product) {
					$rc = $this->checkAndUpdateShadeChild($product);
				}
			}

		}

		//TEMP
		//$updShadeRangeProduct = true;

		if ( $updShadeRangeProduct ) {
			$stockLevelRec = new stdClass();
			$stockLevelRec->partnum = $stockRec->partnum;
			$stockLevelRec->stquant = 1;	//HAVE TO PUT 1 TO GET THE CUSTOM OPTIONS TO WORK ON THE CONFIGURATOR

			if ($product->getShadeRangeProduct() == '1') {
				$stockLevelRec->manageStock = '0';
			} else {
				$stockLevelRec->manageStock = '1';
			}

			$rc = $this->processStock_LevelWebService('U', $stockLevelRec);
		}

		$last2SkuChars = substr($productSku, -2);

		//echo 'last2SkuChars = ' . $last2SkuChars . "\n";

		if ( $last2SkuChars == '/P' || $last2SkuChars == '-P' ) {

			$images = $product->getMediaGalleryImages();

			//echo 'count images = ' . count( $images ) . "\n";;

			if ( count( $images ) == 0 ) {
			//if ( count( $images ) > 0 ) {

				$nonPromoSku = substr($productSku, 0, -2);

				$nonPromoProduct = $this->getProductForPartnum($nonPromoSku, false);

				$promoImgSku = str_replace('/', '-', $productSku);

				if ($nonPromoProduct) {

					$nonPromoSkuImages = $nonPromoProduct->getMediaGalleryImages();

					magentoImgImport::setImageConfig($this->procConfig->imageBaseWatchedDir . '/' . $this->procConfig->mainImagesDir, $this->procConfig->mediaFolderImgImport, true);

					foreach ($nonPromoSkuImages as $nonPromoSkuImage) {

						print_r($nonPromoSkuImage);

						$imgFile = $nonPromoSkuImage->getFile();

						$baseImage = false;

						if ($imgFile == $nonPromoProduct->getImage()) {
							$baseImage = true;
						    //then it's a base image
						}

						/*
						//KEPT FOR REFERENCE
						if ($imgFile == $nonPromoProduct->getSmallImage()){
						    //then it's small image
						    echo "small image\n";
						}

						if ($imgFile == $nonPromoProduct->getThumbnail()){
						    //then it's thumbnail
						    echo "thumbnail\n";
						}
						*/

						$nonPromoSkuImagePathParts = pathinfo( $nonPromoSkuImage['file'] );

						$origImgFilePath = $this->procConfig->mediaFolder . '/catalog/product' . $nonPromoSkuImage['file'];

						if ( !is_numeric($nonPromoSkuImage['position']) ) {
							$nonPromoSkuImage['position'] = '0';
						}

						$promoImgFile = $nonPromoSkuImagePathParts['filename'];
						$promoImgFile = str_replace( $nonPromoSku, $promoImgSku, $promoImgFile);
						$promoImgFile .= '.' . $nonPromoSkuImagePathParts['extension'];

						$destImgFilePath = $this->procConfig->imageBaseWatchedDir . '/' . $this->procConfig->mainImagesDir . '/' . $promoImgFile;

						//echo 'origImgFilePath = ' . $origImgFilePath . "\n";

						//echo 'destImgFilePath = ' . $destImgFilePath . "\n";

						//die();

						if ( copy($origImgFilePath, $destImgFilePath) ) {
							$imgObjs = array();

							$imgObj = new stdClass();
							$imgObj->imgName = $promoImgFile;
							$imgObj->imgNameCaseSensitive = false;
							$imgObj->excludeImg = false;

							$imgObj->position = $nonPromoSkuImage['position'];

							if ( $baseImage ) {
								$imgObj->imgType = array('thumbnail', 'small_image', 'image');
							} else {
								$imgObj->imgType = array();
							}

							$imgObj->imgAllowUpdate = true;

							$imgObjs[] = $imgObj;

							print_r($imgObjs);

							$fileProcessed = magentoImgImport::processProductImages($product, $imgObjs);

							if ($fileProcessed) {

								try {
									generalLib::mesg('Saving product id \'' . $productId . '\' sku \'' . $productSku . '\'');
									$product->save();
									$fileProcessed = true;
								}

								catch(Exception $e) {
									generalLib::error($e->getMessage());
									generalLib::error('Product id \'' . $productId . '\' sku \'' . $productSku . '\' not saved');
									$fileProcessed = false;
								}

								if ( $fileProcessed ) {

									if ( rename($destImgFilePath, $this->procConfig->imageBaseWatchedDir . '/' . $this->procConfig->mainImagesDir . '/processed/' . $promoImgFile) ) {
										generalLib::mesg('Moved \'' . $promoImgFile . ' to the processed directory');
									} else {
										generalLib::error('Unable to move \'' . $promoImgFile . '\' into the processed directory');
									}

								}

							}

						} else {

							generalLib::error('Unable to copy image \'' . $origImgFilePath . '\' to \'' . $destImgFilePath . '\'');

						}
					}

					//die();
		   		}
			}
		}

		//die();

		$retArr['rc'] = $rc;

		return $retArr;
	}


	//###########################
	public function processStock_LevelWebService($actionType, $stockLevelRec) {

		$retArr = array();

		$rc = true;
		$updStock = false;

		generalLib::mesg('');
		generalLib::mesg('====');

		print_r($stockLevelRec);

		if ( isset($stockLevelRec->partnum) ) {

			$product = $this->getProductForPartnum($stockLevelRec->partnum, true);

		} else {

			generalLib::warn('No part number defined for stock level record nav key \'' . $stockLevelRec->Key . '\'');
			$product = false;

		}

		if (!$product) {
			$rc = false;

			$retArr['rc'] = $rc;

			return $retArr;
		}

		$productSku = $product->getSku();

		$updProd = false;

		if ( !isset($stockLevelRec->stavailability) ) {
			$stockLevelRec->stavailability = '';
		}

		$updAttrs = magentoImport::processMagAttrs($product, $stockLevelRec, $this->procConfig->stockLevelMagAttrs);

		if ($updAttrs && (!$updProd)) {
			$updProd = true;
		}

		if ($updProd) {
			try {
				generalLib::mesg('Saving product sku \'' . $productSku . '\'');
				$product->save();
			}

			catch(Exception $e) {
				$rc = false;
				generalLib::error($e->getMessage());
				generalLib::error('Product sku \'' . $productSku . '\' not saved');
			}

		} else {
			generalLib::mesg('No product updates to apply');
		}


		$stockItem = $this->objectManager->create('Magento\CatalogInventory\Api\StockRegistryInterface')->getStockItem( $product->getId() );
		//print_r($stockItem->getData());
		//die();

		if ( isset( $stockLevelRec->manageStock ) ) {

			if ($stockItem->getUseConfigManageStock() == '1') {

				if ($stockLevelRec->manageStock == '0') {
					generalLib::mesg('Setting to not be stock managed');
					$stockItem->setData('manage_stock', '0');
					$stockItem->setData('use_config_manage_stock', '0');
					$stockItem->setData('is_in_stock', '1');
					$stockItem->setData('qty', 0);
					$updStock = true;

				}

			} else {

				if ($stockLevelRec->manageStock == '1') {
					generalLib::mesg('Setting to be stock managed');
					$stockItem->setData('manage_stock', '1');
					$stockItem->setData('use_config_backorders', '1');
					$stockItem->setData('use_config_manage_stock', '1');
					$stockItem->setData('is_in_stock', '0');
					$stockItem->setData('qty', 0);
					$updStock = true;

				}

			}
		}

		/*
		if ($stockItem->getUseConfigManageStock() == '0') {
			generalLib::mesg('This product is not stock controlled - skipping stock update');
			return $rc;
		}
		*/

		$oldQty = $stockItem->getQty();

		if ( !is_numeric($oldQty) ) {
			$oldQty = 0;
			$stockItem->setData('qty', $oldQty);
			$updStock = true;
		}

		$oldQty = (float)$stockItem->getQty();

		$newQty = (float)$stockLevelRec->stquant;

		//echo 'oldqty = ' . $oldQty . "\n";
		//echo 'newqty = ' . $newQty . "\n";

		if ($oldQty != $newQty) {
			$stockItem->setData('qty', $newQty);
			//$stockItem->setQty($newQty);
			generalLib::mesg('Stock level updated to \'' . $newQty .'\'');
			$updStock = true;
		}

		if ($stockItem->getUseConfigManageStock() == '1') {
			//NOTE: ITEMS ARE SHOWN IN STOCK EVEN WHEN THERE IS NO QTY, AS ALL ITEMS CAN BE BACK ORDERED BY DEFAULT (AS DEFINED IN THE MAGENTO CONFIGURATION)

			//print_r($stockItem->getData());

			$isInStock = $stockItem->getData('is_in_stock');
			//echo 'isInStock = ' . $isInStock . "\n";

			//SET THE PRODUCT TO BE OUT OF STOCK IF THE STOCK LEVEL <= 0
			$stockLevel = (float)$stockItem->getQty();
			//echo 'stockLevel = ' . $stockLevel . "\n";

			$backOrders = $stockItem->getData('backorders');
			//echo 'backOrders = ' . $backOrders . "\n";

			$useConfigBackorders = $stockItem->getData('use_config_backorders');
			//echo 'useconfigBackOrders = ' . $useConfigBackorders . "\n";

			$configBackorders = $this->objectManager->create('Magento\Framework\App\Config\ScopeConfigInterface')->getValue('cataloginventory/item_options/backorders', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

			//ALL PRODUCTS TO ALLOW BACK ORDERING
			if ($backOrders == '0' && $useConfigBackorders == '1' && $configBackorders == '1') {
				$backOrders = '1';
				$stockItem->setData('backorders', $backOrders);
				generalLib::mesg('Setting product to have backorders');
				$updStock = true;
			}

			if ($stockLevel <= 0 && $backOrders == '0' && $isInStock == '1') {
				$stockItem->setData('is_in_stock', '0');
				generalLib::mesg('Setting product to out of stock');
				$updStock = true;
			} else {

				if ( ( $stockLevel > 0 || ( $stockLevel <= 0 && $backOrders == '1' ) ) && $isInStock == '0') {
					$stockItem->setData('is_in_stock', 1);
					generalLib::mesg('Setting product to in stock');
					$updStock = true;
				}

			}

		}

		if ($updStock) {

			try {
				generalLib::mesg('saving stock item for sku \'' . $productSku . '\'');
				$stockItem->save();

				$this->procConfig->numStockLevelsUpd++;
			}

			catch(Exception $e) {
				generalLib::error($e->getMessage());
				generalLib::error('stock item for sku \'' . $productSku . '\' not saved');
				$rc = false;
			}

			/*
			if ($rc) {
				$aitocStockItems = Mage::getModel('aitquantitymanager/stock_item')
					->getCollection()
					->addFieldToFilter('product_id', $product->getId() )
					->addFieldToFilter('website_id', '2');


				foreach($aitocStockItems as $aitocStockItem) {
					//print_r($aitocStockItem->getData());

					$aitocOldQty = (float)$aitocStockItem->getQty();

					if ( $aitocOldQty != $newQty ) {
						generalLib::mesg('Aitoc Stock level updated to \'' . $newQty .'\'');
						$aitocStockItem->setData('qty', $newQty);

						try {
							generalLib::mesg('saving aitoc stock item for sku \'' . $productSku . '\' and website id \'2\'');
							$aitocStockItem->save();
						}

						catch(Exception $e) {
							generalLib::error($e->getMessage());
							generalLib::error('aitoc stock item for sku \'' . $productSku . '\' and website id \'2\' not saved');
							$rc = false;
						}

					}
				}
			}
			*/

		} else {
			generalLib::mesg('No stock updates to apply');
		}


		//TEMP - TO CORRECT FOR STOCK LEVELS UPDATED BEFORE THE AITOC FIX
		/*
		$aitocStockItems = Mage::getModel('aitquantitymanager/stock_item')
			->getCollection()
			->addFieldToFilter('product_id', $product->getId() )
			->addFieldToFilter('website_id', '2');


		foreach($aitocStockItems as $aitocStockItem) {
			print_r($aitocStockItem->getData());

			$aitocOldQty = (float)$aitocStockItem->getQty();

			if ( $aitocOldQty != $newQty ) {
				generalLib::mesg('Aitoc Stock level updated to \'' . $newQty .'\'');
				$aitocStockItem->setData('qty', $newQty);

				try {
					generalLib::mesg('saving aitoc stock item correction for sku \'' . $productSku . '\' and website id \'2\'');
					$aitocStockItem->save();
				}

				catch(Exception $e) {
					generalLib::error($e->getMessage());
					generalLib::error('aitoc stock item correction for sku \'' . $productSku . '\' and website id \'2\' not saved');
					$rc = false;
				}

			}
		}
		*/

		//die();
		$retArr['rc'] = $rc;

		return $retArr;
	}



	//###########################
	public function processStocklevelcheckTable($actionType, $stockLevelRec) {
		$rc = true;
		$updStock = false;

		generalLib::mesg('');
		generalLib::mesg('====');

		print_r($stockLevelRec);

		$product = $this->getProductForPartnum($stockLevelRec->partnum, true);

		if (!$product) {
			$rc = false;
			return $rc;
		}

		$productSku = $product->getSku();

		$stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product);
		//print_r($stockItem->getData());

		if ($stockItem->getUseConfigManageStock() == '0') {
			generalLib::mesg('This product is not stock controlled - skipping stock update');
			return $rc;
		}

		$oldQty = (float)$stockItem->getQty();

		$newQty = (float)$stockLevelRec->stquant;

		//echo 'oldqty = ' . $oldQty . "\n";
		//echo 'newqty = ' . $newQty . "\n";

		if ($oldQty != $newQty) {
			//$stockItem->setQty($newQty);
			generalLib::mesg('Stock level to be updated to \'' . $newQty .'\'');
			$updStock = true;
		}


		//NOTE: ITEMS ARE SHOWN IN STOCK EVEN WHEN THERE IS NO QTY, AS ALL ITEMS CAN BE BACK ORDERED BY DEFAULT (AS DEFINED IN THE MAGENTO CONFIGURATION)

		//print_r($stockItem->getData());

		$isInStock = $stockItem->getData('is_in_stock');
		//echo 'isInStock = ' . $isInStock . "\n";

		//SET THE PRODUCT TO BE OUT OF STOCK IF THE STOCK LEVEL <= 0
		$stockLevel = (float)$stockItem->getQty();
		//echo 'stockLevel = ' . $stockLevel . "\n";

		$backOrders = $stockItem->getData('backorders');
		//echo 'backOrders = ' . $backOrders . "\n";

		$useConfigBackorders = $stockItem->getData('use_config_backorders');
		//echo 'useconfigBackOrders = ' . $useConfigBackorders . "\n";

		$configBackorders = Mage::getStoreConfig('cataloginventory/item_options/backorders');
		//echo 'configBackOrders = ' . $configBackorders . "\n";

		//ALL PRODUCTS TO ALLOW BACK ORDERING
		if ($backOrders == '0' && $useConfigBackorders == '1' && $configBackorders == '1') {
			$backOrders = '1';
			$stockItem->setData('backorders', $backOrders);
			generalLib::mesg('Product to be set to have backorders');
			$updStock = true;
		}

		if ($stockLevel <= 0 && $backOrders == '0' && $isInStock == '1') {
			$stockItem->setData('is_in_stock', '0');
			generalLib::mesg('Product to be set to out of stock');
			$updStock = true;
		} else {

			if ( ( $stockLevel > 0 || ( $stockLevel <= 0 && $backOrders == '1' ) ) && $isInStock == '0') {
				$stockItem->setData('is_in_stock', 1);
				generalLib::mesg('Product to be set to in stock');
				$updStock = true;
			}

		}

		if ($updStock) {
			generalLib::mesg('Stock item to be saved for sku \'' . $productSku . '\'');
		} else {
			generalLib::mesg('No stock updates to apply');
		}

		return $rc;
	}

	//###########################
	public function processStore_DataWebService($actionType, $storeDataRec) {
		$rc = true;

		$retArr = array();

		generalLib::mesg('');
		generalLib::mesg('====');

		print_r($storeDataRec);

		//AS 'US' RECORDS ARE GOING TO BE DEPRECATED - COULD REMOVE datatype FROM THE WEB SERVICE
		if ($storeDataRec->datatype != 'PR') {

			$retArr['rc'] = $rc;

			return $retArr;
		}

		$product = $this->getProductForPartnum($storeDataRec->dataid, true);

		if (!$product) {
			$rc = false;

			$retArr['rc'] = $rc;

			return $retArr;
		}

		$productId = $product->getId();

		$productSku = $product->getSku();

		if ( isset( $storeDataRec->storecode ) && isset( $this->procConfig->getStoreCodeDets[ $storeDataRec->storecode ] ) ) {

			$magStoreId = $this->procConfig->getStoreCodeDets[ $storeDataRec->storecode ]->storeId;

		} else {

			if ( !isset( $storeDataRec->storecode ) ) {
                    $storeDataRec->storecode = 'NULL';
            }

			generalLib::warn('Undefined store code \'' . $storeDataRec->storecode . '\' for sku \'' . $storeDataRec->dataid . '\' - skipping' );

			$rc = false;

			$retArr['rc'] = $rc;

			return $retArr;

		}

		//$magStoreId = $this->procConfig->darToMagStoreMappings[ $storeDataRec->storeid ];

		$prodStores = array($magStoreId => $actionType);

		$oldWebSiteIds = $product->getWebsiteIds();

		$websiteId = $this->procConfig->storeDets[ $magStoreId ]->websiteId;

		$updWebSiteIds = $this->setProdWebsites($product, $prodStores);

		$newWebSiteIds = $product->getWebsiteIds();

		//$updWebSiteIds = true;

		if ($updWebSiteIds) {
			generalLib::mesg('Saving product sku \'' . $productSku . '\'');

			try {
				$product->save();
			}

			catch(Exception $e) {
				$rc = false;
				generalLib::error($e->getMessage());
				generalLib::error('Product sku \'' . $productSku . '\' not saved');
			}
		}

		/*
		if ($rc ) {
			$product = $this->getProductForPartnum($storeDataRec->dataid, true);

			if ($product) {

				$shadeBaseCode = $product->getShadeRangeBasecode();

                       		if ( $product->getShadeRangeProduct() == '0' && ( !empty( $shadeBaseCode ) ) ) {
					$rc = $this->checkAndUpdateShadeChild( $product );
				}
			}
		}
		*/


		//die();

		if ($rc) {
			$updProd = false;

			$attrsUpd = array();

			$storeDataRec->Magento_Product_ID = $productId;

			//echo 'magStoreId = ' . $magStoreId . "\n";

			$storeProduct = magentoProd::getMagStoreProduct($storeDataRec, $magStoreId, 'dataid', 'sku', 'Magento_Product_ID', true);

			if (!$storeProduct) {
				generalLib::error('Cannot find product for store id ' . $magStoreId . ' and product id ' . $productId);

				$retArr['rc'] = $rc;

				return $retArr;
			}


			if ($updWebSiteIds) {

				if (!in_array($websiteId, $oldWebSiteIds)) {

					if (in_array($websiteId, $newWebSiteIds)) {
						generalLib::mesg('Setting images to false');
						$attrsUpd['image'] = false;
						$attrsUpd['small_image'] = false;
						$attrsUpd['thumbnail'] = false;

						//$storeProduct->setImage(false);
						//$storeProduct->setSmallImage(false);
						//$storeProduct->setThumbnail(false);
						$updProd = true;
					}

				}

			}

			$prodStatus = $storeProduct->getStatus();

			if ($storeDataRec->show_on_web == 'Y' && $prodStatus == \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_DISABLED) {
				generalLib::mesg('Enabling product \'' . $productSku . '\' for store id \'' . $magStoreId . '\'');
				$attrsUpd['status'] = \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED;
			} else {

				if ($storeDataRec->show_on_web == 'N' && $prodStatus == \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED) {
					generalLib::mesg('Disabling product \'' . $productSku . '\' for store id \'' . $magStoreId . '\'');
					$attrsUpd['status'] = \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_DISABLED;
				}
			}


			/*
			if ($updProd) {

				//TO STOP MAGENTO REGENERATING URL-KEY FOR STORE, OTHERWISE IT RESULTS IN A 'Product with the '<PRODUCT URL KEY>'' url_key attribute already exists. ERROR'
				//$storeProduct->setUrlKey(false);

				//THE setUrlkey HAS THE EFFECT OF UNSETTING CERTAIN DEFAULT VALUE CHECK BOXES FOR THE STORE
				//THESE NEED TO BE RE-APPLIED

				$updDefProd = false;

				//TEMP
				$this->checkProdDefaultValue($storeProduct, $magStoreId, 'price', $attrUpd, $updDefProd);

				//TEMP
				$this->checkProdDefaultValue($storeProduct, $magStoreId, 'special_from_date', $attrsUpd, $updDefProd);

				if ($updProd || $updDefProd) {
					try {
						generalLib::mesg('Saving product sku \'' . $productSku . '\'');
						$storeProduct->save();
					}

					catch(Exception $e) {
						generalLib::error($e->getMessage());
						generalLib::error('Product sku \'' . $productSku . '\' not saved');
						$rc = false;
					}

				}

			}
			*/

			//die();


			//$this->checkProdDefaultValue($storeProduct, $magStoreId, 'price', $attrsUpd);

			//$this->checkProdDefaultValue($storeProduct, $magStoreId, 'special_from_date', $attrsUpd);

			print_r($attrsUpd);

			if ( count($attrsUpd) > 0 ) {
				$prodsUpd = array($productId);

				try {
					$this->procConfig->prodAction->updateAttributes($prodsUpd, $attrsUpd, $magStoreId);
				}

				catch(Exception $e) {
					$rc = false;
					generalLib::error($e->getMessage());
					generalLib::error('Product sku \'' . $productSku . '\' status not saved');
				}

			}
		}

		$retArr['rc'] = $rc;

		//die();

		return $retArr;
	}


	//###########################
	public function chkAndDeleteDupCustomOpt(&$product, $productSku) {
		$rc = true;

		$delCustomOptions = false;

		//$numLinerColourCustOpts = 0;

		foreach ($product->getOptions() as $customOption) {
			generalLib::mesg('Deleting ' . $customOption->getData('title') . ' custom option for sku \'' . $productSku . '\'');
			$customOption->delete();
			$delCustomOptions = true;;
		}

		if ($delCustomOptions) {
			$rc = MagentoCustomOpts::saveCustomOptProd($product, $productSku, true);
		}

		return $rc;
	}


	//###########################
	public function checkProdDefaultValue($product, $storeId, $prodField, &$attrsUpd) {

		$baseValue = $product->getAttributeDefaultValue( $prodField );

		//echo $prodField . ' baseValue = ' . $baseValue . "\n";

		if ($baseValue !== false) {
			generalLib::mesg('Setting '. $prodField . ' default value for store id \'' . $storeId . '\'');
		}

		$attrsUpd[ $prodField ] = false;
	}


	//###########################
	public function setProdWebsites($product, $prodStores) {
		$productSku = $product->getSku();
		$prodWebSiteIds = $product->getWebsiteIds();

		$updProd = false;

		foreach($prodStores as $prodStoreId => $actionType) {
			//ADD PRODUCT TO THE RELEVANT WEBSITES
			$websiteId = $this->procConfig->storeDets[ $prodStoreId ]->websiteId;

			if ($actionType == 'D' || $actionType == 'd') {
				if ( ($siteIdKey = array_search($websiteId, $prodWebSiteIds)) !== false) {
					unset($prodWebSiteIds[ $siteIdKey ]);
					$prodWebSiteIds = array_values($prodWebSiteIds);
					generalLib::mesg('Removing product sku \'' . $productSku . '\' from store id \'' . $prodStoreId . '\' and website id ' . '\'' . $websiteId . '\'');
					$updProd = true;
				} else {
					generalLib::warn('Cannot remove product sku \'' . $productSku . '\' from store id \'' . $prodStoreId . '\' and website id ' . '\'' . $websiteId . '\' - website id not found');
				}
			} else {
				if (!in_array($websiteId, $prodWebSiteIds)) {
					$prodWebSiteIds[] =  $websiteId;
					generalLib::mesg('Adding product sku \'' . $productSku . '\' for store id \'' . $prodStoreId . '\' to website id ' . '\'' . $websiteId . '\'');
					$updProd = true;
				}
			}

			if ($updProd) {
				$product->setWebsiteIds($prodWebSiteIds);
			}
		}

		return $updProd;
	}


	//###########################
	//
	public function checkAndUpdateShadeChild($shadeChildProduct) {
		$rc = true;
		$updProd = false;
		$actionType = 'U';

		$shadeRec = new stdClass();

		$shadeParentSku = $shadeChildProduct->getShadeRangeBasecode();
		//echo 'shadeParentSku = ' . $shadeParentSku . "\n";

		$shadeParentProduct = $this->getProductForPartnum($shadeParentSku, true);

		if (!$shadeParentProduct) {
			generalLib::warn('Shade base product not found for \'' . $shadeParentSku . '\'');
			return $rc;
		}

		//echo 'parent prod id = ' . $shadeParentProduct->getId() . "\n";

		$shadeChildSku = $shadeChildProduct->getSku();

		$shadeChildComponents = explode('-', $shadeChildSku);

		$rangeType = $shadeParentProduct->getShadeRangeType();
		//echo 'rangeType = ' . $rangeType . "\n";

		if ($rangeType == 'SOFT') {
			$braid = false;
			$fringe = false;
			$liner = false;
		} elseif ($rangeType == 'HARD' || $rangeType == 'DHL') {
			$liner = false;
		} elseif ($rangeType == 'DHLPF') {
			$inner = false;
			$outer = false;
			$cable = false;
			$glass = false;
		}

		$compNum = 1;

		foreach($shadeChildComponents as $shadeChildComponent) {

			//SKIP BASE CODE
			if ($compNum == 1) {
				$compNum++;
				continue;
			}

			if ($rangeType == 'HARD' || $rangeType == 'DHL') {

				if ( isset( $this->procConfig->liner_list[ $shadeChildComponent ] ) ) {
					$liner = $this->procConfig->liner_list[ $shadeChildComponent ];
				} else {
					$liner == 'parent';
				}

			} elseif ($rangeType == 'SOFT') {
				$firstCompChar = substr($shadeChildComponent, 0, 1);

				if ($firstCompChar == 'B') {

					if ( isset( $this->procConfig->braid_list[ $shadeChildComponent ] ) ) {
						$braid = $shadeChildComponent;
					} else {
						$braid == 'parent';
					}

				} elseif ($firstCompChar == 'F') {

					if ( isset( $this->procConfig->fringe_list[ $shadeChildComponent ] ) ) {
						$fringe = $shadeChildComponent;
					} else {
						$fringe = 'parent';
					}

				}

			} elseif ($rangeType == 'DHLPF') {
				$firstCompChar = substr($shadeChildComponent, 0, 1);

				if ($compNum == 2) {

					//print_r($this->procConfig->outerpaint_list);
					//echo 'shadeComp = ' . $shadeChildComponent . "\n";

					if ( isset( $this->procConfig->outerpaint_list[ $shadeChildComponent ] ) ) {
						$outer = $shadeChildComponent;
					} else {
						$outer = 'parent';
					}

				}

				if ($compNum == 3) {

					//print_r($this->procConfig->paint_code_list);
					//echo 'shadeComp = ' . $shadeChildComponent . "\n";

					if ( isset( $this->procConfig->innerpaint_list[ $shadeChildComponent ] ) ) {
						$inner = $shadeChildComponent;
					} else {
						$inner = 'parent';
					}

				}

				if ($firstCompChar == 'C') {

					if ( isset( $this->procConfig->cable_list[ $shadeChildComponent ] ) ) {
						$cable = $shadeChildComponent;
					} else {
						$cable = 'parent';
					}

				} elseif ($firstCompChar == 'G') {

					if ( isset( $this->procConfig->glass_list[ $shadeChildComponent ] ) ) {
						$glass = $shadeChildComponent;
					} else {
						$glass = 'parent';
					}

				}

			}

			$compNum++;
		}


		if ($rangeType == 'HARD' || $rangeType == 'DHL') {

			if (!$liner) {
				$liner = 'parent';
			}

		} elseif ($rangeType == 'SOFT') {

			if (!$braid) {
				$braid = 'parent';
			}

			if (!$fringe) {
				$fringe = 'parent';
			}

		} elseif ($rangeType == 'DHLPF') {

			if (!$inner) {
				$inner = 'parent';
			}

			if (!$outer) {
				$outer = 'parent';
			}

			if (!$cable) {
				$cable = 'parent';
			}

			if (!$glass) {
				$glass = 'parent';
			}
		}

		if ($rangeType == 'SOFT') {
			echo 'braid = ' . $braid . "\n";
			echo 'fringe = ' . $fringe . "\n";
			echo 'liner = ' . $liner . "\n";
		}

		if ($rangeType == 'DHLPF') {
			echo 'inner = ' . $inner . "\n";
			echo 'outer = ' . $outer . "\n";
			echo 'cable = ' . $cable . "\n";
			echo 'glass = ' . $glass . "\n";
		}
		//die();

		//echo 'parent prod id = ' . $shadeParentProduct->getId() . "\n";

		//populate shadeRec VIA THE PARENT SHADE
		foreach($this->procConfig->shaderangesCustomAttrs as $shaderangesCustomAttr => $shaderangesCustomAttrType) {
			//echo 'shaderangesCustomAttr = ' . $shaderangesCustomAttr . "\n";

			//echo $shaderangesCustomAttr . ' = ' . $shadeParentProduct->getData($shaderangesCustomAttr) . "\n";
			$shadeRec->$shaderangesCustomAttr = $shadeParentProduct->getData($shaderangesCustomAttr);
			//echo 'shadeRec->' . $shaderangesCustomAttr . ' = ' . $shadeRec->$shaderangesCustomAttr . "\n";
		}


		$updAttrs = magentoImport::processCustomAttrs($this, $shadeChildProduct, $shadeRec, $this->procConfig->shaderangesCustomAttrs);

		if ($updAttrs && (!$updProd)) {
			$updProd = true;
		}

		$shadeRec->shade_range_name = array_search($shadeParentProduct->getData('shade_range_name'), $this->procConfig->shadeRangeOpts);
		$shadeRec->shade_baffle = array_search($shadeParentProduct->getData('shade_baffle'), $this->procConfig->shadeBaffleOpts);

		$updAttrs = magentoImport::chkDropdownAttr($shadeChildProduct, 'shade_range_name', (string) $shadeRec->shade_range_name, $this->procConfig->shadeRangeOpts);

		if ($updAttrs && (!$updProd)) {
			$updProd = true;
		}


		$updAttrs = magentoImport::chkDropdownAttr($shadeChildProduct, 'shade_baffle', (string) $shadeRec->shade_baffle, $this->procConfig->shadeBaffleOpts);

		if ($updAttrs && (!$updProd)) {
			$updProd = true;
		}


		//$updProd = true;

		if ($updProd) {

			try {
				generalLib::mesg('Saving product sku \'' . $shadeChildSku . '\'');
                $shadeChildProduct->save();
			}

			catch (Exception $e) {
				generalLib::error($e->getMessage());
				$rc = false;
			}
		}

		if ($rc && $updProd) {
			$shadeChildProduct = $this->objectManager->create('Magento\Catalog\Model\Product')->load( $shadeChildProduct->getId() );
		}

		if ($rc) {
			$shadeRec = new stdClass();

			//echo 'parent sku = ' . $shadeParentProduct->getSku() . "\n";

			//COPY THE TIER SETTINGS FROM THE PARENT SHADE PRODUCT
			foreach($this->procConfig->shaderangesTierCustomAttrs as $shaderangesTierCustomAttr => $shaderangesLinedCustomAttrType) {
				//echo 'shaderangesTierCustomAttr = ' . $shaderangesTierCustomAttr . "\n";

				$shadeRec->$shaderangesTierCustomAttr = $shadeParentProduct->getData($shaderangesTierCustomAttr);
			}

			//echo "bef build\n";
			//print_r($shadeRec);

			$rc = $this->buildShadeRangeCustomOptions($shadeChildProduct, $shadeRec);
		}

		//echo 'rc = ' . $rc . "\n";
		//echo 'rangeType = ' . $rangeType . "\n";

		if ($rc && $rangeType == 'SOFT') {

			/*
			ACCESSORY CUSTOM OPTIONS NOT REQUIRED AS THESE RELATE TO PRODUCT SKUS WHICH ARE ADDED AS SEPARATE ORDER LINES
			$shadeParentAccessoryCustOptValues = magentoCustomOpts::getCustomOptionValues($shadeParentProduct, 'Accessory');

			foreach($shadeParentAccessoryCustOptValues as $shadeParentAccessoryCustOptValue) {

				$shadeAccessoryRec = new stdClass();
				$shadeAccessoryRec->accessory_partnum = $shadeParentAccessoryCustOptValue[ 'title' ];

				$retArr = $this->buildShadeCustomOption('U', $shadeChildProduct, 'Accessory',  $shadeAccessoryRec->accessory_partnum, '0');

				$rc = $retArr['rc'];

				if (!$rc) {
					break;
				}
			}

			$rc = $this->chkAndDeleteCustomOptionValue($shadeChildProduct, 'Accessory', $shadeParentAccessoryCustOptValues);
			*/

			if ($rc) {
				$rc = $this->setCustomOption('Braid', $braid, $shadeParentProduct, $shadeChildProduct);
			}

			if ($rc) {
				$rc = $this->setCustomOption('Fringe', $fringe, $shadeParentProduct, $shadeChildProduct);
			}

		}


		//WILL HAVE TO APPLY FOR EACH RELEVANT SHADE LINER PRICE RECORD - UNLESS THE PART NUMBER CONTAINS A SPECIFIC LINER
		//IF PARENT - GET ALL PRODUCT LINER CUSTOM OPTIONS AND LOOP
		//IF A SPECIFIC LINER ONLY PROCESS THAT PARTICULAR LINER

		if ($rc && ($rangeType == 'HARD' || $rangeType == 'DHL') ) {

			$shadeParentLinerCustOptValues = magentoCustomOpts::getCustomOptionValues($shadeParentProduct, 'Liner Colour');
			//print_r($shadeParentLinerCustOptValues);

			foreach($shadeParentLinerCustOptValues as $shadeParentLinerCustOptValue) {

				if ($liner == 'parent' || $liner == $shadeParentLinerCustOptValue[ 'title' ]) {

					$shadeLinerPriceRec = new stdClass();
					$shadeLinerPriceRec->liner_code = array_search($shadeParentLinerCustOptValue[ 'title' ], $this->procConfig->liner_list);

					if ($liner == 'parent') {
						$shadeLinerPriceRec->liner_price = $shadeParentLinerCustOptValue[ 'price' ];
					} else {
						$shadeLinerPriceRec->liner_price = '0'; //LINER PRICE IS INCLUDED IN THE PRODUCT PRICE
					}

					//echo "parent values:\n";
					//print_r($shadeParentLinerCustOptValue);

					//echo "shadeLinerPriceRec:\n";
					//print_r($shadeLinerPriceRec);

					$retArr = $this->buildShadeCustomOption('U', $shadeChildProduct, 'Liner Colour',  $this->procConfig->liner_list[ $shadeLinerPriceRec->liner_code ], $shadeLinerPriceRec->liner_price);

					$rc = $retArr['rc'];

					if (!$rc) {
						break;
					} else {
						//$shadeChildProduct = Mage::getModel('catalog/product')->load($shadeChildProduct->getId());
						//$shadeChildProduct = $retArr['product'];
						//$retArr = MagentoCustomOpts::saveCustomOptProd($shadeChildProduct, $shadeChildProduct->getSku(), true);
						//$shadeChildProduct = $retArr['product'];
					}
				}
			}

			$rc = $this->chkAndDeleteCustomOptionValue($shadeChildProduct, 'Liner Colour', $shadeParentLinerCustOptValues);
		}

		if ($rc && ($rangeType == 'DHLPF') ) {

			$rc = $this->setCustomOption('Inner', $inner, $shadeParentProduct, $shadeChildProduct);

			if ($rc) {
				$rc = $this->setCustomOption('Outer', $outer, $shadeParentProduct, $shadeChildProduct);
			}

			if ($rc) {
				$rc = $this->setCustomOption('Cable', $cable, $shadeParentProduct, $shadeChildProduct);
			}

			if ($rc) {
				$rc = $this->setCustomOption('Glass', $glass, $shadeParentProduct, $shadeChildProduct);
			}

		}

		//echo 'rc = ' . $rc . "\n";
		return $rc;
	}


	//###########################
	private function setCustomOption($custOptType, $custOptCode, $shadeParentProduct, $shadeChildProduct) {
		$rc = true;

		//echo "setCustomOption:\n";
		//echo 'custOptType = ' . $custOptType . "\n";

		$shadeParentCustOptValues = magentoCustomOpts::getCustomOptionValues($shadeParentProduct, $custOptType);

		//echo 'custOptCode = ' . $custOptCode . "\n";
		//print_r($shadeParentCustOptValues);

		foreach($shadeParentCustOptValues as $shadeParentCustOptValue) {

			if ($custOptCode == 'parent' || $custOptCode == $shadeParentCustOptValue[ 'title' ]) {

				$shadePriceRec = new stdClass();
				$shadePriceRec->code = $shadeParentCustOptValue[ 'title' ];

				if ($custOptCode == 'parent') {
					$shadePriceRec->price = $shadeParentCustOptValue[ 'price' ];
				} else {
					$shadePriceRec->price = '0'; //PRICE IS INCLUDED IN THE PRODUCT PRICE
				}

				//print_r($shadeBraidPriceRec);
				//die();

				$retArr = $this->buildShadeCustomOption('U', $shadeChildProduct, $custOptType,  $shadePriceRec->code, $shadePriceRec->price);

				$rc = $retArr['rc'];

				if (!$rc) {
					break;
				}
			}
		}

		if ($rc) {
			$rc = $this->chkAndDeleteCustomOptionValue($shadeChildProduct, $custOptType, $shadeParentCustOptValues);
		}

		return $rc;
	}


	//###########################
	//DELETE ANY VALUES FOR THE CUSTOM OPTION NOT PRESENT ON THE PARENT SHADE PRODUCT
	private function chkAndDeleteCustomOptionValue($product, $custOptTitle, $parentShadeCustOptValues) {
		$rc = true;

		//echo "chkAndDeleteCustomOptionValue:\n";
		//echo 'custOptTitle = ' . $custOptTitle . "\n";

		$childShadeCustOptValues = magentoCustomOpts::getCustomOptionValues($product, $custOptTitle);

		//echo "parent: \n";
		//print_r($parentShadeCustOptValues);

		//echo "child: \n";
		//print_r($childShadeCustOptValues);

		foreach($childShadeCustOptValues as $childShadeCustOptValue) {
			$gotParentCustOptValue = false;

			foreach($parentShadeCustOptValues as $parentShadeCustOptValue) {

				if ($parentShadeCustOptValue['title'] == $childShadeCustOptValue['title']) {
					$gotParentCustOptValue = true;
					break;
				}
			}

			if (!$gotParentCustOptValue) {
				$retArr = $this->buildShadeCustomOption('D', $product, $custOptTitle,  $childShadeCustOptValue['title'], $childShadeCustOptValue['price']);

				$rc = $retArr['rc'];

				if (!$rc) {
					break;
				}
			}
		}

		return $rc;
	}


	//###########################
	public function processX_Web_Lamp_CategoryWebService($actionType, $xweblampcatRec) {
		$rc = true;

		$retArr = array();

		generalLib::mesg('');
		generalLib::mesg('====');

		print_r($xweblampcatRec);

		$lampGrpProdId = false;

		$grpProdCollection = $this->objectManager->create('Magento\Catalog\Model\Product')
                ->getCollection()
                ->addAttributeToFilter('type_id', 'grouped')
                ->addAttributeToFilter('lamp_group_id', $xweblampcatRec->lampgroupid )
                ->setOrder('entity_id','DESC')
                ->setPageSize(1)
                ->setCurPage(1);

		foreach ($grpProdCollection as $grpProd) {

		        $lampGrpProdId = $grpProd->getId();

		}

		//echo 'lampGrpProdId = ' . $lampGrpProdId . "\n";

		if ($lampGrpProdId) {
			$grpProduct = $this->objectManager->create('Magento\Catalog\Model\Product')->load( $lampGrpProdId );
		} else {
			$grpProduct = false;
		}

		if (!$grpProduct) {
			generalLib::warn('Cannot update group product for lamp group id \'' . $xweblampcatRec->lampgroupid . '\' - does not exist');
			$rc = false;

			$retArr['rc'] = $rc;
			return $retArr;
		}

		$productSku = $grpProduct->getSku();

		if ($actionType == 'D') {
			generalLib::mesg('Deleting group product with SKU: ' . $productSku);

			try {
				$grpProduct->delete();
				generalLib::mesg('Product deleted');
			}

			catch (Exception $e) {
				generalLib::error($e->getMessage());
				generalLib::error('Product not deleted');
				$rc = false;
			}

			$retArr['rc'] = $rc;

			return $retArr;
		}

		$updProd = false;

		if (magentoImport::processMagAttrs($grpProduct, $xweblampcatRec, array('lampgroupname' => 'name'))) {
			$grpProduct->setShortDescription( $xweblampcatRec->lampgroupname );
			$grpProduct->setDescription( $xweblampcatRec->lampgroupname );

			$productSku = str_replace(' ', '_', $xweblampcatRec->lampgroupname);
			$grpProduct->setSku( $productSku );
			$updProd = true;
		}

		$oldCatIds = $grpProduct->getCategoryIds();

		if ( magentoImport::processMagAttrs($grpProduct, $xweblampcatRec, $this->procConfig->xWebLampCategoryMagAttrs) ) {

			$magCatIdFld = $this->procConfig->catCustomAttrMappings[ $this->procConfig->extCatId ];

			//echo 'magCatIdFld = ' . $magCatIdFld . "\n";
			$category = magentoCat::getCategory($magCatIdFld, $xweblampcatRec->categoryid);

			if ($category) {
				generalLib::mesg('setting category id to \'' . $category->getId() . '\' (' . $xweblampcatRec->categoryid . ')');
				$grpProduct->setCategoryIds( $category->getId() );
			} else {
				generalLib::warn('Cannot find category via external category id \'' . $xweblampcatRec->categoryid . '\'');
				$grpProduct->setGeneralCatId( $oldCatIds );
				$rc = false;
			}

			$updProd = true;
		}


		if ($updProd) {
			try {
				generalLib::mesg('Saving group product sku \'' . $productSku . '\'');
				$grpProduct->save();
			}

			catch(Exception $e) {
					generalLib::error($e->getMessage());
					generalLib::error('Group product sku \'' . $productSku . '\' not saved');
					$rc = false;
			}
		} else {
			generalLib::mesg('No web lamp category updates to apply');
		}

		$retArr['rc'] = $rc;

		return $retArr;
	}


	//###########################
	public function processX_Web_LampWebService($actionType, $xweblampRec) {
		$rc = true;

		$retArr = array();

		generalLib::mesg('');
		generalLib::mesg('====');

		print_r($xweblampRec);

		$product = $this->getProductForPartnum($xweblampRec->partnum, false);

		$xwebLampCatRec = $this->procConfig->xweblampcat_list[ $xweblampRec->lampgroupid ];

		if (!$product) {
			generalLib::warn('Cannot process part num \'' . $xweblampRec->partnum . '\' for grouped product \'' . $xwebLampCatRec->lampgroupname . '\' - Part num does not exist in stock table');
			$rc = false;

			$retArr['rc'] = $rc;

			return $retArr;
		}

		$updProd = false;

		$productSku = $product->getSku();
		$productId = $product->getId();

		$xweblampRec->web_lamp_product = '1';


		$updAttrs = magentoImport::processMagAttrs($product, $xweblampRec, $this->procConfig->xWebLampMagAttrs);

		if ($updAttrs && (!$updProd)) {
			$updProd = true;
		}


		$oldLampGroupId = $product->getLampGroupId();

		if ( magentoImport::processMagAttrs($product, $xweblampRec, $this->procConfig->xWebLampGroupIdMagAttr) ) {

			if ( !empty($oldLampGroupId) ) {
				$grpProdCollection = $this->objectManager->get('Magento\Catalog\Model\Product')
                	->getCollection()
                	->addAttributeToFilter('type_id', 'grouped')
                	->addAttributeToFilter('lamp_group_id', $oldLampGroupId)
                	->setOrder('entity_id','DESC')
                	->setPageSize(1)
                	->setCurPage(1);

				$oldLampGrpProdId = 0;

				foreach ($grpProdCollection as $grpProd) {
			        	$oldLampGrpProdId = $grpProd->getId();
				}

				if ($oldLampGrpProdId != 0) {

					$oldLampGrpProduct = $this->objectManager->create('Magento\Catalog\Model\Product')->load( $oldLampGrpProdId );

					if ($oldLampGrpProduct) {

						$rc = magentoGroupProducts::processGroupedProductForSingleProduct($oldLampGrpProduct, '', '', 'mag_id', array( $productId ), 'D', true);

						if ($rc) {
							$rc = magentoGroupProducts::setGrpProdWebSitesFromAssocProds($this->procConfig->storeDets, $oldLampGrpProduct->getSku() );
						}

					}

					if (!$rc) {
						return $rc;

						$retArr['rc'] = $rc;

						return $retArr;
					}

				}
			}

			$updProd = true;
		}


		$updAttrs = magentoImport::processCustomAttrs($this, $product, $xweblampRec, $this->procConfig->xWebLampCustomAttrs);

		if ($updAttrs && (!$updProd)) {
			$updProd = true;
		}


		$updAttrs = magentoimport::chkDropdownAttr($product, 'lampholder', (string) $xweblampRec->lampholder, $this->procConfig->lampholderOpts);

		if ($updAttrs && (!$updProd)) {
			$updProd = true;
		}


		if ( !isset($xweblampRec->glass) ) {
			$xweblampRec->glass = '';
		}

		$updAttrs = magentoimport::chkDropdownAttr($product, 'glass', (string) $xweblampRec->glass, $this->procConfig->glassOpts);

		if ($updAttrs && (!$updProd)) {
			$updProd = true;
		}


		$updAttrs = magentoimport::chkDropdownAttr($product, 'lamplife', (string) $xweblampRec->lamplife, $this->procConfig->lamplifeOpts);

		if ($updAttrs && (!$updProd)) {
			$updProd = true;
		}


		if ($updProd) {
			try {
				generalLib::mesg('Saving product sku \'' . $productSku . '\'');
				$product->save();
			}

			catch(Exception $e) {
					generalLib::error($e->getMessage());
					generalLib::error('Product sku \'' . $productSku . '\' not saved');
					$rc = false;
			}
		} else {
			generalLib::mesg('No web lamp updates to apply');
		}

		$grpProdAttrs = new stdClass();
		$grpProdAttrs->name = $xwebLampCatRec->lampgroupname;
		$grpProdAttrs->description = $xwebLampCatRec->lampgroupname;

		$grpProdAttrs->sku = str_replace(' ', '_', $xwebLampCatRec->lampgroupname);

		$grpProdAttrs->lampGroupId = $xweblampRec->lampgroupid;
		$grpProdAttrs->lampGroupCatId = $xwebLampCatRec->categoryid;

        $magCatIdFld = $this->procConfig->catCustomAttrMappings[ $this->procConfig->extCatId ];

		$category = magentoCat::getCategory($magCatIdFld, $xwebLampCatRec->categoryid);

		if ($category == false) {

			generalLib::warn('Cannot find category via category id \'' . $xwebLampCatRec->categoryid . '\'');
			$grpProdAttrs->categoryId = '';

		} else {
			$grpProdAttrs->categoryId = $category->getId();
		}


		$rc = $this->processGroupedProduct($actionType, $grpProdAttrs, $productId);

		//die();

		$retArr['rc'] = $rc;

		return $retArr;
	}


	//###########################
	public function processGroupedProduct($actionType, $grpProdAttrs, $productId) {
		$rc = true;
		//echo 'actionType = ' . $actionType . "\n";

		//CHECK WHETHER GROUPED PRODUCT EXISTS - THE SUB CATEGORY IS USED AS THE SKU FOR THE GROUPED PRODUCT
		//echo 'processGroupedProduct: sub cat = ' . $stSubCat . "\n";
		$grpProduct = $this->objectManager->get('Magento\Catalog\Model\Product')->loadByAttribute('sku', $grpProdAttrs->sku);

		//IF DELETING AND NO GROUP PRODUCT - NOTHING TO DO
		if (!$grpProduct && $actionType == 'D') {
			generalLib::warn('Cannot delete Group product sku \''. $grpProdAttrs->sku . '\' as it does not exist - aborting grouped product processing');
			$rc = false;
			return $rc;
		}

		//IF THE GROUPED PRODUCTS DOES NOT EXIST - CREATE IT
		if (!$grpProduct) {

			//echo "No group product exists\n";
			$grpProduct = $this->objectManager->create('Magento\Catalog\Model\Product');

			//die();
			$grpProduct->setTypeId(\Magento\GroupedProduct\Model\Product\Type\Grouped::TYPE_CODE);
			$grpProduct->setStatus(\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED);
			$grpProduct->setVisibility(\Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH);
			$grpProduct->setAttributeSetId(4);

			$grpProduct->setName( $grpProdAttrs->name );
			$grpProduct->setShortDescription( $grpProdAttrs->description );
			$grpProduct->setDescription( $grpProdAttrs->description );
			$grpProduct->setSku( $grpProdAttrs->sku );
			$grpProduct->setPrice(0);
			$grpProduct->setWeight(0);
			$grpProduct->setStockData(array(
                                    'is_in_stock'             => 1,
                                    'manage_stock'            => 0,
                                    'use_config_manage_stock' => 1
                                    ));

			$grpProduct->setLampGroupId( $grpProdAttrs->lampGroupId );
			$grpProduct->setLampGroupCatId( $grpProdAttrs->lampGroupCatId );
			$grpProduct->setCategoryIds( $grpProdAttrs->categoryId );

			$prodStores = array( $this->procConfig->b2bStoreId => 'U');

			$updAttrs = $this->setProdWebsites($grpProduct, $prodStores);

		} else {

			$grpProdId = $grpProduct->getId();

			$grpProduct = $this->objectManager->get('Magento\Catalog\Model\Product')->load( $grpProdId );

			$updProd = false;

			if ($grpProduct->getTypeId() != \Magento\GroupedProduct\Model\Product\Type\Grouped::TYPE_CODE) {
				generalLib::warn('Product with sku \'' . $grpProdAttrs->sku . '\' is not a grouped product - aborting grouped product processing');
				$rc = false;
				return $rc;
			}

		}

		//ONLY EVER AVAILABLE IN THE B2B STORE - DEPRECATING STORE ID LOOK-UP
		//$prodStores = $this->getStoresForDataId('PR', $grpProdAttrs->sku);

		//ADD OR REMOVE PRODUCT TO/FROM GROUP PRODUCT - DELETE THE GROUP PRODUCT IF A DELETION RESULTS IN NO REMAINING ASSOCIATED PRODUCTS

		$rc = magentoGroupProducts::processGroupedProductForSingleProduct($grpProduct, '', '', 'mag_id', array($productId), $actionType, true);

		if ($rc) {
			$rc = magentoGroupProducts::setGrpProdWebSitesFromAssocProds($this->procConfig->storeDets, $grpProdAttrs->sku);
		}

		//die();
		return $rc;
	}


	//###########################
	public function processUsersWebService($actionType, $userRec) {

		$retArr = array();

		$rc = true;

		generalLib::mesg('');
		generalLib::mesg('====');

		//die();
		//echo "custreci:\n";
		print_r($userRec);
		//die();

		if ( !isset($userRec->userid) ) {

			generalLib::warn('Undefined user id - skipping customer');
			$rc = false;

			$retArr['rc'] = $rc;

			return $retArr;

		}

		if ( !isset($userRec->email) ) {
			generalLib::warn('Undefined email - skipping customer');
			$rc = false;

			$retArr['rc'] = $rc;

			return $retArr;
		}

		$userId = $userRec->userid;

		$userRec->cust = $userRec->userid;

		$updCust = false;

		//$customer = magentoCust::getMagCustomer($userRec, 'userid', 'userid', 'cust_magento', false);
		$customer = magentoCust::getMagCustomer($userRec, 'cust', 'cust', 'cust_magento', false);
		//echo "customer:\n";
		//print_r($customer->getData());
		//die();

		if ($actionType == 'D') {
			$rc = magentoCust::processCustDeletion( $customer->getStoreId(),  $userRec->userid, $customer);

			$retArr['rc'] = $rc;

			return $retArr;
		}

		if (!$customer) {
			$customer = $this->objectManager->create('Magento\Customer\Model\Customer');
			//$customerFactory = $this->objectManager->get('\Magento\Customer\Model\CustomerFactory');
			//$customer = $customerFactory->create();
			$customer->setStoreId( $this->procConfig->b2bStoreId );
			$customer->setCreatedIn( $this->procConfig->storeDets[ $this->procConfig->b2bStoreId ]->storeName );
			$customer->setWebsiteId($this->procConfig->storeDets[ $this->procConfig->b2bStoreId ]->websiteId);
			$custId = '';
			$userId = $userRec->userid;

			generalLib::mesg('Creating customer \'' . $userId . '\' (' . $userRec->email . ') for store id \'' . $this->procConfig->b2bStoreId . '\'');
		} else {
			$custId = $customer->getId();

			//$userId = $customer->getUserid();
			$userId = $customer->getCust();

			generalLib::mesg('Updating customer \'' . $userId . '\' (' . $userRec->email . ') for store id \'' . $this->procConfig->b2bStoreId . '\'');
		}

		//die();

		if ( isset($userRec->epassword) ) {
			$userRec->epassword = strtolower( $userRec->epassword );
			$customer->setPasswordHash($userRec->epassword);
			generalLib::mesg('Setting password hash to \'' . $userRec->epassword . '\'');
			$updCust = true;
		}

		$updAttrs = magentoImport::processMagAttrs($customer, $userRec, $this->procConfig->custMagAttrs);

		if ($updAttrs && (!$updCust)) {
			$updCust = true;
		}


		$updAttrs = magentoImport::processCustomAttrs($this, $customer, $userRec, $this->procConfig->custCustomAttrs);

		if ($updAttrs && (!$updCust)) {
			$updCust = true;
		}

		//echo 'attr set id = ' . \Magento\Customer\Api\CustomerMetadataInterface::ATTRIBUTE_SET_ID_CUSTOMER . "\n";
		//die();

		//IMPORTANT: WITHOUT THIS LINE, UPDATES TO THE CUSTOMER INFORMATION RESULTS IN THE LOSS OF CUSTOM ATTRIBUTE VALUES
		//SEE: https://github.com/magento/magento2/issues/12479
		$customer->setAttributeSetId(\Magento\Customer\Api\CustomerMetadataInterface::ATTRIBUTE_SET_ID_CUSTOMER);

		$newMdValueArr = array();

		if ( isset ( $userRec->allowed_selling_brand ) ) {

			$allowedSellingBrandArr = explode(',', $userRec->allowed_selling_brand);

			foreach ($allowedSellingBrandArr as $allowedSellingBrandVal) {
				magentoImport::chkModelMultiSelectAttr('customer', $customer, 'allowed_selling_brand', $allowedSellingBrandVal, $this->procConfig->allowedSellingBrandOpts, $newMdValueArr);
			}

		}


		if ( magentoImport::chkModelMultiSelectAttrVals('customer', $customer, 'allowed_selling_brand', $newMdValueArr, $this->procConfig->allowedSellingBrandOpts) ) {
			$updCust = true;
		}

		if ($updCust) {
			//print_r($customer->getData());

			try {
				generalLib::mesg('Saving customer');
				$customer->save();
			} catch (Exception $e) {
				generalLib::error($e->getMessage());
				$rc = false;
			}

			if ($rc && $custId == '') {
				if (($custId = $customer->getId()) == false) {
					generalLib::error('Could not get ID for customer \'' . $custCode . '\' (' . $userRec->email . ')');
					$rc = false;
				}
			}
		} else {
			generalLib::mesg('No changes to apply');
		}

		//die();

		$retArr['rc'] = $rc;

		return $retArr;
	}


	//###########################
	public function processW_AddressesWebService($actionType, $custAddrRec) {

		$retArr = array();

		$rc = true;

		generalLib::mesg('');
		generalLib::mesg('====');


		if ( !isset($custAddrRec->address1) ) {
			$custAddrRec->address1 = '';
		}

		if ( !isset($custAddrRec->address2) ) {
			$custAddrRec->address2 = '';
		}

		if ( !isset($custAddrRec->address3) ) {
			$custAddrRec->address3 = '';
		}

		if ( !isset($custAddrRec->address4) ) {
			$custAddrRec->address4 = '';
		}

		if ( !isset($custAddrRec->postcode) ) {
			$custAddrRec->postcode = '';
		}

		if ( !isset($custAddrRec->adtelephone) ) {
			$custAddrRec->adtelephone = '-';
		}

		if ( !isset($custAddrRec->advatcat) ) {
			$custAddrRec->advatcat = '';
		}

		if ( empty( $custAddrRec->address3 ) ) {

			if ( !empty( $custAddrRec->address4 ) ) {

				$custAddrRec->address3 = $custAddrRec->address4;

				$custAddrRec->address4 = '';
			}

		}

		print_r($custAddrRec);

		//TEMP WORK AROUND DUE TO MAGENTO BUG WHERE THESE NEW ATTRIBUTE VALUES ARE NOT BEING SAVED
		/*
		$this->procConfig->userMainContact = '';

		$this->procConfig->userCdefDelAdd = '';

		//TEMP - GET maincontact and cdefdeladd FROM USERS WEB SERVICE - WORK AROUND DUE TO MAGENTO BUG WHERE THESE NEW ATTRIBUTE VALUES ARE NOT BEING SAVED
		$funcFeedWebService = 'Users';

		$url = $this->procConfig->baseWsUrl . '/' . $funcFeedWebService;

		echo 'url = ' . $url . "\n";

		$processFunc = 'process' . $funcFeedWebService . 'Values';

		$parameters = 	array('filter' => array(
													array( 'Field' => 'userid', 'Criteria' => $custAddrRec->addrcode ),
													)
												);

		$batchSize = 10;

		print_r($parameters);

		try {
			$this->procConfig->wsAddlConn->processData($url, 'ReadMultiple', $parameters, $batchSize, $funcFeedWebService, $processFunc, 'actiontype');

		}

		catch(Exception $e){
				generalLib::error($e->getMessage());
		}

		//die();
		*/

		/*
		$addrExclArr = array('closed', 'see below', 'see below for address', 'please see address details', '');

		if (in_array( strtolower($custAddrRec->address1), $addrExclArr)) {
			generalLib::warn('Invalid address \'' . $address1 . '\' for addrcode \'' . $custAddrRec->addrcode . '\' addritem \'' . $custAddrRec->addritem . '\'');
		}

		if ($custAddrRec->address2 == '') {
			generalLib::warn('Blank address 2 \'' . $address2 . '\' for addrcode \'' . $custAddrRec->addrcode . '\' addritem \'' . $custAddrRec->addritem . '\'');
		}
		*/

		$customer = magentoCust::getMagCustomer($custAddrRec, 'addrcode', 'userid', 'cust_magento_id', false);
		//$customer = magentoCust::getMagCustomer($custAddrRec, 'addrcode', 'cust', 'cust_magento_id', false);

		if ($customer) {

			//TEMP - UNTO MAGENTO CUSTOMER ADDITIONAL ATTRIBUTES SAVE FIX
			//if ($this->procConfig->userMainContact != '' && $this->procConfig->userCdefDelAdd != '') {

				$custAddrSettings = new stdClass();
				$custAddrSettings->defaultAddr = false;
				$gotDefaultBilling = false;
				$gotDefaultShipping = false;

				$custAddrRec->custname = $customer->getLastname();

				$custAddrRec->firstname = $customer->getFirstname();

				$custAddrRec->lastname = $customer->getLastname();

				//TEMP - UNTO MAGENTO CUSTOMER ADDITIONAL ATTRIBUTES SAVE FIX
				//if ($this->procConfig->userMainContact == $custAddrRec->addritem) {

				//echo 'maincontact = ' . $customer->getMaincontact() . "\n";
				//echo 'cdefdeladd = ' . $customer->getCdefdeladd() . "\n";

				if ($customer->getMaincontact() == $custAddrRec->addritem) {
					$gotDefaultBilling = true;
				}

				//TEMP - UNTO MAGENTO CUSTOMER ADDITIONAL ATTRIBUTES SAVE FIX
				//if ($this->procConfig->userCdefDelAdd == $custAddrRec->addritem) {
				if ($customer->getCdefdeladd() == $custAddrRec->addritem) {
					$gotDefaultShipping = true;
				}

				//echo 'gotDefaultBilling = ' . $gotDefaultBilling . "\n";
				//echo 'gotDefaultShipping = ' . $gotDefaultShipping . "\n";
				//die();

				if ($gotDefaultBilling && $gotDefaultShipping) {

					$custAddrSettings->addrType = magentoCust::BILLING_AND_SHIPPING;

				} else {

					if ($gotDefaultBilling) {
						$custAddrSettings->addrType = magentoCust::BILLING;
					} else {
						$custAddrSettings->addrType = magentoCust::SHIPPING;
					}

				}

				if ($gotDefaultBilling || $gotDefaultShipping) {
					$custAddrSettings->defaultAddr = true;
				}

				$custAddrSettings->customAddrId = $custAddrRec->addritem;
				$custAddrSettings->defaultCountryId = 'GB';

				print_r($custAddrSettings);
				$rc = magentoCust::processCustomerAddressNew($actionType, $customer, $custAddrRec, $this->procConfig->custAddrAttrs, $custAddrSettings);
				//die();

			/*
			} else {
				generalLib::warn('Cannot process addresses for customer code \'' . $custAddrRec->addrcode . '\' - default main contact or delivery address undefined');
				$rc = false;
			}
			*/

		} else {
			generalLib::warn('Cannot process addresses for customer code \'' . $custAddrRec->addrcode . '\' - customer not found');
			$rc = false;
		}

		$retArr['rc'] = $rc;

		return $retArr;
	}


	//###########################
	public function processUsersValues($actionType, $userRec) {

		$retArr = array();

		$this->procConfig->userMainContact = $userRec->maincontact;

		//echo 'userMainContact = ' . $this->procConfig->userMainContact . "\n";

		$this->procConfig->userCdefDelAdd = $userRec->cdefdeladd;

		//echo 'userCdefDelAdd = ' . $this->procConfig->userCdefDelAdd . "\n";

		$rc = false;

		$retArr['rc'] = $rc;

		return $retArr;
	}


	//###########################
	public function getProductForPartnum($partNum, $reportErrors) {
		//$productSku = (string)$partNum;
		//$productSku = str_replace( array('/', ' '), '_', $productSku);

		//$productId = Mage::getModel('catalog/product')->getIdBySku($productSku);

		//if (is_numeric($productId)) {
			//echo 'getProductForPartNum: product id = ' . $productId . "\n";
			$prodObj = new stdClass();
			$prodObj->sku = $partNum;
			//$prodObj->magId = $productId;

			//print_r($prodObj);

			$product = magentoProd::getMagProduct($prodObj, 'sku', 'sku', 'magId', $reportErrors);
		//} else {
			//if ($reportErrors) {
				//generalLib::warn('Cannot find product for sku \'' . $productSku . '\'');
			//}

			//$product = false;
		//}

		/*
		if ($product) {
			echo "got product\n";
		} else {
			echo "not got product\n";
		}
		*/

		return $product;
	}


	//###########################
	/*
	public function getStoresForDataId($dataType, $dataId) {
		$stores = array();

		$query = 'SELECT storeid, actiontype FROM storedata WHERE datatype = "' . $dataType . '" AND dataid = "' . $dataId . '"';
		//echo 'query = ' . $query . "\n";

		$result = $this->dbObj->absDbQuery($query);

		while($queryResult = $this->dbObj->absDbFetchArray($result, PDO::FETCH_ASSOC)) {
			$queryResultObj = (object) $queryResult;

			$stores[ $this->procConfig->darToMagStoreMappings[ $queryResult['storeid'] ] ] = $queryResult['actiontype'];
		}

		//print_r($stores);
		return $stores;
	}
	*/


	//###########################
	public function processScanDbFeed() {

		if ($this->procConfig->feedType == 'PROMO_EXPIRY' || $this->procConfig->feedType == 'ALL') {
			self::scanPromoExpiry();
		}

		/*
		if ($feedType == 'STOCK_RRP' || $feedType == 'ALL') {
			self::scanStockRrp();
		}
		*/
	}


	//###########################
	//SCAN FOR PRODUCTS WITH NO RRP OR SRP - THESE PRODUCTS TO HAVE THEIR RRP SET TO TWICE THE DEFAULT PRICE
	/*
	public function scanStockRrp() {
		$collection = Mage::getModel("catalog/product")
			->getCollection()
			->addAttributeToFilter('type_id', 'simple')
			->addAttributeToSelect(array('mrrp', 'msrp'))
			->addAttributeToFilter('mrrp', array('eq' => array('0')))
			->addAttributeToFilter('msrp', array('eq' => array('0')));

		generalLib::mesg('RRP Scan');

		foreach ($collection as $product) {
			//echo 'prod id = ' . $product->getId() . "\n";
			//echo 'prod sku = ' . $product->getSku() . "\n";

			$prodObj = new stdClass();
			$prodObj->sku = $product->getSku();
			$prodObj->magId = $product->getId();

			$product = magentoProd::getMagProduct($prodObj, 'sku', 'sku', 'magId', true);

			if ($product) {
				generalLib::mesg('');
				generalLib::mesg('====');
				$productSku = $product->getSku();

				$mrrp = $product->getPrice() * 2;
				$product->setMrrp($mrrp);
				generalLib::mesg('Setting RRP to \'' . $mrrp . '\'');

				try {
					generalLib::mesg('Saving product sku \'' . $productSku . '\'');
					$product->save();
				}

				catch(Exception $e) {
					generalLib::error($e->getMessage());
					generalLib::error('Product sku \'' . $productSku . '\' not saved');
				}
			}
		}
	}
	*/


	//###########################
	//SCAN FOR EXPIRED PROMOTIONS
	public function scanPromoExpiry() {
		$rc = true;


		$this->dbObj = new pdoDb($this->procConfig->dbConfig);

		if (!$this->dbObj->absDbConnect()) {
			return;
		}

		//SCAN FOR EXPIRED SALES PRICES - END DATE < TODAY'S DATE - DELETE THEM FROM THE XPROMO AND XPROMO PART TABLES
		$chkDate = date('Y-m-d', strtotime('- 1 day'));

		 $xpromoColl = $this->objectManager->create( $this->procConfig->xpromoModel )
		 					->getCollection()
                        	->addFieldToFilter('xpromo_to', array('to' => $chkDate));

		foreach ($xpromoColl as $expiredPromo) {
			//print_r($expiredPromo->getData());

			generalLib::mesg('');
			generalLib::mesg('====');

			$expiredXpromoCode = $expiredPromo->getXpromocode();

			$xpromopartColl = $this->objectManager->create( $this->procConfig->xpromoPartModel )
                        ->getCollection()
                        ->addFieldToFilter('xpromo_code', array('eq' => $expiredXpromoCode));

			foreach ($xpromopartColl as $expiredPromoPart) {
				//print_r($expiredPromoPart->getData());

				$expiredXpromoPart = $expiredPromoPart->getXpromopart();

				$xpromopart = $this->objectManager->create( $this->procConfig->xpromoPartModel )->load( $expiredPromoPart->getId() );

				if ($xpromopart) {
					try {
						generalLib::mesg('Deleting promo part \'' . $expiredXpromoPart . '\' for promo code \'' . $expiredXpromoCode . '\'');
						$xpromopart->delete();
					}

					catch(Exception $e) {
						generalLib::error($e->getMessage());
						generalLib::error('Promo part \'' . $expiredXpromoPart . '\' not deleted for promo code \'' . $expiredXpromoCode . '\'');
						$rc = false;
					}
				}

				if ($rc) {

					$parameters = 	array('filter' => array(
													array( 'Field' => 'plpromotioncode', 'Criteria' => $expiredXpromoCode ),
													array( 'Field' => 'plpart', 'Criteria' => $expiredXpromoPart ),
													)
												);

					$servResponseArr = $this->procConfig->wsConn->processWsUrl($this->procConfig->baseWsUrl . 'Price_List', 'ReadMultiple', $parameters);

					$actTypeRec = new stdClass();
					$actTypeRec->dbObj = $this->dbObj;
					$actTypeRec->actionTypeName = 'actiontype';
					$actTypeRec->actionType = 'd';
					$actTypeRec->extTable = 'xpromopart';
					$actTypeRec->actionTypeWhereFieldTables = array('xpromopart', 'xpromopart');
					$actTypeRec->actionTypeWhereFields = array('xpromocode' => 'TXT', 'xpromopart' => 'TXT');
					$actTypeRec->actionTypeWhereFieldVals = array('xpromocode' => $expiredXpromoCode, 'xpromopart' => $expiredXpromoPart);

					$rc = magentoDbFuncs::updActionType($actTypeRec);
				}

				if (!$rc){
					break;
				}
			}

			if ($rc) {
				generalLib::mesg('');
				generalLib::mesg('====');

				$xpromo = $this->objectManager->create( $this->procConfig->xpromoModel )->load( $expiredPromo->getId() );

				if ($xpromo) {
					try {
						generalLib::mesg('Deleting promo code \'' . $expiredXpromoCode . '\'');
						$xpromo->delete();
					}

					catch(Exception $e) {
						generalLib::error($e->getMessage());
						generalLib::error('Promo code \'' . $expiredXpromoCode . '\' not deleted');
						$rc = false;
					}
				}

				if ($rc) {
					$actTypeRec = new stdClass();
					$actTypeRec->dbObj = $this->dbObj;
					$actTypeRec->actionTypeName = 'actiontype';
					$actTypeRec->actionType = 'd';
					$actTypeRec->extTable = 'xpromo';
					$actTypeRec->actionTypeWhereFieldTables = array('xpromo');
					$actTypeRec->actionTypeWhereFields = array('xpromocode' => 'TXT');
					$actTypeRec->actionTypeWhereFieldVals = array('xpromocode' => $expiredXpromoCode);

					$rc = magentoDbFuncs::updActionType($actTypeRec);
				}
			}
		}

		$this->dbObj->absDbClose();
	}


	//###########################
	public function exportDbFeed() {
		$this->exportOrders();
	}


	//###########################
	protected function exportOrders() {

		$dateTimeUTC5MinsAgo = date('Y-m-d H:i:s', strtotime('- 5 minutes'));
		$dateTimeUTC10MinsAgo = date('Y-m-d H:i:s', strtotime('- 10 minutes'));


		$ordWebService = 'MagentoOrderHeader';

		$orderUrl = $this->procConfig->baseWsUrl . '/' . $ordWebService;

		$ordClient = new projectNTLMSoapClient($orderUrl);


		$ordLineWebService = 'MagentoOrderLine';

		$orderLineUrl = $this->procConfig->baseWsUrl . '/' . $ordLineWebService;

		$ordLineClient = new projectNTLMSoapClient($orderLineUrl);



		//print_r($this->procConfig->storeDets);

		foreach ($this->procConfig->storeDets as $storeId => $storeDet) {
			//echo 'storeId = ' . $storeId . "\n";

			$mergedOrders = $this->objectManager->create('\Magento\Sales\Model\Order')->getCollection()->addFieldToFilter('entity_id',-1);

			try {
				$orders = $this->objectManager->create('\Magento\Sales\Model\Order')->getCollection()
						->addAttributeToFilter('store_id', $storeId)
						//TEMP
						//->addAttributeToFilter('status', 'order_sent')
						->addAttributeToFilter('status', 'processing')
						->addAttributeToFilter('order_nav_export_status', array('null' => true))
						->addAttributeToFilter('created_at', array('from'  => '2018-08-28', 'to' => $dateTimeUTC10MinsAgo))
						//->addAttributeToFilter('increment_id', array('in' => array('200000359')))
						;

			}

			catch (Exception $ex) {
				generalLib::error($ex->getMessage());
			}

			//$numOrd = $orders->getSize();
			//echo 'numOrd = ' . $numOrd . "\n";

			magentoExport::mergeOrders($orders, $mergedOrders);

			try {
				$orders = $this->objectManager->create('\Magento\Sales\Model\Order')->getCollection()
						->addAttributeToFilter('store_id', $storeId)
						//TEMP
						//->addAttributeToFilter('status', 'order_sent')
						->addAttributeToFilter('status', 'pending')
						//->addAttributeToFilter('status', 'pending_payment')
						->addAttributeToFilter('order_nav_export_status', array('null' => true))
						//->addAttributeToFilter('created_at', array('from'  => '2018-08-28'))
						->addAttributeToFilter('created_at', array('from'  => '2018-08-28', 'to' => $dateTimeUTC5MinsAgo))
						//->addAttributeToFilter('increment_id', array('in' => array('2000063390')))
						->join(
        					array('payment' => 'sales_order_payment'),
        					'main_table.entity_id=payment.parent_id',
        					array('payment_method' => 'payment.method')
    						);

				$orders->addFieldToFilter('payment.method', array( 'in' => array('purchaseorder', 'checkmo', 'banktransfer') ) );

			}

			catch (Exception $ex) {
				generalLib::error($ex->getMessage());
			}

			magentoExport::mergeOrders($orders, $mergedOrders);


			/*
			foreach ($mergedOrders as $order) {
				echo 'order id = ' . $order->getIncrementId() . "\n";

				$payment = $order->getPayment();
				echo 'method = ' . $payment->getMethod() . "\n";
			}

			continue;
			*/

			foreach ($mergedOrders as $order) {

				$rc = true;

				$orderId = $order->getIncrementId();

				/*
				$parameters = array(['$ordWebService'] => $orderArr);

				//THERE HAS BEEN AN INTERMITTANT ISSUE WHERE ONCE THE ORDER IS SAVED, THE MAGENTO ORDER STATUS SOMETIMES FAILS WITH A 'The requested Payment Method is not available.' ERROR
				//THIS RESULTS IN THE ORDER BEING RE-PROCESSED AND RE-CREATED AGAIN ON THE NEXT ORDER EXPORT RUN
				//WHETHER YOU CREATE THE ORDER OR UPDATE THE STATUS FIRST, IF AN ERROR OCCURS A ROLLBACK WILL BE REQUIRED OF ONE OR THE OTHER
				//INSTEAD, WHEN THE ORDER IS RE-PROCESSED, A CHECK IS MADE TO SEE IF THE ORDER IS ALREADY PRESENT IN THE ORDERHEADER TABLE
				//IF PRESENT, AN ATTEMPT IS JUST MADE TO UPDATE THE MAGENTO ORDER STATUS AGAIN

				try {
						$response = $client->Read($parameters);
					}

				catch(Exception $e) {
					generalLib::error($e->getMessage());
					$rc = false;
				}

				$result = $this->dbObj->absDbQuery('SELECT COUNT(*) num_order_hdr FROM orderheader where mordernum = "' . $orderId . '"');

				$gotHdrCnt = false;

				while($queryResult = $this->dbObj->absDbFetchArray($result, PDO::FETCH_ASSOC)) {
					$queryResultObj = (object) $queryResult;
					//print_r($queryResultObj);

					//echo $queryResultObj->num_order . "\n";
					$gotHdrCnt = true;
				}


				if ($gotHdrCnt) {

					if ($queryResultObj->num_order_hdr > 0) {

						$ordStatus = 'order_sent';

						$rc = magentoGeneral::updateOrderStatus($orderId, '', $ordStatus, true, 'DAR DB Processor');

						if ($rc) {
							magentoGeneral::updateOrderExportStatus($orderId, $ordStatus);
						}

						continue;
					}

				} else {
					generalLib::error('Cannot get orderheader count for magento order id \'' . $orderId . '\'');
					continue;
				}
				*/


				$payment = $order->getPayment();

				//echo 'order = ' . $order->getIncrementId() . "\n";
				//print_r($order->getData());
				//continue;

				//echo 'orderid = ' . $orderId . "\n";
				//echo 'entityid = ' . $order->getId() . "\n";


				$customerId = $order->getCustomerId();

				$shippingAddr = $order->getShippingAddress();
				//print_r($shippingAddr->getData());


				$orderType = strtolower($order->getOrderType());

				//echo 'orderType = ' . $orderType . "\n";


				if ($orderType == 'b2c') {
					$b2c = 'Y';
					$delAdd = '999';
				} else {
					$b2c = 'N';
					$delAdd = intval( str_replace('custom_addr_id', '', $shippingAddr->getCustomAddrId() ) );
				}

				//echo 'delAdd = ' . $delAdd . "\n";

				$orderArr = array();

				$orderArr['id'] = $orderId;

				try {
					$customer = $this->objectManager->create('Magento\Customer\Model\Customer')->load($customerId);

					if ($customer) {
						$orderArr['cust'] = $customer->getCust();
					}
				}

				catch(Exception $e) {
					generalLib::error($e->getMessage());
				}

				$orderArr['deladd'] = $delAdd;

				$orderArr['cordernum'] = $order->getCustomerOrderNo();

				$orderArr['mordernum'] = $orderId;

				//die();

				$odate = $order->getCreatedAt();
				//generalLib::mesg('order date created at = ' . $odate);

				$dateTimeSplitPos = strpos($odate, ' ');

				if ($dateTimeSplitPos !== false) {
					$odate = substr($odate, 0, ( strlen($odate) - ( $dateTimeSplitPos - 1) ) );
					//generalLib::mesg('order date substr = ' . $odate);
				}

				$odate .= 'T' . date('H:i:s', magentoGeneral::getLocalTimeStamp( $order->getCreatedAt() ) );
				//generalLib::mesg('order date = ' . $odate);
				//die();

				//$odate = '2018-08-18T08:19:32';
				$orderArr['odate'] = $odate;

				$orderArr['status'] = 'WEB';

				$orderArr['cnotes'] = $order->getOnestepcheckoutCustomercomment();

				$giftMessageId = $order->getGiftMessageId();

				if ( is_numeric( $giftMessageId ) ) {
					$giftMesg = $this->objectManager->create('\Magento\GiftMessage\Model\Message')->load( $giftMessageId );

					$orderArr['giftnotefrom'] = $giftMesg->getSender();
					$orderArr['giftnoteto'] = $giftMesg->getRecipient();
					$orderArr['giftnote'] = $giftMesg->getMessage();
				}

				$orderArr['b2c'] = $b2c;

				if ($b2c == 'Y') {
					$addrName = trim($shippingAddr->getPrefix());

					if (!empty($addrName)) {
						$addrName .= ' ';
					}

					$addrName .= trim($shippingAddr->getFirstname()) . ' ' . trim($shippingAddr->getLastname());

					$streetArr = $shippingAddr->getStreet();

					$orderArr['b2caddress'] = $addrName;

					$orderArr['b2caddress1'] = $streetArr[0];

					if (strlen( $orderArr['b2caddress1']) > 50) {
						$orderArr['b2caddress1'] = substr($orderArr['b2caddress1'], 0, 50);
					}

					if ( isset($streetArr[1] ) ) {

						$orderArr['b2caddress2'] = $streetArr[1];

						if (strlen( $orderArr['b2caddress2']) > 50) {
							$orderArr['b2caddress2'] = substr($orderArr['b2caddress2'], 0, 50);
						}

					}

					$orderArr['b2caddress3'] = $shippingAddr->getCity();

					if (strlen( $orderArr['b2caddress3']) > 50) {
						$orderArr['b2caddress3'] = substr($orderArr['b2caddress3'], 0, 50);
					}


					$orderArr['b2caddress4'] = $shippingAddr->getRegion();

					if (strlen( $orderArr['b2caddress4']) > 50) {
						$orderArr['b2caddress4'] = substr($orderArr['b2caddress4'], 0, 50);
					}


					$countryCode = $shippingAddr->getCountry();

					if ($countryCode != '') {
						$country = Mage::getModel('directory/country')->loadByCode($countryCode);
						$countryName = $country->getName();
					} else {
						$countryName = '';
					}

					$orderArr['b2caddress5'] = $countryName;

					$orderArr['b2cpostcode'] = $shippingAddr->getPostcode();

					$orderArr['b2ctelephone'] = $shippingAddr->getTelephone();

					$orderArr['b2cspecialinstruction'] = $order->getSpecialDeliveryInstructions();

					$orderArr['b2cemail'] = $order->getCustomerEmail();

				}

				//magentoDbFuncs::buildInsertStmt('deliveryMethod', $deliveryMethod, 'TXT', $ordHdrFldNames, $ordHdrFldValues);


				$payment = $order->getPayment();

				$paymentMethod = $payment->getMethod();

				switch($paymentMethod) {
					case 'worldpay':
					case 'worldpay_cc':
										$paymentType = '1'; // credit card
										break;

					case 'banktransfer':
										$paymentType = '2'; // bacs
										break;

					default:
										$paymentType = '0'; // pay on account
										break;
				}

				$orderArr['paymenttype'] = $paymentType;

				if ( ( $paymentMethod == 'worldpay' ) || ( $paymentMethod == 'worldpay_cc') ) {

					$orderArr['creditcardtype'] = $payment->getCcType();

					$orderArr['creditcardtransid'] = $payment->getLastTransId();

					$orderArr['creditcardavs'] = $payment->getCcAvsStatus();

				}

				$orderArr['actiontype'] = 'U';

				//print_r($orderArr);
				//die();

				$parameters = array($ordWebService => $orderArr);

				//print_r($parameters);

				try {
					$response = $ordClient->Create($parameters);
				}

				catch(Exception $e) {
					generalLib::error($e->getMessage());
					$rc = false;
				}

				if ($rc) {

					$gotResponse = count( (array)$response->MagentoOrderHeader);

					if ($gotResponse == 0) {
						generalLib::error('No Order record returned from Nav');
						continue;
					}

				} else {

					continue;

				}

				foreach($response as $navSalesOrder) {
					$navOrderKey = $navSalesOrder->Key;
				}

				///NOTE: NEED TO AD AN EXTRA ORDER LINE FOR SHIPPING

				//NOTE: NEED A SHIPMENT TABLE, WHICH WILL BE USED TO INDICATE WHICH ITEMS HAVE BEEN DELIVERED AND A TRACKING REF TO CREATE A SHIPMENT
				//WHEN ALL ITEMS ARE SHIPPED THE ORDER WILL BE SET TO COMPLETE

				$orderItems = $order->getAllItems();

				$itemNum = 1;

				$orderLineList = array();

				foreach($orderItems AS $orderItem) {
					//print_r($orderItem->getData());
					//continue;

					$prodObj = new stdClass();
					$prodObj->sku = $orderItem->getSku();

					$shadeproduct = false;

					try {
						$product = magentoProd::getMagProduct($prodObj, 'sku', 'sku', 'magId', true);

						if ($product) {
							$shadeRangeName = $product->getShadeRangeName();

							if ( !empty($shadeRangeName) ) {
								$shadeproduct = true;
							}

						}

					}

					catch(Exception $e) {
						generalLib::error($e->getMessage());
					}

					//echo 'shadeproduct = ' . $shadeproduct . "\n";

					$taxPerc = $orderItem->getTaxPercent();

					//echo 'taxPerc = ' . $taxPerc . "\n";

					if ($taxPerc != 0) {
						$vatCode = 'S';
					} else {
						$vatCode = 'Z';
					}

					if ($orderItem->getActiveDiscounts() != '') {
						$nunitPrice = $orderItem->getPrice();
						$discPerc = 0;
						$punitPrice = $nunitPrice;
					} else {
						$orderItmPrice = $orderItem->getOriginalPrice();

						if ($orderItmPrice == 0) {
							$orderItmPrice = $orderItem->getPrice();
						}

						$discAmt = $orderItem->getDiscountAmount();

						if ($orderItmPrice > 0) {
							$discPerc = ( $discAmt / $orderItmPrice ) * 100;
						} else {
							$discPerc = 0;
						}

						$nunitPrice = $orderItmPrice - $discAmt;
						$punitPrice = 0;
					}


					$note = '';
					$partNum = '';

					$orderItemProdOptions = $orderItem->getProductOptions();

					if (isset($orderItemProdOptions['options'])) {

						foreach($orderItemProdOptions['options'] as $orderItemProdOption) {

							switch( $orderItemProdOption['label'] ) {
								case 'Note':
												$note = $orderItemProdOption['value'];
												break;

								case 'Partnum':
												$partNum = $orderItemProdOption['value'];
												break;
							}

						}

					}

					if ($partNum == '') {
						$partNum = $orderItem->getSku();
					}

					$orderLineArr = array();

					$orderLineArr['id'] = $orderId;

					$orderLineArr['item'] = $itemNum;

					$orderLineArr['part'] = $partNum;


					$itemDesc = $orderItem->getName();

					if ( strlen($itemDesc) > 60 ) {
						$itemDesc = substr($itemDesc, 0, 60);
					}

					$orderLineArr['descr'] = $itemDesc;

					$orderLineArr['qty'] = $orderItem->getQtyOrdered();

					$orderLineArr['unitprice'] = $orderItem->getOriginalPrice();

					$orderLineArr['discount'] = $discPerc;

					$orderLineArr['nunitprice'] = $nunitPrice;

					$orderLineArr['value'] = $orderItem->getRowTotal();

					$orderLineArr['vatcode'] = $vatCode;

					$orderLineArr['vatrate'] = $taxPerc;

					$orderLineArr['vatvalue'] = $orderItem->getTaxAmount();

					$orderLineArr['goodstotal'] = $orderItem->getRowTotalInclTax();

					$orderLineArr['currency'] = $order->getOrderCurrencyCode();

					$orderLineArr['currate'] = $order->getStoreToOrderRate();

					if ($note != '') {
						$orderLineArr['note'] = $note;
					}

					$orderLineArr['punitprice'] = $punitPrice;

					$orderLineArr['shadeproduct'] = $shadeproduct;

					$orderLineArr['actiontype'] = 'U';

					$orderLineList['MagentoOrderLine'][]  = $orderLineArr;

					$itemNum++;
				}


				$shippingDescription = $order->getShippingDescription();

				$shippingDescArr = explode('- ' , $shippingDescription);

				$prodObj = new stdClass();
				$prodObj->name = $shippingDescArr[1];

				$product = magentoProd::getMagProduct($prodObj, 'name', 'name', 'magId', true);

				if ($product) {
					$shipPartNum = $product->getSku();
				} else {
					$shipPartNum = 'UNKNOWN';
				}

				$shipDesc = $shippingDescArr[1];


				//echo 'shipPartNum = ' . $shipPartNum . "\n";
				//echo 'shipDesc = ' . $shipDesc . "\n";

				/*
				switch($shippingDescArr[1]) {
					case 'B2C CARRIAGE CHARGE':
											$shipPartNum = 'B2C';
											break;

					case '24HR CARRIAGE':
											$shipPartNum = '24HR';
											break;

					case 'FREE SHIPPING':
											$shipPartNum = 'FREE';
											break;

					case 'PARCELFORCE CARRIAGE CHARGE':
											$shipPartNum = 'PARCELFORCE';
											break;

					default:
											$shipPartNum = 'UNKNOWN';
											$shipDesc = 'UNKNOWN';
											break;
				}

				if ($shipPartNum != 'UNKNOWN'){
					$shipDesc = $shippingDescArr[1];
				}
				*/


				if ($order->getShippingAmount() != 0) {
					$taxPerc = ( $order->getShippingTaxAmount() / $order->getShippingAmount() ) * 100;
				} else {
					$taxPerc = 0;
				}

				//echo 'taxPerc = ' . $taxPerc . "\n";

				if ($taxPerc != 0) {
					$vatCode = 'S';
				} else {
					$vatCode = 'Z';
				}

				$orderLineArr = array();

				$orderLineArr['id'] = $orderId;

				$orderLineArr['item'] = $itemNum;

				$orderLineArr['part'] = $shipPartNum;

				if ( strlen($shipDesc) > 60 ) {
					$shipDesc = substr($shipDesc, 0, 60);
				}

				$orderLineArr['descr'] = $shipDesc;

				$orderLineArr['qty'] = '1';

				$orderLineArr['unitprice'] = $order->getShippingAmount();

				$orderLineArr['discount'] = '0';

				$orderLineArr['nunitprice'] = $order->getShippingAmount();

				$orderLineArr['value'] = $order->getShippingAmount();

				$orderLineArr['vatcode'] = $vatCode;

				$orderLineArr['vatrate'] = $taxPerc;

				$orderLineArr['vatvalue'] = $orderItem->getShippingTaxAmount();

				$orderLineArr['goodstotal'] = $orderItem->getShippingInclTax();

				$orderLineArr['currency'] = $order->getOrderCurrencyCode();

				$orderLineArr['currate'] = $order->getStoreToOrderRate();

				$orderLineArr['punitprice'] = '0';

				$orderLineArr['shadeproduct'] = false;

				$orderLineArr['actiontype'] = 'U';

				$orderLineList['MagentoOrderLine'][] = $orderLineArr;

				$parameters = array('MagentoOrderLine_List' => $orderLineList);

				//print_r($parameters);

				try {
						$response = $ordLineClient->CreateMultiple($parameters);
						//print_r($response);
				}

				catch(Exception $e) {
					generalLib::error($e->getMessage());
					$rc = false;
				}

				if ( $rc ) {
					$gotResponse = count( (array)$response->MagentoOrderLine_List->MagentoOrderLine);

					if ($gotResponse == 0) {
						generalLib::error('No Order line records returned from Nav');
						$rc = false;
						continue;
					}

					if ( $gotResponse != $itemNum ) {
						generalLib::error('Number of Order line records \'' . $itemNum . '\' does not equal Nav response \'' . $gotResponse . '\'');
						$rc = false;
					}
				}

				if ($rc) {

					$ordStatus = 'order_sent';

					//TEMP - WHILST USING TO MIRROR LIVE M2
					//$rc = magentoGeneral::updateOrderStatus($orderId, '', $ordStatus, true, 'DAR NAV Processor');

					if ($rc) {
						//TEMP - WHILST USING TO MIRROR LIVE M2
						//magentoGeneral::updateOrdeExportStatus($orderId, $ordStatus);
						$this->updateOrderNavExportStatus($orderId, $ordStatus);
					}

				} else {
					$delRecord = new stdClass();
					$delRecord->Key = $navOrderKey;

					try {
						generalLib::mesg('Deleting order id \'' . $orderId . '\'');
						$delResponse = $ordClient->Delete( $delRecord );
					}

					catch (Exception $e) {
						generalLib::error('Order ID ' . $orderId . ':');
	                	generalLib::error($e->getMessage());
	                	$rc = false;
	            	}

	            	$retArr = $this->procConfig->wsConn->chkDeleteResponse($delResponse, 'Delete', $delRecord->Key);

	            	if ($retArr['rc']) {
	            		generalLib::error('Order id \'' . $orderId . '\' deleted in NAV');
	            	}
				}

				//die();
			}

		}
			//die();
	}


	//###########################
	protected function updateOrderNavExportStatus($ordNum, $ordExpStatus) {
		$rc = true;

		$order = $this->objectManager->create('\Magento\Sales\Model\Order')->loadByIncrementId($ordNum);

		if (is_numeric($order->getEntityId())) {
			$order->setOrderNavExportStatus($ordExpStatus);

			try {
					generalLib::mesg('Setting nav order export status to \'' . $ordExpStatus . '\'  for order \'' . $ordNum . '\'');
					$order->save();
			}

			catch(Exception $e) {
				generalLib::mesg($e->getMessage());
				$rc = false;
			}
		} else {
			generalLib::error('Cannot update order export status. Order \'' . $ordNum . '\' not found');
			$rc = false;
		}

		return $rc;
	}


	//###########################
	function setWsRecMagId($magId, $wsRec, $wsMagIdFieldName, &$retArr) {
		$updMagId = false;

		if ( empty($wsRec->$wsMagIdFieldName)) {
			$updMagId = true;
		} else {

			if ($wsRec->$wsMagIdFieldName != $magId) {
				$updMagId = true;
			}

		}

		if ($updMagId) {
			$retArr['updWS_' . $wsMagIdFieldName] = $magId;
		}

		//return $retArr;
	}
}

?>
