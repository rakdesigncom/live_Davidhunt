<?php

/*
SUGGESTIONS:
CONCATENATE THE PRODUCT DESCRIPTION INTO ONE LINE, PUTTING HTML LINE BREAKS BETWEEN EACH ONE '<br />'
*/

class darDbProcessor {
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

		$feedTables = array();
		$feedTables['ATTRIBUTES'] = array('attributeset', 'attributegroup', 'attribute', 'productattribute');
		$feedTables['CATEGORIES'] = array('cat_categories');
		$feedTables['CUSTOMERS'] = array('users', 'waddresses');
		$feedTables['LEAFLETS'] = array('ildocument');
		$feedTables['LOCAL_STOCKISTS'] = array('localstockists');
		$feedTables['ORDERS'] = array('orderheader');

		//TEMP - DISABLE Stock and shaderanges import WHILT TESTING BRAID/FRINGE/LINER CHANGES
		$feedTables['PRODUCTS'] = array('stock', 'pricelist', 'xrrp', /* 'productcategory', */ 'part_related',
										'shaderanges', 'shadelinerprices', 'storedata');

		$feedTables['BESPOKE_SHADES'] = array('shadesilkcolours', 'shadestyles', 'shadebraid', 'shadebraidprices', 'shadefringe', 'shadefringeprices',
									'shadeaccessory', 'dhlcable', 'dhlcableprices', 'dhlglass', 'dhlglassprices', 'dhlinnerpaint', 'dhlinnerpaintprices',
									'dhlouterpaint', 'dhlouterpaintprices'
									);

		$feedTables['XPROMOS'] = array('xpromo', 'xpromopart');
		$feedTables['XWEBLAMPS'] = array('xweblampcategory', 'xweblamp');

		//THESE ARE USED FOR BESPOKE IMPORTS - NOT REGULAR CRONTAB IMPORTS
		$feedTables['ADDRESSES'] = array('waddresses');
		$feedTables['ATTRIBUTE_SET'] = array('attributeset');
		$feedTables['ATTRIBUTE_GROUP'] = array('attributegroup');
		$feedTables['ATTRIBUTE'] = array('attribute');
		$feedTables['CABLE'] = array('dhlcable');
		$feedTables['CABLE_PRICES'] = array('dhlcableprices');
		$feedTables['PART_RELATED'] = array('part_related');
		$feedTables['GLASS'] = array('dhlglass');
		$feedTables['GLASS_PRICES'] = array('dhlglassprices');
		$feedTables['INNER_PAINT'] = array('dhlinnerpaint');
		$feedTables['INNER_PAINT_PRICES'] = array('dhlinnerpaintprices');
		$feedTables['OUTER_PAINT'] = array('dhlouterpaint');
		$feedTables['OUTER_PAINT_PRICES'] = array('dhlouterpaintprices');
		$feedTables['PRICE_LIST'] = array('pricelist');
		$feedTables['PRODUCT_ATTRIBUTE'] = array('productattribute');
		//$feedTables['PRODUCT_CATEGORY'] = array('productcategory');
		$feedTables['SHADE_RANGE'] = array('shaderanges');
		$feedTables['ACCESSORIES'] = array('shadeaccessory');

		$feedTables['BRAID'] = array('shadebraid');
		$feedTables['BRAID_PRICES'] = array('shadebraidprices');

		$feedTables['FRINGE'] = array('shadefringe');
		$feedTables['FRINGE_PRICES'] = array('shadefringeprices');

		$feedTables['SHADE_LINER_PRICES'] = array('shadelinerprices');
		$feedTables['SILK_COLOURS'] = array('shadesilkcolours');
		$feedTables['STYLES'] = array('shadestyles');
		$feedTables['STOCK'] = array('stock');
		$feedTables['STOCK_LEVEL'] = array('stocklevel');
		$feedTables['STORE_DATA'] = array('storedata');
		$feedTables['USER'] = array('users');
		$feedTables['XPROMO'] = array('xpromo');
		$feedTables['XPROMO_PART'] = array('xpromopart');
		$feedTables['XRRP'] = array('xrrp');
		$feedTables['XWEBLAMP'] = array('xweblamp');
		$feedTables['XWEBLAMP_CATEGORY'] = array('xweblampcategory');

		$this->procConfig->feedTables = $feedTables;

		//CUSTOMER MAGENTO FIELDS
		$this->procConfig->custMagAttrs = array('accTitle' => 'prefix', 'firstname' => 'firstname', 'lastname' => 'lastname', 'email' => 'email');


		//CUSTOMER CUSTOM ATTRIBUTES
		$this->procConfig->custCustomAttrs = array('cust' => 'T', 'cdisc' => 'T', 'parkerdisc' => 'T', 'dhldisc' => 'T', 'conhold' => 'B',
												'features' => 'T', 'is_active' => 'B', 'downloadimages' => 'B', 'contract' => 'B', 'validateduser' => 'B',
												'weborder' => 'B', 'downloadfiles' => 'B', 'priority' => 'T', 'b2c' => 'B',
												'autohold' => 'B', 'dhl_permission' => 'B', 'customer_friendly' => 'B', 'proforma' => 'B', 'bespoke' => 'B'
											);

		//CUSTOMER ADDRESS ATTRIBUTES
		$this->procConfig->custAddrAttrs = array('addritem' => 'custom_addr_id', 'custname' => 'company', 'firstname' => 'firstname', 'lastname' => 'lastname',
														'address1' => 'street_1', 'address2' => 'street_2', 'address3' => 'city',
														'address4' => 'region', 'postcode' => 'postcode', 'adcountry' => 'country_id',
														'adcurrency' => 'adcurrency', 'adtelephone' => 'telephone',
 														'advatcat' => 'advatcat', 'capltype' => 'capltype'
														);


		//LEAFLET CUSTOM FIELDS
		$this->procConfig->leafletMagAttrs = array('file_non_eng' => 'file_non_eng', 'file_eng' => 'file_eng');


		//PRODUCT MAGENTO FIELDS
		$this->procConfig->stockMagSkuAttr = array('partnum' => 'sku');


		//PRODUCT MAGENTO PRICE FIELD
		$this->procConfig->stockPriceMagAttr = array('price' => 'price');


		//PRODUCT MAGENTO PRODUCT NAME FIELD
		$this->procConfig->stockMagNameAttr = array('partdesc' => 'name');


		//PRODUCT MAGENTO PRODUCT META TITLE FIELD
		$this->procConfig->stockMagMetaTitleAttr = array('metatitle' => 'meta_title');


		//PRODUCT CUSTOM FIELDS
		$this->procConfig->stockCustomAttrs = array('material' => 'T', 'custref' => 'T', 'costhead' => 'T', 'xpallet' => 'B', 'restricted' => 'B', 'leaflet_only' => 'B',
													'shade_range_basecode' => 'T');


		//PRODUCT VAT CUSTOM FIELD
		$this->procConfig->stockCustomVatAttr = array('vatcode' => 'T');


		//PRODUCT SHADE RANGE PRODUCT CUSTOM FIELD
		$this->procConfig->stockCustomShadeRangeAttr = array('shade_range_product' => 'B');


		//PRODUCT XRRP MAGENTO FIELDS

		$this->procConfig->xrrpMagPackSizeAttr = array('packsize' => 'packsize');

		$this->procConfig->xrrpMagAttrs = array('srp' => 'srp', 'rrp' => 'rrp');


		//PRODUCT XPROMO CUSTOM FIELDS
		$this->procConfig->xpromoMagAttrs = array('xpromocode' => 'xpromo_code', 'xpromodesc' => 'xpromo_desc', 'xpromoqty' => 'xpromo_qty',
													'xpromotype' => 'xpromo_type', 'xpromocust' => 'xpromo_cust', 'xpromofreeqty' => 'xpromo_free_qty');

		$this->procConfig->xpromoCustomAttrs = array('xpromofrom' => 'D', 'xpromoto' => 'D');

		$this->procConfig->xpromoCustomAttrMappings = array('xpromofrom' => 'xpromo_from', 'xpromoto' => 'xpromo_to');

		//PRODUCT XPROMOPART CUSTOM FIELDS
		$this->procConfig->xpromoPartMagAttrs = array('xpromocode' => 'xpromo_code', 'xpromopart' => 'xpromo_part', 'xpromounitprice' => 'xpromo_unit_price');


		//PRODUCT XWEBLAMPCATEGORY CUSTOM FIELDS
		$this->procConfig->xWebLampCategoryCustomAttrs = array('categoryid' => 'T');

		//PRODUCT XWEBLAMPCATEGORY CUSTOM FIELD MAPPINGS
		$this->procConfig->xWebLampCategoryCustomAttrMappings = array('categoryid' => 'general_cat_id');


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
		//print_r($this->procConfig->storeDets);

		$this->procConfig->darToMagStoreMappings = array('1' => '2', '2' => '1', '3' => '3', '4' => '4', '5' => '5', '6' => '6', '7' => '7', '8' => '8', '9' => '9', '10' => '10', '11' => '11');

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


		$this->procConfig->googleApiKey = $this->objectManager->get('\Magento\Framework\App\Config\ScopeConfigInterface')->getValue('stockistlocator/locatorconfiguration/access_token');

		$this->procConfig->mediaFolder = $this->objectManager->get('Magento\Framework\App\Filesystem\DirectoryList')->getPath('media');

		$this->procConfig->mediaFolderImgImport =  $this->procConfig->mediaFolder . '/import/images';
	}


	public function processAction() {
		global $argv;

		$gotAction = false;

		if ($this->procConfig->action == 'IMPORT' || $this->procConfig->action == 'ALL') {
			$this->processDbFeed();
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
	public function processDbFeed() {
		//print_r($this->procConfig->dbConfig);

		if ( !magentoImport::gotMagFeedLock($this->procConfig->feedType, $this->procConfig->lockDir) ) {
			return;
		}


		/*
		//TEMP - TO BE RESTORED
		$magFeedIdxLock = magentoIndex::getMagFeedIdxLock();

		if (!$magFeedIdxLock['gotLock']) {
			return;
		}
		*/

		$this->dbObj = new pdoDb($this->procConfig->dbConfig);

		if (!$this->dbObj->absDbConnect()) {
			return;
		}


		$this->procConfig->braid_list = $this->getShadeBraids();

		if ( count($this->procConfig->braid_list) == 0 ) {
			generalLib::Error('Cannot define shade braid list');
			return;
		}


		$this->procConfig->fringe_list = $this->getShadeFringes();

		if ( count($this->procConfig->fringe_list) == 0 ) {
			generalLib::Error('Cannot define shade fringe list');
			return;
		}

		$this->procConfig->shadeLiners = $this->getShadeLiners();

		if ( count($this->procConfig->shadeLiners) == 0 ) {
			generalLib::Error('Cannot define shade liners');
			return;
		}

		$this->procConfig->cable_list = $this->getDhlCables();

		if ( count($this->procConfig->cable_list) == 0 ) {
			generalLib::Error('Cannot define DHL Cables');
			return;
		}

		$this->procConfig->glass_list = $this->getDhlGlass();

		if ( count($this->procConfig->glass_list) == 0 ) {
			generalLib::Error('Cannot define DHL Glass list');
			return;
		}

		$this->procConfig->innerpaint_list = $this->getDhlInnerPaint();

		if ( count($this->procConfig->innerpaint_list) == 0 ) {
			generalLib::Error('Cannot define DHL Inner Paint list');
			return;
		}

		$this->procConfig->outerpaint_list = $this->getDhlOuterPaint();

		if ( count($this->procConfig->outerpaint_list) == 0 ) {
			generalLib::Error('Cannot define DHL Outer Paint list');
			return;
		}

		//WITHOUT THIS STATEMENT A 'Heterogeneous queries require the ANSI_NULLS and ANSI_WARNINGS options to be set for the connection. This ensures consistent query semantics. Enable these options and then reissue your query.' ERROR IS RETURNED.
		// In short, it means that if you're running a query against a Linked Server that isn't a SQL Server box, then more than likely you'll need to turn these two features on to standardize the way certain comparison operators are handled across different databases.
		//$dbObj->absDbQuery('SET ANSI_NULLS ON; SET ANSI_WARNINGS ON');

		if (!isset($this->procConfig->feedTables[ $this->procConfig->feedType ])) {
			generalLib::error('Feed type \'' . $this->procConfig->feedType . '\' not found');
		}

		foreach($this->procConfig->feedTables[ $this->procConfig->feedType ] as $funcFeedTable) {
			$funcFeedTable = ucfirst($funcFeedTable);
			$processFunc = 'process' . $funcFeedTable . 'Table';

			if (method_exists($this, $processFunc)) {
				generalLib::mesg('Processing ' . $funcFeedTable);
				$actionTypeName = 'actiontype';		//DEFAULT

				switch($funcFeedTable) {

					case 'Attribute':
								$query = 'SELECT ats.set_mag_id, ats.set_name, ag.grp_mag_id, ag.grp_name, a.* FROM attributeset ats, attributegroup ag, attribute a WHERE ats.set_id = a.attr_set_id and ag.grp_id = a.attr_grp_id AND a.attr_action_type in ("U", "D") ORDER BY a.attr_id';
								//$query = 'SELECT ats.set_mag_id, ats.set_name, ag.grp_mag_id, ag.grp_name, a.* FROM attributeset ats, attributegroup ag, attribute a WHERE ats.set_id = a.attr_set_id and ag.grp_id = a.attr_grp_id AND a.attr_action_type in ("-") ORDER BY a.attr_id';
								$actionTypeName = 'attr_action_type';
								$actionTypeWhereFieldTables = array('attribute');
								$actionTypeWhereFields = array('attr_id' => 'INT');
								break;

					case 'Attributegroup':
								$query = 'SELECT ats.set_mag_id, ats.set_name, ag.*  FROM attributegroup ag, attributeset ats WHERE ats.set_id = ag.grp_set_id AND grp_action_type in ("U", "D") ORDER BY ag.grp_id';
								//$query = 'SELECT ats.set_mag_id, ats.set_name, ag.*  FROM attributegroup ag, attributeset ats WHERE ats.set_id = ag.grp_set_id AND grp_action_type in ("-") ORDER BY ag.grp_id';

								$actionTypeName = 'grp_action_type';
								$actionTypeWhereFieldTables = array('attributegroup');
								$actionTypeWhereFields = array('grp_id' => 'INT');
								break;

					case 'Attributeset':
								$query = 'SELECT * FROM attributeset WHERE set_action_type in ("U","D") ORDER BY set_id';
								//$query = 'SELECT * FROM attributeset WHERE set_action_type in ("-") ORDER BY set_id';
								$actionTypeName = 'set_action_type';
								$actionTypeWhereFieldTables = array('attributeset');
								$actionTypeWhereFields = array('set_id' => 'INT');
								break;

					case 'Cat_categories':
								$query = 'SELECT * FROM cat_categories WHERE actiontype in ("U", "D") ORDER BY id';
								//$query = 'SELECT * FROM cat_categories WHERE actiontype in ("-") ORDER BY id';
								$actionTypeWhereFieldTables = array('cat_categories');
								$actionTypeWhereFields = array('id' => 'INT');
								break;

					case 'Dhlcable':
								$query = 'SELECT * FROM dhlcable WHERE actiontype in ("U", "D") ORDER BY cable_code';
								//$query = 'SELECT * FROM dhlcable WHERE actiontype in ("-") ORDER BY cable_code';
								//$query = 'SELECT * FROM dhlcable WHERE actiontype in ("-", "U", "D") ORDER BY cable_code';
								$actionTypeWhereFieldTables = array('dhlcable');
								$actionTypeWhereFields = array('cable_code' => 'TXT');
								break;

					case 'Dhlcableprices':
								$query = 'SELECT * FROM dhlcableprices WHERE actiontype in ("U", "D") ORDER BY dhl_partnum, cable_code';
								//$query = 'SELECT * FROM dhlcableprices WHERE actiontype in ("-") ORDER BY dhl_partnum, cable_code';
								//$query = 'SELECT * FROM dhlcableprices WHERE actiontype in ("-","U","D") ORDER BY dhl_partnum, cable_code';
								//$query = 'SELECT * FROM dhlcableprices WHERE actiontype in ("-") AND dhl_partnum = "EP0199" ORDER BY dhl_partnum, cable_code';
								$actionTypeWhereFieldTables = array('dhlcableprices', 'dhlcableprices');
								$actionTypeWhereFields = array('dhl_partnum' => 'TXT', 'cable_code' => 'TXT');
								break;

					case 'Dhlglass':
								$query = 'SELECT * FROM dhlglass WHERE actiontype in ("U", "D") ORDER BY glass_code';
								//$query = 'SELECT * FROM dhlglass WHERE actiontype in ("-") ORDER BY glass_code';
								//$query = 'SELECT * FROM dhlglass WHERE actiontype in ("-","U", "D") ORDER BY glass_code';
								$actionTypeWhereFieldTables = array('dhlglass');
								$actionTypeWhereFields = array('glass_code' => 'TXT');
								break;

					case 'Dhlglassprices':
								$query = 'SELECT * FROM dhlglassprices WHERE actiontype in ("U", "D") ORDER BY dhl_partnum, glass_code';
								//$query = 'SELECT * FROM dhlglassprices WHERE actiontype in ("-") ORDER BY dhl_partnum, glass_code';
								//$query = 'SELECT * FROM dhlglassprices WHERE actiontype in ("-", "U", "D") ORDER BY dhl_partnum, glass_code';
								//$query = 'SELECT * FROM dhlglassprices WHERE actiontype in ("-") ORDER BY dhl_partnum, glass_code';
								//$query = 'SELECT * FROM dhlglassprices WHERE actiontype in ("-") AND dhl_partnum = "EP0199" ORDER BY dhl_partnum, glass_code';
								$actionTypeWhereFieldTables = array('dhlglassprices', 'dhlglassprices');
								$actionTypeWhereFields = array('dhl_partnum' => 'TXT', 'glass_code' => 'TXT');
								break;

					case 'Dhlinnerpaint':
								$query = 'SELECT * FROM dhlinnerpaint WHERE actiontype in ("U", "D") ORDER BY paint_code';
								//$query = 'SELECT * FROM dhlinnerpaint WHERE actiontype in ("-") ORDER BY paint_code';
								//$query = 'SELECT * FROM dhlinnerpaint WHERE actiontype in ("-", "U", "D") ORDER BY paint_code';
								$actionTypeWhereFieldTables = array('dhlinnerpaint');
								$actionTypeWhereFields = array('paint_code' => 'TXT');
								break;

					case 'Dhlinnerpaintprices':
								$query = 'SELECT * FROM dhlinnerpaintprices WHERE actiontype in ("U", "D") ORDER BY dhl_partnum, paint_code';
								//$query = 'SELECT * FROM dhlinnerpaintprices WHERE actiontype in ("-") ORDER BY dhl_partnum, paint_code';
								//$query = 'SELECT * FROM dhlinnerpaintprices WHERE actiontype in ("-","U", "D") ORDER BY dhl_partnum, paint_code';
								//$query = 'SELECT * FROM dhlinnerpaintprices WHERE actiontype in ("-") ORDER BY dhl_partnum, paint_code';
								//$query = 'SELECT * FROM dhlinnerpaintprices WHERE actiontype in ("-") AND dhl_partnum = "EP0199" ORDER BY dhl_partnum, paint_code';
								$actionTypeWhereFieldTables = array('dhlinnerpaintprices', 'dhlinnerpaintprices');
								$actionTypeWhereFields = array('dhl_partnum' => 'TXT', 'paint_code' => 'TXT');
								break;

					case 'Dhlouterpaint':
								$query = 'SELECT * FROM dhlouterpaint WHERE actiontype in ("U", "D") ORDER BY paint_code';
								//$query = 'SELECT * FROM dhlouterpaint WHERE actiontype in ("-") ORDER BY paint_code';
								//$query = 'SELECT * FROM dhlouterpaint WHERE actiontype in ("-","U", "D") ORDER BY paint_code';
								$actionTypeWhereFieldTables = array('dhlouterpaint');
								$actionTypeWhereFields = array('paint_code' => 'TXT');
								break;

					case 'Dhlouterpaintprices':
								$query = 'SELECT * FROM dhlouterpaintprices WHERE actiontype in ("U", "D") ORDER BY dhl_partnum, paint_code';
								//$query = 'SELECT * FROM dhlouterpaintprices WHERE actiontype in ("-") ORDER BY dhl_partnum, paint_code';
								//$query = 'SELECT * FROM dhlouterpaintprices WHERE actiontype in ("-","U", "D") ORDER BY dhl_partnum, paint_code';
								//$query = 'SELECT * FROM dhlouterpaintprices WHERE actiontype in ("-") ORDER BY dhl_partnum, paint_code';
								//$query = 'SELECT * FROM dhlouterpaintprices WHERE actiontype in ("-") AND dhl_partnum = "EP0199" ORDER BY dhl_partnum, paint_code';
								$actionTypeWhereFieldTables = array('dhlouterpaintprices', 'dhlouterpaintprices');
								$actionTypeWhereFields = array('dhl_partnum' => 'TXT', 'paint_code' => 'TXT');
								break;

					case 'Ildocument':
								$query = 'SELECT * FROM ildocument WHERE actiontype in ("U", "D")';
								//$query = 'SELECT * FROM ildocument WHERE actiontype in ("-")';
								$actionTypeWhereFieldTables = array('ildocument', 'ildocument', 'ildocument');
								$actionTypeWhereFields = array('sku' => 'TXT', 'IL' => 'TXT', 'file_eng' => 'TXT');
								break;

					case 'Localstockists':
								$query = 'SELECT * FROM localstockists WHERE actiontype in ("U", "D") ORDER BY stockist_id';
								//$query = 'SELECT * FROM localstockists WHERE actiontype in ("-") ORDER BY stockist_id';
								$actionTypeWhereFieldTables = array('localstockists');
								$actionTypeWhereFields = array('stockist_id' => 'INT');
								break;

					case 'Orderheader':
								$query = 'SELECT * FROM orderheader WHERE status = "LIVE" AND actiontype = "U"';
								$actionTypeWhereFieldTables = array('orderheader');
								$actionTypeWhereFields = array('id' => 'INT');
								break;

					case 'Part_related':
								$query = 'SELECT * FROM part_related WHERE actiontype in ("U", "D")';
								//$query = 'SELECT * FROM part_related WHERE actiontype in ("-")';
								//$query = 'SELECT * FROM part_related WHERE actiontype in ("-")';
								$actionTypeWhereFieldTables = array('part_related');
								$actionTypeWhereFields = array('autoid' => 'INT');
								break;

					case 'Pricelist':
								$query = 'SELECT * FROM pricelist WHERE actiontype in ("U", "D") ORDER BY xpl_id';
								//$query = 'SELECT * FROM pricelist WHERE actiontype in ("-") ORDER BY xpl_id';
								//$query = 'SELECT * FROM pricelist WHERE actiontype in ("-") ORDER BY xpl_id';
								//$query = 'SELECT * FROM pricelist WHERE actiontype in ("U","D") AND plpart = "DAI1415/B31/F31" ORDER BY xpl_id';
								$actionTypeWhereFieldTables = array('pricelist');
								$actionTypeWhereFields = array('xpl_id' => 'INT');
								break;

					case 'Productattribute':
								//$query = 'SELECT p.*, a.attributecode, a.attributetype FROM productattribute p, attribute a WHERE p.attributeid = a.id AND p.actiontype = "U" AND p.productid in ("44400003520/BS","44400003530/BS") AND p.attributeid = 11';
								//$query = 'SELECT p.*, a.attributecode, a.attributetype FROM productattribute p, attribute a WHERE p.attributeid = a.id AND p.actiontype = "U" AND p.attributeid not in (39, 40) AND p.productid = "001ANT49" ORDER BY productid';
								//$query = 'SELECT pa.*, a.attributecode, a.attributetype FROM productattribute pa, attribute a WHERE pa.attributeid = a.id AND pa.actiontype = "U" AND pa.productid = "001ANT49" ORDER BY pa.productid, pa.attributeid';
								//$query = 'SELECT pa.*, a.attr_code, a.attr_type FROM productattribute pa, attribute a WHERE pattr_attr_id = a.attr_id AND pattr_action_type = "U" AND pattr_product_id IN ("001DAK1A08", "001DUN3A08") ORDER BY pattr_product_id, pattr_attr_id';
								//$query = 'SELECT pa.*, a.attr_code, a.attr_type FROM productattribute pa, attribute a WHERE pattr_attr_id = a.attr_id AND pattr_action_type = "-" ORDER BY pattr_product_id, pattr_attr_id';
								$query = 'SELECT pa.*, a.attr_code, a.attr_type FROM productattribute pa, attribute a WHERE pattr_attr_id = a.attr_id AND pattr_action_type = "U" ORDER BY pattr_product_id, pattr_attr_id';
								//$query = 'SELECT pa.*, a.attr_code, a.attr_type FROM productattribute pa, attribute a WHERE pattr_attr_id = a.attr_id AND pattr_action_type = "-" ORDER BY pattr_product_id, pattr_attr_id';
								$actionTypeName = 'pattr_action_type';
								$actionTypeWhereFieldTables = array();
								$actionTypeWhereFields = array();	//UPDATES HANDLED EN-MASSE IN PRODUCT ATTRIBUTE CLASS
								break;

					case 'Productcategory':
								$query = 'SELECT * FROM productcategory WHERE actiontype in ("U", "D") ORDER BY productid';
								//$query = 'SELECT * FROM productcategory WHERE actiontype in ("-") ORDER BY productid';
								//$actionTypeName = 'n_a';
								$actionTypeWhereFieldTables = array();
								$actionTypeWhereFields = array();	//UPDATES HANDLED EN-MASSE IN PRODUCT CATEGORY CLASS
								break;

					case 'Shaderanges':
								$query = 'SELECT * FROM shaderanges WHERE actiontype in ("U", "D") ORDER BY id';
								//$query = 'SELECT * FROM shaderanges WHERE actiontype in ("-") ORDER BY id';
								//$query = 'SELECT * FROM shaderanges WHERE actiontype in ("-","U", "D") ORDER BY id';
								//$query = 'SELECT * FROM shaderanges WHERE actiontype in ("-") ORDER BY id';
								//$query = 'SELECT * FROM shaderanges WHERE shade_partnum = "DAI10" AND actiontype in ("-") ORDER BY id';
								$actionTypeWhereFieldTables = array('shaderanges');
								$actionTypeWhereFields = array('id' => 'INT');
								break;

					case 'Shadeaccessory':
								$query = 'SELECT * FROM shadeaccessory WHERE actiontype in ("U", "D") ORDER BY shade_partnum, accessory_type, accessory_partnum';
								//$query = 'SELECT * FROM shadeaccessory WHERE actiontype in ("-") ORDER BY shade_partnum, accessory_type, accessory_partnum';
								$actionTypeWhereFieldTables = array('shadeaccessory', 'shadeaccessory', 'shadeaccessory');
								$actionTypeWhereFields = array('shade_partnum' => 'TXT', 'accessory_type' => 'TXT', 'accessory_partnum' => 'TXT');
								break;


					case 'Shadebraid':
								$query = 'SELECT * FROM shadebraid WHERE actiontype in ("U", "D") ORDER BY braid_code';
								//$query = 'SELECT * FROM shadebraid WHERE actiontype in ("-") ORDER BY braid_code';
								//$query = 'SELECT * FROM shadebraid WHERE actiontype in ("-","U", "D") ORDER BY braid_code';
								$actionTypeWhereFieldTables = array('shadebraid');
								$actionTypeWhereFields = array('braid_code' => 'TXT');
								break;

					case 'Shadebraidprices':
								$query = 'SELECT * FROM shadebraidprices WHERE actiontype in ("U", "D") ORDER BY shade_partnum, braid_code';
								//$query = 'SELECT * FROM shadebraidprices WHERE actiontype in ("-") ORDER BY shade_partnum, braid_code';

								//$query = 'SELECT * FROM shadebraidprices WHERE actiontype in ("-","U", "D") ORDER BY shade_partnum, braid_code';
								//$query = 'SELECT * FROM shadebraidprices WHERE actiontype in ("-") ORDER BY shade_partnum, braid_code';
								//$query = 'SELECT * FROM shadebraidprices WHERE actiontype in ("-") AND shade_partnum = "DAI10" ORDER BY shade_partnum, braid_code';
								$actionTypeWhereFieldTables = array('shadebraidprices', 'shadebraidprices');
								$actionTypeWhereFields = array('shade_partnum' => 'TXT', 'braid_code' => 'TXT');
								break;

					case 'Shadefringe':
								$query = 'SELECT * FROM shadefringe WHERE actiontype in ("U", "D") ORDER BY fringe_code';
								//$query = 'SELECT * FROM shadefringe WHERE actiontype in ("-") ORDER BY fringe_code';
								//$query = 'SELECT * FROM shadefringe WHERE actiontype in ("-", "U", "D") ORDER BY fringe_code';
								$actionTypeWhereFieldTables = array('shadefringe');
								$actionTypeWhereFields = array('fringe_code' => 'TXT');
								break;

					case 'Shadefringeprices':
								$query = 'SELECT * FROM shadefringeprices WHERE actiontype in ("U", "D") ORDER BY shade_partnum, fringe_code';
								//$query = 'SELECT * FROM shadefringeprices WHERE actiontype in ("-") ORDER BY shade_partnum, fringe_code';
								//$query = 'SELECT * FROM shadefringeprices WHERE actiontype in ("-", "U", "D") ORDER BY shade_partnum, fringe_code';
								//$query = 'SELECT * FROM shadefringeprices WHERE actiontype in ("-") ORDER BY shade_partnum, fringe_code';
								//$query = 'SELECT * FROM shadefringeprices WHERE actiontype in ("-") AND shade_partnum = "EP0199" ORDER BY shade_partnum, fringe_code';
								$actionTypeWhereFieldTables = array('shadefringeprices', 'shadefringeprices');
								$actionTypeWhereFields = array('shade_partnum' => 'TXT', 'fringe_code' => 'TXT');
								break;

					case 'Shadelinerprices':
								$query = 'SELECT * FROM shadelinerprices WHERE actiontype in ("U", "D") ORDER BY shade_partnum, liner_code';
								//$query = 'SELECT * FROM shadelinerprices WHERE actiontype in ("-") ORDER BY shade_partnum, liner_code';
								//$query = 'SELECT * FROM shadelinerprices WHERE actiontype in ("-", "U", "D") ORDER BY shade_partnum, liner_code';
								//$query = 'SELECT * FROM shadelinerprices WHERE actiontype in ("-") ORDER BY shade_partnum, liner_code';
								//$query = 'SELECT * FROM shadelinerprices WHERE actiontype in ("-") AND shade_partnum = "EP0199" ORDER BY shade_partnum, liner_code';
								$actionTypeWhereFieldTables = array('shadelinerprices', 'shadelinerprices');
								$actionTypeWhereFields = array('shade_partnum' => 'TXT', 'liner_code' => 'TXT');
								break;

					case 'Shadesilkcolours':
								$query = 'SELECT * FROM shadesilkcolours WHERE actiontype in ("U", "D") ORDER BY id';
								//$query = 'SELECT * FROM shadesilkcolours WHERE actiontype in ("-") ORDER BY id';
								//$query = 'SELECT * FROM shadesilkcolours WHERE actiontype in ("-", "U", "D") ORDER BY id';
								$actionTypeWhereFieldTables = array('shadesilkcolours');
								$actionTypeWhereFields = array('id' => 'INT');
								break;

					case 'Shadestyles':
								$query = 'SELECT * FROM shadestyles WHERE actiontype in ("U", "D") ORDER BY style_code';
								//$query = 'SELECT * FROM shadestyles WHERE actiontype in ("-") ORDER BY style_code';
								//$query = 'SELECT * FROM shadestyles WHERE actiontype in ("-", "U", "D") ORDER BY style_code';
								$actionTypeWhereFieldTables = array('shadestyles');
								$actionTypeWhereFields = array('style_code' => 'TXT');
								break;

					case 'Stock':
								//NOTE: ONLY LOOKING FOR ACTION TYPE 'U', AS DELETIONS WILL BE HANDLED BY THE STORE DATA TABLE
								$query = 'SELECT * FROM stock WHERE actiontype IN ("U", "D")';
								//$query = 'SELECT * FROM stock WHERE actiontype IN ("-")';
								//$query = 'SELECT * FROM stock WHERE actiontype IN ("-") AND partnum = "SP364/P"';
								//$query = 'SELECT * FROM stock WHERE actiontype IN ("-") AND partnum = "NOA522/P"';
								//$query = 'SELECT * FROM stock WHERE actiontype IN ("-") and partnum = "001DUN3A08/P"';
								//$query = 'SELECT * FROM stock WHERE actiontype IN ("-") and partnum = "001HOW4A08/P"';
								//$query = 'SELECT * FROM stock WHERE actiontype IN ("-") and partnum = "001DUN3A08"';
								//$query = 'SELECT * FROM stock WHERE shade_range_product = "Y" ORDER BY partnum';
								//$query = 'SELECT * FROM stock WHERE partnum in ("PIP16")';
								//$query = 'SELECT * FROM stock WHERE actiontype IN ("-") AND partnum = "TUC0746/P"';
								$actionTypeWhereFieldTables = array('stock');
								$actionTypeWhereFields = array('partnum' => 'TXT');
								break;


					case 'Stocklevel':
								//NOTE: ONLY LOOKING FOR ACTION TYPE 'U', AS DELETIONS WILL BE HANDLED BY THE STORE DATA TABLE
								$query = 'SELECT * FROM stocklevel WHERE actiontype IN ("U")';
								//$query = 'SELECT * FROM stocklevel WHERE actiontype IN ("-")';
								//$query = 'SELECT * FROM stock WHERE partnum in ("001ALL1A08/P")';
								//$query = 'SELECT * FROM stocklevel WHERE actiontype IN ("-")';

								$actionTypeWhereFieldTables = array('stocklevel');
								$actionTypeWhereFields = array('partnum' => 'TXT');
								break;


					case 'Stocklevelcheck':
								$query = 'SELECT * FROM stocklevel WHERE actiontype IN ("U")';
								$actionTypeWhereFields = array();	//N/A - NO ACTION TYPE FIELD
								break;

					case 'Storedata':
								//$query = 'SELECT * FROM storedata WHERE dataid = "ZUM1037" AND actiontype IN ("U", "D")';
								//$query = 'SELECT * FROM storedata WHERE actiontype IN ("-") AND datatype = "PR" and dataid IN ("003L57017/P") ORDER BY datatype, dataid, storeid';
								//$query = 'SELECT * FROM storedata WHERE actiontype IN ("-","D") AND datatype = "PR" AND dataid = "BYR0711/GD" ORDER BY datatype, dataid, storeid';

								$query = 'SELECT * FROM storedata WHERE actiontype IN ("U", "D") ORDER BY datatype, dataid, storeid';
								//$query = 'SELECT * FROM storedata WHERE actiontype IN ("-") ORDER BY datatype, dataid, storeid';
								//$query = 'SELECT * FROM storedata WHERE actiontype IN ("U", "D") ORDER BY datatype, dataid, storeid';
								$actionTypeWhereFieldTables = array('storedata', 'storedata', 'storedata');
								$actionTypeWhereFields = array('storeid' => 'INT', 'dataid' => 'TXT', 'datatype' => 'TXT');
								break;

					case 'Waddresses':
								//NEED TO LINK FOR MAIN CONTACT AND DELIVERY ADDRESSES
								//EXCLUDE '999' ADDRESSES, WHICH ARE USED PURELY TO POPULATE A PRICE LIST 'capltype' FIELD AT THE CUSTOMER LEVEL
								$query = 'SELECT c.*, u.email, u.firstname, u.lastname, u.cust_magento_id, a.* FROM wcustomers c, users u, waddresses a WHERE c.cust = u.userid AND c.cust = a.addrcode AND a.actiontype in ("U", "D") AND a.addritem != "999" ORDER BY c.cust';
								//$query = 'SELECT c.*, u.email, u.firstname, u.lastname, u.cust_magento_id, a.* FROM wcustomers c, users u, waddresses a WHERE c.cust = u.userid AND c.cust = a.addrcode AND a.actiontype in ("-") AND a.addritem != "999" ORDER BY c.cust';

								//$query = 'SELECT c.*, u.email, u.firstname, u.lastname, u.cust_magento_id, a.* FROM wcustomers c, users u, waddresses a WHERE c.cust = u.userid AND c.cust = a.addrcode AND userid = "JDL" AND a.actiontype in ("U", "D") AND a.addritem != "999" ORDER BY c.cust';

								$actionTypeWhereFieldTables = array('waddresses', 'waddresses');
								$actionTypeWhereFields = array('addrcode' => 'TXT', 'addritem' => 'INT');
								break;

					case 'Users':
								$query = 'SELECT c.cust, c.custname, c.maincontact, c.cdisc, c.parkerdisc, c.dhldisc, c.cdefdeladd, c.conhold, c.proforma, u.* FROM wcustomers c, users u WHERE c.cust = u.userid AND u.actiontype IN ("U", "D")';
								//$query = 'SELECT c.cust, c.custname, c.maincontact, c.cdisc, c.parkerdisc, c.dhldisc, c.cdefdeladd, c.conhold, c.proforma, u.* FROM wcustomers c, users u WHERE c.cust = u.userid AND u.actiontype IN ("-")';

								//$query = 'SELECT c.cust, c.custname, c.maincontact, c.cdisc, c.parkerdisc, c.dhldisc, c.cdefdeladd, c.conhold, c.proforma, u.* FROM wcustomers c, users u WHERE c.cust = u.userid AND u.actiontype IN ("U") and u.userid = "DAR999"';

								$actionTypeWhereFieldTables = array('users');
								$actionTypeWhereFields = array('userid' => 'TXT');
								break;

					case 'Xpromo':
								$query = 'SELECT * FROM xpromo WHERE actiontype IN ("U", "D") ORDER BY xpromocode';
								//$query = 'SELECT * FROM xpromo WHERE actiontype IN ("-") ORDER BY xpromocode';
								//$query = 'SELECT * FROM xpromo WHERE actiontype IN ("-")';

								$actionTypeWhereFieldTables = array('xpromo');
								$actionTypeWhereFields = array('xpromocode' => 'TXT');
								break;

					case 'Xpromopart':
								$query = 'SELECT * FROM xpromopart WHERE actiontype IN ("U", "D") ORDER BY xpromocode, xpromopart';
								//$query = 'SELECT * FROM xpromopart WHERE actiontype IN ("-") ORDER BY xpromocode, xpromopart';
								//$query = 'SELECT * FROM xpromopart WHERE actiontype IN ("-")';

								$actionTypeWhereFieldTables = array('xpromopart', 'xpromopart');
								$actionTypeWhereFields = array('xpromocode' => 'TXT', 'xpromopart' => 'TXT');
								break;

					case 'Xrrp':
								//$query = 'SELECT * FROM xrrp WHERE actiontype = "U"';
								$query = 'SELECT * FROM xrrp WHERE actiontype IN ("U")';
								//$query = 'SELECT * FROM xrrp WHERE actiontype IN ("-")';
								//$query = 'SELECT * FROM xrrp WHERE partnum = "BULGU1050"';

								$actionTypeWhereFieldTables = array('xrrp');
								$actionTypeWhereFields = array('partnum' => 'TXT');
								break;

					case 'Xweblampcategory':
								$query = 'SELECT * FROM xweblampcategory WHERE actiontype IN ("U", "D")';
								//$query = 'SELECT * FROM xweblampcategory WHERE actiontype IN ("-")';
								$actionTypeWhereFieldTables = array('xweblampcategory');
								$actionTypeWhereFields = array('lampgroupid' => 'INT');
								break;

					case 'Xweblamp':
								$query = 'SELECT xwl.*, xwlc.lampgroupname, xwlc.categoryid FROM xweblamp xwl, xweblampcategory xwlc WHERE xwl.lampgroupid = xwlc.lampgroupid AND xwl.actiontype IN ("U", "D")';
								//$query = 'SELECT xwl.*, xwlc.lampgroupname, xwlc.categoryid FROM xweblamp xwl, xweblampcategory xwlc WHERE xwl.lampgroupid = xwlc.lampgroupid AND xwl.actiontype IN ("-")';
								//$query = 'SELECT xwl.*, xwlc.lampgroupname, xwlc.categoryid FROM xweblamp xwl, xweblampcategory xwlc WHERE xwl.lampgroupid = xwlc.lampgroupid AND xwl.actiontype IN ("-")';
								//$query = 'SELECT xwl.*, xwlc.lampgroupname, xwlc.categoryid FROM xweblamp xwl, xweblampcategory xwlc WHERE xwl.lampgroupid = xwlc.lampgroupid AND xwl.actiontype IN ("-","D") AND xwl.partnum = "BULGU1050"';
								//$query = 'SELECT xwl.*, xwlc.lampgroupname, xwlc.categoryid FROM xweblamp xwl, xweblampcategory xwlc WHERE xwl.lampgroupid = xwlc.lampgroupid AND xwl.actiontype IN ("-","D") AND xwl.partnum = "BULBG920"';
								//$query = 'SELECT xwl.*, xwlc.lampgroupname, xwlc.categoryid FROM xweblamp xwl, xweblampcategory xwlc WHERE xwl.lampgroupid = xwlc.lampgroupid AND xwl.actiontype IN ("-","D") AND xwl.partnum = "BULGU1050"';
								//$query = 'SELECT xwl.*, xwlc.lampgroupname, xwlc.categoryid FROM xweblamp xwl, xweblampcategory xwlc WHERE xwl.lampgroupid = xwlc.lampgroupid AND xwl.actiontype IN ("-","D") AND xwl.partnum = "BULR7S230"';
								$actionTypeWhereFieldTables = array('xweblamp', 'xweblamp');
								$actionTypeWhereFields = array('partnum' => 'TXT', 'lampgroupid' => 'INT');
								break;

					default:
							generalLib::error('No query set for the \'' . $funcFeedTable . '\' table');
							break;
				}


				//echo "bef processData\n";

				try {
				magentoDbFuncs::processData($this, $this->dbObj,  $query, PDO::FETCH_ASSOC, $funcFeedTable, $processFunc, $actionTypeName,
							$actionTypeWhereFieldTables, $actionTypeWhereFields,
							$this->procConfig->actionTypeUpdProcessed, $this->procConfig->actionTypeDelProcessed);
				}
				catch(Exception $e){
						generalLib::error($e->getMessage());
				}

				if ($funcFeedTable == 'Stocklevel' && $this->procConfig->numStockLevelsUpd > 0) {

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
				generalLib::error('Cannot process. No ' . $processFunc . ' function for feedtable ' . $funcFeedTable);
			}
		}

		//print_r($this->procConfig->shadePartNumsToChk);

		foreach( $this->procConfig->shadePartNumsToChk as $shadePartNumToChk) {
			$this->chkAndUpdShadeChildProducts($shadePartNumToChk);
		}

		if ($this->procConfig->checkRangeAndStyleTables) {
			$this->chkShadeStyleAndRangeTypes();
		}

		$this->dbObj->absDbClose();

		magentoImport::delMagFeedLock();

		/*
		//TEMP - TO BE RESTORED
		if ($magFeedIdxLock['createdLock']) {
			magentoIndex::delMagIdxLock();
		}
		*/
	}


	//###########################
	private function getShadeBraids() {
		$shadeBraidArr = array();

		$query = 'SELECT braid_code, braid_name FROM shadebraid';

		$result = $this->dbObj->absDbQuery($query);

		while($queryResult = $this->dbObj->absDbFetchArray($result, PDO::FETCH_ASSOC)) {
			$queryResultObj = (object) $queryResult;
			$shadeBraidArr[ $queryResultObj->braid_code ] = $queryResultObj->braid_name;

		}

		//print_r($shadeBraidArr);
		return $shadeBraidArr;
	}


	//###########################
	private function getShadeFringes() {
		$shadeFringeArr = array();

		$query = 'SELECT fringe_code, fringe_name FROM shadefringe';

		$result = $this->dbObj->absDbQuery($query);

		while($queryResult = $this->dbObj->absDbFetchArray($result, PDO::FETCH_ASSOC)) {
			$queryResultObj = (object) $queryResult;
			$shadeFringeArr[ $queryResultObj->fringe_code ] = $queryResultObj->fringe_name;

		}

		//print_r($shadeFringeArr);
		return $shadeFringeArr;
	}


	//###########################
	private function getShadeLiners() {
		$shadeLinerArr = array();

		$query = 'SELECT liner_code, liner_name FROM shadeliner';

		$result = $this->dbObj->absDbQuery($query);

		while($queryResult = $this->dbObj->absDbFetchArray($result, PDO::FETCH_ASSOC)) {
			$queryResultObj = (object) $queryResult;
			$shadeLinerArr[ $queryResultObj->liner_code ] = strtok($queryResultObj->liner_name, ' ');
		}

		//print_r($shadeLinerArr);
		return $shadeLinerArr;
	}


	//###########################
	public function getDhlCables() {
		$cableArr = array();

		$query = 'SELECT cable_code, cable_name FROM dhlcable';

		$result = $this->dbObj->absDbQuery($query);

		while($queryResult = $this->dbObj->absDbFetchArray($result, PDO::FETCH_ASSOC)) {
			$queryResultObj = (object) $queryResult;
			$cableArr[ $queryResultObj->cable_code ] = strtok($queryResultObj->cable_name, ' ');
		}

		//print_r($cableArr);
		return $cableArr;
	}


	//###########################
	public function getDhlGlass() {
		$glassArr = array();

		$query = 'SELECT glass_code, glass_name FROM dhlglass';

		$result = $this->dbObj->absDbQuery($query);

		while($queryResult = $this->dbObj->absDbFetchArray($result, PDO::FETCH_ASSOC)) {
			$queryResultObj = (object) $queryResult;
			$glassArr[ $queryResultObj->glass_code ] = strtok($queryResultObj->glass_name, ' ');
		}

		//print_r($glassArr);
		return $glassArr;
	}


	//###########################
	public function getDhlInnerPaint() {
		$innerPaintArr = array();

		$query = 'SELECT paint_code, paint_name FROM dhlinnerpaint';

		$result = $this->dbObj->absDbQuery($query);

		while($queryResult = $this->dbObj->absDbFetchArray($result, PDO::FETCH_ASSOC)) {
			$queryResultObj = (object) $queryResult;
			$innerPaintArr[ $queryResultObj->paint_code ] = strtok($queryResultObj->paint_name, ' ');
		}

		//print_r($shadeLinerArr);
		return $innerPaintArr;
	}


	//###########################
	public function getDhlOuterPaint() {
		$outerPaintArr = array();

		$query = 'SELECT paint_code, paint_name FROM dhlouterpaint';

		$result = $this->dbObj->absDbQuery($query);

		while($queryResult = $this->dbObj->absDbFetchArray($result, PDO::FETCH_ASSOC)) {
			$queryResultObj = (object) $queryResult;
			$outerPaintArr[ $queryResultObj->paint_code ] = strtok($queryResultObj->paint_name, ' ');
		}

		//print_r($shadeLinerArr);
		return $outerPaintArr;
	}


	//###########################
	public function processAttributesetTable($actionType, $attrSetRec) {
		$rc = true;

		generalLib::mesg('');
		generalLib::mesg('====');

		print_r($attrSetRec);

		$retArr = magentoAttrImport::processProdAttrSet($actionType, $attrSetRec, 'Default');

		$rc = $retArr['rc'];

		if ($rc && ( ($actionType == 'U' && $attrSetRec->set_mag_id == '') || $actionType == 'D') ) {
			$magIdUpdRec = new stdClass();
			$magIdUpdRec->dbObj = $this->dbObj;
			$magIdUpdRec->extTable = 'attributeset';
			$magIdUpdRec->magentoIdFldName = 'set_mag_id';
			$magIdUpdRec->magentoId = $retArr['setId'];
			$magIdUpdRec->idFldName = 'set_id';
			$magIdUpdRec->id = $attrSetRec->set_id;
			$magIdUpdRec->idType = 'INT';


			$rc = magentoDbFuncs::setTblMagId($magIdUpdRec);
		}

		return $rc;
	}


	//###########################
	public function processAttributegroupTable($actionType, $attrGrpRec) {
		$rc = true;

		generalLib::mesg('');
		generalLib::mesg('====');

		//print_r($attrGrpRec);

		$retArr = magentoAttrImport::processProdAttrGrp($actionType, $attrGrpRec);

		//print_r($retArr);

		$rc = $retArr['rc'];

		if ($rc && ( ($actionType == 'U' && $attrGrpRec->grp_mag_id == '') || $actionType == 'D') ) {
			$magIdUpdRec = new stdClass();
			$magIdUpdRec->dbObj = $this->dbObj;
			$magIdUpdRec->extTable = 'attributegroup';
			$magIdUpdRec->magentoIdFldName = 'grp_mag_id';
			$magIdUpdRec->magentoId = $retArr['grpId'];
			$magIdUpdRec->idFldName = 'grp_id';
			$magIdUpdRec->id = $attrGrpRec->grp_id;
			$magIdUpdRec->idType = 'INT';

			$rc = magentoDbFuncs::setTblMagId($magIdUpdRec);
		}

		return $rc;
	}


	//###########################
	public function processAttributeTable($actionType, $attrRec) {
		$rc = true;

		generalLib::mesg('');
		generalLib::mesg('====');

		//print_r($attrRec);

		$retArr = magentoAttrImport::processAttribute($actionType, $attrRec);

		$rc = $retArr['rc'];

		if ($rc && ( ($actionType == 'U' && $attrRec->attr_mag_id == '') || $actionType == 'D') ) {
			$magIdUpdRec = new stdClass();
			$magIdUpdRec->dbObj = $this->dbObj;
			$magIdUpdRec->extTable = 'attribute';
			$magIdUpdRec->magentoIdFldName = 'attr_mag_id';
			$magIdUpdRec->magentoId = $retArr['attrId'];
			$magIdUpdRec->idFldName = 'attr_id';
			$magIdUpdRec->id = $attrRec->attr_id;
			$magIdUpdRec->idType = 'INT';

			$rc = magentoDbFuncs::setTblMagId($magIdUpdRec);
		}

		return $rc;
	}


	//###########################
	public function processProductattributeTable($actionType, $prodAttrRec) {
		$rc = true;

		generalLib::mesg('');
		generalLib::mesg('====');

		//print_r($prodAttrRec);

		$prodAttrRec->pattr_product_id = trim($prodAttrRec->pattr_product_id);

		$prodAttrRec->prodAttrTable = 'productattribute';
		$prodAttrRec->attrTable = 'attribute';

		$rc = magentoAttrImport::processProdAttributes($actionType, $prodAttrRec);

		return $rc;
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
	public function processCat_categoriesTable($actionType, $catRec) {
		$rc = true;

		//print_r($catRec);

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

			return $rc;
		}


		$updCat = false;


		if (!$category || magentoImport::processCustomAttrs($this, $category, $catRec, array($this->procConfig->extParentCatId => 'T'), $this->procConfig->catCustomAttrMappings)) {

			if ($extCatParentId == NULL) {
				$parentMagPath = $this->procConfig->rootStoreCatPath;
				$parentMagCatId = $this->procConfig->rootStoreCatId;
			} else {
				$parentCategory = magentoCat::getCategory($magCatIdFld, $extCatParentId);

				if ($parentCategory != false) {
					$parentMagPath = $parentCategory->getPath();
					$parentMagCatId = $parentCategory->getId();
				} else {
					generalLib::warn('Cannot find parent external cat id ' . $extCatParentId . ' - aborting input');
					return;
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

		//die();
		return $rc;
	}


	//###########################
	public function processIldocumentTable($actionType, $leafletRec) {
		$rc = true;

		generalLib::mesg('');
		generalLib::mesg('====');

		$product = $this->getProductForPartnum($leafletRec->sku, true);

		if (!$product) {
			$rc = false;
			return $rc;
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

		return $rc;
	}


	//###########################
	public function processLocalstockistsTable($actionType, $stockistRec) {
		$rc = true;

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

			return $rc;
		}


		if ($stockistMagRec->getId() == '') {

			generalLib::mesg('Creating stockist for stock id : \'' . $stockistId . '\'');
			$stockist = $this->objectManager->create( $this->procConfig->stockistModel );

		} else {
			generalLib::mesg('Updating stockist for stock id : \'' . $stockistId . '\'');
		}

		if ( isset( $stockistRec->postcode ) ) {

			/*
			$addr = '';

			if (!empty( $stockistRec->address1 ) ) {
				$addr = $stockistRec->address1 . ',';
			}

			if (!empty( $stockistRec->address2 ) ) {
				$addr .= $stockistRec->address2 . ',';
			}

			if (!empty( $stockistRec->address3 ) ) {
				$addr .= $stockistRec->address3 . ',';
			}

			if (!empty( $stockistRec->county ) ) {
				$addr .= $stockistRec->county . ',';
			}
			*/

			$addr = $stockistRec->postcode;

			//echo 'addr = ' . $addr . "\n";

			$lonLat = $this->getLonLat( $addr );

			//$lonLat = $this->getLonLat($stockistRec->postcode);

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

		return $rc;
	}


	//###########################
	// Getting the Longitude and Latitude of each address
    private function getLonLat($address) {
        $points = array();

        if (strlen($address) < 6) {
            $address .= ' United Kingdom';
        }

        // Setting API Key

        //$this->procConfig->googleApiKey = $this->objectManager->get('\Magento\Framework\App\Config\ScopeConfigInterface')->getValue('stockistlocator/locatorconfiguration/access_token');
        //$apiKey = $this->_scopeConfig->getValue('stockistlocator/locatorconfiguration/access_token', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

        //ini_set('allow_url_fopen', 1);
        $url = 'https://maps.googleapis.com/maps/api/geocode/json?key=' . $this->procConfig->googleApiKey . '&address=' . str_replace(' ', '+', $address);

		//echo 'url = ' . $url . "\n";

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
	public function processPart_relatedTable($actionType, $partRelRec) {
		$rc = true;

		//print_r($partRelRec);

		generalLib::mesg('');
		generalLib::mesg('====');

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

		//die();

		return $rc;
	}


	//###########################
	public function processOrderheaderTable($actionType, $orderRec) {
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
	public function processPricelistTable($actionType, $prodPriceRec) {
		$rc = true;
		//print_r($prodPriceRec);

		generalLib::mesg('');
		generalLib::mesg('====');

		$product = $this->getProductForPartnum($prodPriceRec->plpart, true);

		if (!$product) {
			$rc = false;
			return $rc;
		}

		$prodSku = $product->getSku();

		generalLib::mesg('Processing product sku \'' . $prodSku . '\'');

		if ($prodPriceRec->pltype == '') {
			generalLib::warn('Blank price list type - aborting import');
			$rc = false;
			return $rc;
		}

		$discountData = new stdClass();
		$discountData->extId = $prodPriceRec->xpl_id;
		$discountData->websiteId = '0'; //ALL WEB SITES
		$discountData->storeId = '0'; //DEFAULT STORE
		$discountData->productNo = $prodSku;
		$discountData->customerNo = $prodPriceRec->pltype;
		$discountData->source = 'pricelist';
		$discountData->priceQty = $prodPriceRec->plbrkqty;
		$discountData->price = $prodPriceRec->plunitprice;
		$discountData->startDate = date('Y-m-d', strtotime($prodPriceRec->plfromdate));
		$discountData->endDate = date('Y-m-d', strtotime($prodPriceRec->pltodate));

		//print_r($discountData);

		$rc = magentoCustomPrices::processCustomPrice($actionType, $product, $discountData);

		return $rc;
	}


	//###########################
	public function processProductcategoryTable($actionType, $prodCatRec) {
		$rc = true;

		generalLib::mesg('');
		generalLib::mesg('====');

		print_r($prodCatRec);

		$product = $this->getProductForPartnum($prodCatRec->productid, true);

		if (!$product) {
			$rc = false;
			return $rc;
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

		return $rc;
	}


	//###########################
	public function processShaderangesTable($actionType, $shadeRec) {
		$rc = true;

		generalLib::mesg('');
		generalLib::mesg('====');

		//print_r($shadeRec);

		$product = $this->getProductForPartnum($shadeRec->shade_partnum, true);

		if (!$product) {
			$rc = false;
			return $rc;
		}

		//NEED TO THINK ABOUT DELETING THE CUSTOM OPTIONS IF SET FOR DELETION

		$productId = $product->getId();
		$productSku = $product->getSku();

		$updProd = false;

		$updShadeRangeName = magentoImport::chkDropdownAttr($product, 'shade_range_name', (string) $shadeRec->shade_range_name, $this->procConfig->shadeRangeOpts);

		if ($updShadeRangeName && (!$updProd)) {
			$updProd = true;
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
			$rc = $this->buildShadeRangeCustomOptions($product, $shadeRec, false);

			if ($rc) {
				//TEMP
				$rc = $this->chkAndUpdShadeChildProducts($productSku);
			}
		}

		return $rc;
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


		$query = 'SELECT * FROM shaderanges WHERE actiontype = "-" ORDER BY id';
		$funcFeedTable = 'Shaderangestyles';
		$processFunc = 'buildShadeStyleAndRangeTypeLists';
		$actionTypeName = 'actiontype';
		$actionTypeWhereFieldTables = array();
		$actionTypeWhereFields = array();

		try {
			magentoDbFuncs::processData($this, $this->dbObj,  $query, PDO::FETCH_ASSOC, $funcFeedTable, $processFunc, $actionTypeName,
							$actionTypeWhereFieldTables, $actionTypeWhereFields,
							$this->procConfig->actionTypeUpdProcessed, $this->procConfig->actionTypeDelProcessed);
		}

		catch(Exception $e) {
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

		//die();
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
	        		generalLib::mesg('Updating range type to \''. $styleRangeTypes . '\' for style code \'' . $style_code . '\'');

	        		$styleMagRec->setRangeType( $styleRangeTypes );

	        		try {
						generalLib::mesg('Saving style code');
						$styleMagRec->save();
					}

					catch(Exception $e) {
						generalLib::error($e->getMessage());
						generalLib::error('Style \'' . $styleRec->style_code . '\' not saved');
						$rc = false;
					}
	        	}

	        } else {
	        	generalLib::warn('Cannot find style code \'' . $styleRec->style_code . '\' - unable to update range type');
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

		//echo "got to buildShadeStyleAndRangeTypeLists\n";

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
	public function processShadelinerpricesTable($actionType, $shadeLinerPriceRec) {
		$rc = true;


		/*
		if ($shadeLinerPriceRec->shade_partnum != 'ABY01') {
			return false;
		}
		*/

		generalLib::mesg('');
		generalLib::mesg('====');

		//print_r($shadeLinerPriceRec);

		$product = $this->getProductForPartnum($shadeLinerPriceRec->shade_partnum, true);

		if (!$product) {
			$rc = false;
			return $rc;
		}

		$retArr = $this->buildShadeCustomOption($actionType, $product, 'Liner Colour',  $this->procConfig->shadeLiners[ $shadeLinerPriceRec->liner_code ], $shadeLinerPriceRec->liner_price);

		$rc = $retArr['rc'];

		//if ($rc && $retArr['updProd']) {

			if (!isset( $this->procConfig->shadePartNumsToChk[ $shadeLinerPriceRec->shade_partnum ]) ) {
				$this->procConfig->shadePartNumsToChk[ $shadeLinerPriceRec->shade_partnum ] = $shadeLinerPriceRec->shade_partnum;
			}

		//}

		return $rc;
	}


	//###########################
	public function buildShadeCustomOption($actionType, &$product, $custOptTitle, $custOptValTitle, $custOptValPrice) {
		$rc = true;
		$retArr = array();

		$customOptionArr = array();
		$customOptionArr['title'] = $custOptTitle;
		$customOptionArr['type'] = 'radio';
		$customOptionArr['is_require'] = '0';
		//print_r($customOptionArr);

		$customOptionValueArr = array();
		$customOptionValueArr['title'] = $custOptValTitle;
		$customOptionValueArr['price_type'] = 'fixed';
		$customOptionValueArr['price'] = $custOptValPrice;
		$customOptionValueArr['sku'] = '';

		//print_r($customOptionValueArr);

		$updProd = MagentoCustomOpts::processCustomOption($actionType, $product, 'title', $customOptionArr, 'title', $customOptionValueArr);

		if ($updProd) {
			$rc = MagentoCustomOpts::saveCustomOptProd($product, $product->getSku(), true);
		}

		unset($customOptionArr);
		unset($customOptionValueArr);

		$retArr['rc'] = $rc;
		$retArr['updProd'] = $updProd;

		return $retArr;
	}


	//###########################
	public function buildShadeRangeCustomOptions($product, $shadeRec, $updProd) {
		$rc = true;
		$productSku = $product->getSku();

		$updatedCustomOpts = false;


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

			if ($rc) {
				$updatedCustomOpts = true;
			}
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

			if ($rc) {
				$updatedCustomOpts = true;
			}
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

		unset($customOptionArr);
		$customOptionArr = array();
		$customOptionArr['title'] = 'Tier 1';
		$customOptionArr['type'] = 'field';
		$customOptionArr['price_type'] = 'fixed';
		$customOptionArr['is_require'] = '0';

		unset($customOptionValueArr);
		$customOptionValueArr = array();

		//print_r($customOptionArr);
		//print_r($customOptionValueArr);
		//die();

		//THERE WILL ALWAYS BE ONE TIER
		$updCustomOpts = MagentoCustomOpts::processCustomOption($actionType, $product, 'title', $customOptionArr, 'price_type', $customOptionValueArr);

		if ($updCustomOpts) {
			$rc = MagentoCustomOpts::saveCustomOptProd($product, $productSku, true);

			if ($rc) {
				$updatedCustomOpts = true;
			}
		}


		//print_r($shadeRec);

		if (magentoImport::processCustomAttrs($this, $product, $shadeRec, $this->procConfig->shaderangesTierCustomAttrs)) {

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

					if ($rc) {
						$updatedCustomOpts = true;
					}
				}
			}

			$updProd = true;
		}


		if (!$updatedCustomOpts && $updProd) {
			$rc = MagentoCustomOpts::saveCustomOptProd($product, $productSku, false);
		}

		return $rc;
	}


	//###########################
	public function processStockTable($actionType, $stockRec) {
		$rc = true;

		generalLib::mesg('');
		generalLib::mesg('====');

		//print_r($stockRec);

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
			return $rc;
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

		$stockRec->metatitle = htmlspecialchars_decode( $stockRec->partdesc );
		$stockRec->metatitle = trim($stockRec->metatitle);

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


		if ($productId == '') {
			$rrp = $product->getPrice() * 2;

			$updRrp = false;

			if ($productId != '') {
				$oldRrp = $product->getRrp();

				if ($oldRrp != $rrp) {
					$updRrp = true;
				}
			} else {
				$updRrp = true;
			}

			if ($updRrp) {
				$product->setRrp($rrp);

				generalLib::mesg('Setting RRP default to \'' . $rrp . '\'');
				$updProd = true;
			}
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

			$rc = $this->processStockLevelTable('U', $stockLevelRec);
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

						//print_r($nonPromoSkuImage);

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

							if ($imgObj->position == 0) {
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
		   		}
			}
		}

		//die();

		return $rc;
	}


	//###########################
	public function processStocklevelTable($actionType, $stockLevelRec) {
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
		return $rc;
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
	public function processStoredataTable($actionType, $storeDataRec) {
		$rc = true;

		generalLib::mesg('');
		generalLib::mesg('====');

		print_r($storeDataRec);

		if ($storeDataRec->datatype == 'PR') {
			$rc = $this->processStoreDataProduct($actionType, $storeDataRec);
		} else {
			$rc = $this->processStoreDataUser($actionType, $storeDataRec);
		}

		return $rc;
	}


	//###########################
	public function processStoreDataProduct($actionType, $storeDataRec) {
		$rc = true;

		$product = $this->getProductForPartnum($storeDataRec->dataid, true);

		if (!$product) {
			$rc = false;
			return $rc;
		}

		$productId = $product->getId();

		$productSku = $product->getSku();

		$magStoreId = $this->procConfig->darToMagStoreMappings[ $storeDataRec->storeid ];

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
				$rc = false;
				return $rc;
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
					generalLib::error($e->getMessage());
					generalLib::error('Product sku \'' . $productSku . '\' status not saved');
				}

			}
		}

		return $rc;
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
	public function processStoreDataUser($actionType, $storeDataRec) {
		$rc = true;

		if ($actionType == 'D') {
			//SELECT CUSTOMER FOR THE SPECIFIC STORE AND DELETE IT
			$customer = magentoCust::getMagCustomer($storeDataRec, 'dataid', 'cust', 'cust_magento_id', false);

			if ($customer) {
				$rc = magentoCust::processCustDeletion($storeDataRec->storeid, $storeDataRec->dataid, $customer);
			}
		}

		return $rc;
	}


	//###########################
	public function setProdWebsites($product, $prodStores) {
		$productSku = $product->getSku();
		$prodWebSiteIds = $product->getWebsiteIds();

		//$prodStores = $this->getStoresForDataId('PR', $productSku);

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
		echo 'shadeParentSku = ' . $shadeParentSku . "\n";

		$shadeParentProduct = $this->getProductForPartnum($shadeParentSku, true);
		echo 'parent prod id = ' . $shadeParentProduct->getId() . "\n";

		if (!$shadeParentProduct) {
			generalLib::warn('Shade base product not found for \'' . $shadeParentSku . '\'');
			return $rc;
		}

		$shadeChildSku = $shadeChildProduct->getSku();

		$shadeChildComponents = explode('/', $shadeChildSku);

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

				if ( isset( $this->procConfig->shadeLiners[ $shadeChildComponent ] ) ) {
					$liner = $this->procConfig->shadeLiners[ $shadeChildComponent ];
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

			//COPY THE TIER SETTINGS FROM THE PARENT SHADE PRODUCT
			foreach($this->procConfig->shaderangesTierCustomAttrs as $shaderangesTierCustomAttr => $shaderangesLinedCustomAttrType) {
				//echo 'shaderangesTierCustomAttr = ' . $shaderangesTierCustomAttr . "\n";

				$shadeRec->$shaderangesTierCustomAttr = $shadeParentProduct->getData($shaderangesTierCustomAttr);
			}

		//print_r($shadeRec);

			$rc = $this->buildShadeRangeCustomOptions($shadeChildProduct, $shadeRec, $updProd);
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
					$shadeLinerPriceRec->liner_code = array_search($shadeParentLinerCustOptValue[ 'title' ], $this->procConfig->shadeLiners);

					if ($liner == 'parent') {
						$shadeLinerPriceRec->liner_price = $shadeParentLinerCustOptValue[ 'price' ];
					} else {
						$shadeLinerPriceRec->liner_price = '0'; //LINER PRICE IS INCLUDED IN THE PRODUCT PRICE
					}

					//echo "parent values:\n";
					//print_r($shadeParentLinerCustOptValue);

					//echo "shadeLinerPriceRec:\n";
					//print_r($shadeLinerPriceRec);

					$retArr = $this->buildShadeCustomOption('U', $shadeChildProduct, 'Liner Colour',  $this->procConfig->shadeLiners[ $shadeLinerPriceRec->liner_code ], $shadeLinerPriceRec->liner_price);

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
	public function processXpromoTable($actionType, $xpromoRec) {
		$rc = true;

		generalLib::mesg('');
		generalLib::mesg('====');

		print_r($xpromoRec);

		$xpromoVars = get_object_vars($xpromoRec);

		foreach($xpromoVars as $xpromoField => $xpromoFieldVal) {
			$xpromoRec->$xpromoField = trim($xpromoRec->$xpromoField);
		}

		//print_r($xpromoRec);

		$xpromoCode = $xpromoRec->xpromocode;

		$endDateExp = ( strtotime($xpromoRec->xpromoto) < $this->procConfig->currDateTStamp );

		$xpromoMagRec = $this->objectManager->create( $this->procConfig->xpromoModel )->load($xpromoCode, 'xpromo_code');

		if ($xpromoMagRec->getId() == '' && $actionType == 'U' && $endDateExp) {
			generalLib::warn('Cannot create promo for code \'' . $xpromoCode .  ' - Promo already expired on \'' . $xpromoRec->xpromoto . '\' - flagging promo for deletion');
			$rc = -2;
			return $rc;
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

			return $rc;
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

		return $rc;
	}


	//###########################
	public function processXpromopartTable($actionType, $xpromoPartRec) {
		$rc = true;

		generalLib::mesg('');
		generalLib::mesg('====');

		print_r($xpromoPartRec);

		$xpromoCode = $xpromoPartRec->xpromocode;

		$xpromoPartMagRec = $this->objectManager->create( $this->procConfig->xpromoPartModel )->load($xpromoCode, 'xpromo_code');

		if ($xpromoPartMagRec->getId() == '' && $actionType == 'U') {
			generalLib::warn('Promo code \'' . $xpromoCode . ' does not exist - flagging promo part for deletion');
			$rc = -2;
			return $rc;
		}

		$xpromoPart = $xpromoPartRec->xpromopart;

		$xpromoPartColl = $this->objectManager->create( $this->procConfig->xpromoPartModel )
                        ->getCollection()
                        ->addFieldToFilter('xpromo_code', array('eq' => $xpromoCode))
						->addFieldToFilter('xpromo_part', array('eq' => $xpromoPart));

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

			return $rc;

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

		return $rc;
	}


	//###########################
	public function processXrrpTable($actionType, $xrrpRec) {
		$rc = true;

		generalLib::mesg('');
		generalLib::mesg('====');

		print_r($xrrpRec);

		$product = $this->getProductForPartnum($xrrpRec->partnum, true);

		if (!$product) {
			$rc = false;
			return $rc;
		}

		$productSku = $product->getSku();

		$updProd = false;

		if ($product->getWebLampProduct() != '1') {

			$updAttrs = magentoImport::processMagAttrs($product, $xrrpRec, $this->procConfig->xrrpMagPackSizeAttr);

			if ( $updAttrs && ( !$updProd) ) {
				$updProd = true;
			}

		}

		$updAttrs = magentoImport::processMagAttrs($product, $xrrpRec, $this->procConfig->xrrpMagAttrs);

		if ( $updAttrs && ( !$updProd) ) {
			$updProd = true;
		}

		/*
		if ($product->getRrp() == 0 && $product->getSrp() == 0) {
			$rrp = $product->getPrice() * 2;
			$product->setRrp($rrp);
			generalLib::mesg('Both SRP and RRP are zero - Setting RRP to \'' . $rrp . '\'');
			$updProd = true;
		}
		*/

		if ( $updProd ) {

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

			generalLib::mesg('No rrp updates to apply for product sku \'' . $productSku . '\'');

		}

		return $rc;
	}


	//###########################
	public function processXweblampcategoryTable($actionType, $xweblampcatRec) {
		$rc = true;
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
			return $rc;
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

			return $rc;
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

		if (magentoImport::processCustomAttrs($this, $grpProduct, $xweblampcatRec, array('categoryid' => 'T'), array('categoryid' => 'general_cat_id'))) {
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

		return $rc;
	}


	//###########################
	public function processXweblampTable($actionType, $xweblampRec) {
		$rc = true;

		generalLib::mesg('');
		generalLib::mesg('====');

		print_r($xweblampRec);

		$product = $this->getProductForPartnum($xweblampRec->partnum, false);

		if (!$product) {
			generalLib::warn('Cannot process part num \'' . $xweblampRec->partnum . '\' for grouped product \'' . $xweblampRec->lampgroupname . '\' - Part num does not exist in stock table');
			$rc = false;
			return $rc;
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
		$grpProdAttrs->name = $xweblampRec->lampgroupname;
		$grpProdAttrs->description = $xweblampRec->lampgroupname;

		$grpProdAttrs->sku = str_replace(' ', '_', $xweblampRec->lampgroupname);

		$grpProdAttrs->lampGroupId = $xweblampRec->lampgroupid;
		$grpProdAttrs->lampGroupCatId = $xweblampRec->categoryid;

        $magCatIdFld = $this->procConfig->catCustomAttrMappings[ $this->procConfig->extCatId ];

		$category = magentoCat::getCategory($magCatIdFld, $xweblampRec->categoryid);

		if ($category == false) {
			generalLib::warn('Cannot find category via category id \'' . $xweblampRec->categoryid . '\'');
			$grpProdAttrs->categoryId = '';
		} else {
			$grpProdAttrs->categoryId = $category->getId();
		}


		$rc = $this->processGroupedProduct($actionType, $grpProdAttrs, $productId);

		//die();

		return $rc;
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

			$updProd = true;
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

		$prodStores = $this->getStoresForDataId('PR', $grpProdAttrs->sku);

		$updAttrs = $this->setProdWebsites($grpProduct, $prodStores);

		if ($updAttrs && (!$updProd)) {
			$updProd = true;
		}


		//ADD OR REMOVE PRODUCT TO/FROM GROUP PRODUCT - DELETE THE GROUP PRODUCT IF A DELETION RESULTS IN NO REMAINING ASSOCIATED PRODUCTS

		$rc = magentoGroupProducts::processGroupedProductForSingleProduct($grpProduct, '', '', 'mag_id', array($productId), $actionType, true);

		if ($rc) {
			$rc = magentoGroupProducts::setGrpProdWebSitesFromAssocProds($this->procConfig->storeDets, $grpProdAttrs->sku);
		}

		//die();
		return $rc;
	}


	//###########################
	public function processWaddressesTable($actionType, $custAddrRec) {
		$rc = true;

		generalLib::mesg('');
		generalLib::mesg('====');

		print_r($custAddrRec);
		//die();

		/*
		$addrExclArr = array('closed', 'see below', 'see below for address', 'please see address details', '');

		if (in_array( strtolower($custAddrRec->address1), $addrExclArr)) {
			generalLib::warn('Invalid address \'' . $address1 . '\' for addrcode \'' . $custAddrRec->addrcode . '\' addritem \'' . $custAddrRec->addritem . '\'');
		}

		if ($custAddrRec->address2 == '') {
			generalLib::warn('Blank address 2 \'' . $address2 . '\' for addrcode \'' . $custAddrRec->addrcode . '\' addritem \'' . $custAddrRec->addritem . '\'');
		}
		*/

		$custStores = $this->getStoresForDataId('US', $custAddrRec->cust);

		if (count($custStores) == 0) {
			generalLib::warn('Cannot process addresses for customer code \'' . $custAddrRec->cust . '\' - no store data records found');
			$rc = false;
		}

		foreach($custStores as $custStoreId) {
			$customer = magentoCust::getMagCustomer($custAddrRec, 'cust', 'cust', 'cust_magento_id', false);

			if ($customer) {
				$custAddrSettings = new stdClass();
				$custAddrSettings->defaultAddr = false;
				$gotDefaultBilling = false;
				$gotDefaultShipping = false;

				if ($custAddrRec->maincontact == $custAddrRec->addritem) {
					$gotDefaultBilling = true;
				}

				if ($custAddrRec->cdefdeladd == $custAddrRec->addritem) {
					$gotDefaultShipping = true;
				}

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
			} else {
				generalLib::warn('Cannot process addresses for customer code \'' . $custAddrRec->cust . '\' - customer not found');
				$rc = false;
			}
		}

		return $rc;
	}


	//###########################
	public function processUsersTable($actionType, $custUserRec) {
		$rc = true;

		generalLib::mesg('');
		generalLib::mesg('====');

		//die();
		//echo "custreci:\n";
		print_r($custUserRec);
		//die();

		$custCode = $custUserRec->cust;

		$custStores = $this->getStoresForDataId('US', $custUserRec->cust);

		if (count($custStores) == 0) {
			generalLib::warn('Cannot process cust code \'' . $custUserRec->cust . '\' - no store data records');
			$rc = false;
		}

		foreach($custStores as $custStoreId => $custActionType) {
			$updCust = false;

			$customer = magentoCust::getMagCustomer($custUserRec, 'cust', 'cust', 'cust_magento', false);
			//echo "customer:\n";
			//print_r($customer->getData());
			//die();

			if ($actionType == 'D') {
				$rc = magentoCust::processCustDeletion($custStoreId,  $custUserRec->cust, $customer);

				if ($rc) {
					continue;
				} else {
					break;
				}
			}

			if (!$customer) {
				$customer = $this->objectManager->create('Magento\Customer\Model\Customer');
				//$customerFactory = $this->objectManager->get('\Magento\Customer\Model\CustomerFactory');
				//$customer = $customerFactory->create();
				$customer->setStoreId( $custStoreId );
				$customer->setCreatedIn( $this->procConfig->storeDets[ $custStoreId ]->storeName );
				$customer->setWebsiteId($this->procConfig->storeDets[ $custStoreId ]->websiteId);
				$custId = '';
				$custCode = $custUserRec->cust;

				generalLib::mesg('Creating customer \'' . $custCode . '\' (' . $custUserRec->email . ') for store id \'' . $custStoreId . '\'');
			} else {
				$custId = $customer->getId();
				$custCode = $customer->getCust();

				generalLib::mesg('Updating customer \'' . $custCode . '\' (' . $custUserRec->email . ') for store id \'' . $custStoreId . '\'');
			}

			//die();
			$customer->setPasswordHash($custUserRec->epassword);
			generalLib::mesg('Setting password hash to \'' . $custUserRec->epassword . '\'');
			$updCust = true;

			$oldCaplType = $customer->getCapltype();

			$query = 'SELECT capltype FROM waddresses WHERE addrcode = "' . $custUserRec->cust . '" AND addritem = "999"';

			$result = $this->dbObj->absDbQuery($query);

			while($queryResult = $this->dbObj->absDbFetchArray($result, PDO::FETCH_ASSOC)) {
				$queryResultObj = json_decode( json_encode($queryResult), FALSE);

				$newCaplType = $queryResult['capltype'];

				if ($oldCaplType != $newCaplType) {
					$customer->setCapltype($newCaplType);
					$updCust = true;
				}
			}


			$updAttrs = magentoImport::processMagAttrs($customer, $custUserRec, $this->procConfig->custMagAttrs);

			if ($updAttrs && (!$updCust)) {
				$updCust = true;
			}


			$updAttrs = magentoImport::processCustomAttrs($this, $customer, $custUserRec, $this->procConfig->custCustomAttrs);

			if ($updAttrs && (!$updCust)) {
				$updCust = true;
			}

			//echo 'attr set id = ' . \Magento\Customer\Api\CustomerMetadataInterface::ATTRIBUTE_SET_ID_CUSTOMER . "\n";
			//die();

			//IMPORTANT: WITHOUT THIS LINE, UPDATES TO THE CUSTOMER INFORMATION RESULTS IN THE LOSS OF CUSTOM ATTRIBUTE VALUES
			//SEE: https://github.com/magento/magento2/issues/12479
			$customer->setAttributeSetId(\Magento\Customer\Api\CustomerMetadataInterface::ATTRIBUTE_SET_ID_CUSTOMER);

			//$customerData = $customer->getDataModel();
			//$customer->setCustomAttribute('dhl_permission', '1');
			//$customerRepos = $this->objectManager->get('\Magento\Customer\Api\CustomerRepositoryInterface');
			//$customerRepos2 = $customerRepos->create();
			//$customerRepos->save($customer);
			//die();

			//echo 'customer id = ' . $customer->getId() . "\n";
			//die();
			//$customer->setFirstname('John');
			//print_r($customer->getData());
			//die();
			//die();

			/*
			//DOES NOT WORK
			$customerModel  = $this->objectManager->create('Magento\Customer\Model\Customer');
			$customer->setCustomAttribute('cust', 'JDL');
			$customer->setCustomAttribute('dhl_permission', '1');
			$customer->setFirstname('Johnny');

			print_r($customer->getData());
			$customerModel->updateData($customer);
			die();
			*/

			/*
			//THIS WORKS
			$customerRepository = $this->objectManager->get('Magento\Customer\Api\CustomerRepositoryInterface');
			//$customerData = $customer;
			$customerData = $customerRepository->getById( $customer->getId() );
			$customerData->setCustomAttribute('cust', 'JDL');
			$customerData->setCustomAttribute('dhl_permission', '1');
			$customerData->setCustomAttribute('b2c', '1');
			$customerData->setFirstname('Johnny');
			$customerRepository->save( $customerData );
			//$customer->save();
			die();
			*/

			/*
			$customerData = $customer->getDataModel();
			$customerData->setCustomAttribute('dhl_permission', '0');
			$customer->updateData($customerData);
			$customerFactory = $this->objectManager->get('\Magento\Customer\Model\ResourceModel\CustomerFactory');
			$customerResource = $customerFactory->create();
			$customerResource->saveAttribute($customer, 'dhl_permission');

			die();
			*/

			$newMdValueArr = array();

			if ( isset ( $custUserRec->allowed_selling_brand ) ) {

				$allowedSellingBrandArr = explode(',', $custUserRec->allowed_selling_brand);

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
						generalLib::error('Could not get ID for customer \'' . $custCode . '\' (' . $custUserRec->email . ')');
						$rc = false;
					}
				}
			} else {
				generalLib::mesg('No changes to apply');
			}


			if ($rc && ( ($actionType == 'U' && $custUserRec->cust_magento_id != $custId) || $actionType == 'D') ) {
				$magIdUpdRec = new stdclass();
				$magIdUpdRec->dbObj = $this->dbObj;
				$magIdUpdRec->extTable = 'users';
				$magIdUpdRec->magentoIdFldName = 'cust_magento_id';
				$magIdUpdRec->magentoId = $custId;
				$magIdUpdRec->idFldName = 'userid';
				$magIdUpdRec->id = $custUserRec->userid;
				$magIdUpdRec->idType = 'TXT';

				$rc = magentoDbFuncs::setTblMagId($magIdUpdRec);
			}
		}

		//die();

		return $rc;
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
                        	->addFieldToFilter('xpromoto', array('to' => $chkDate));

		foreach ($xpromoColl as $expiredPromo) {
			//print_r($expiredPromo->getData());

			generalLib::mesg('');
			generalLib::mesg('====');

			$expiredXpromoCode = $expiredPromo->getXpromocode();

			$xpromopartColl = $this->objectManager->create( $this->procConfig->xpromoPartModel )
                        ->getCollection()
                        ->addFieldToFilter('xpromocode', array('eq' => $expiredXpromoCode));

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

		$this->dbObj = new pdoDb($this->procConfig->dbConfig);

		if (!$this->dbObj->absDbConnect()) {
			return;
		}

		//print_r($this->procConfig->storeDets);

		foreach ($this->procConfig->storeDets as $storeId => $storeDet) {
			//echo 'storeId = ' . $storeId . "\n";

			$mergedOrders = $this->objectManager->create('\Magento\Sales\Model\Order')->getCollection()->addFieldToFilter('entity_id',-1);

			try {
				$orders = $this->objectManager->create('\Magento\Sales\Model\Order')->getCollection()
						->addAttributeToFilter('store_id', $storeId)
						->addAttributeToFilter('status', 'processing')
						->addAttributeToFilter('order_export_status', array('null' => true))
						->addAttributeToFilter('created_at', array('to' => $dateTimeUTC10MinsAgo))
						//->addAttributeToFilter('increment_id', array('in' => array('2000052515')))
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
						->addAttributeToFilter('status', 'pending')
						->addAttributeToFilter('order_export_status', array('null' => true))
						->addAttributeToFilter('created_at', array('to' => $dateTimeUTC5MinsAgo))
						//->addAttributeToFilter('increment_id', array('in' => array('2000052539')))
						->join(
        					array('payment' => 'sales_order_payment'),
        					'main_table.entity_id=payment.parent_id',
        					array('payment_method' => 'payment.method')
    						);

			}

			catch (Exception $ex) {
				generalLib::error($ex->getMessage());
			}


			/*
			$orders->getSelect()->join(
    			array('p' => $orders->getResource()->getTable('sales/order_payment')),
    				'p.parent_id = main_table.entity_id',
    			array()
			);
			*/

			//$orders->addFieldToFilter('method', array( 'in' => array('purchaseorder', 'checkmo', 'banktransfer') ) );

			$orders->addFieldToFilter('payment.method', array( 'in' => array('purchaseorder', 'checkmo', 'banktransfer') ) );

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
				$savedItems = false;
				$ordHdrFldNames = '';
				$ordHdrFldValues = '';

				$orderId = $order->getIncrementId();

				//THERE HAS BEEN AN INTERMITTANT ISSUE WHERE ONCE THE ORDER IS SAVED, THE MAGENTO ORDER STATUS SOMETIMES FAILS WITH A 'The requested Payment Method is not available.' ERROR
				//THIS RESULTS IN THE ORDER BEING RE-PROCESSED AND RE-CREATED AGAIN ON THE NEXT ORDER EXPORT RUN
				//WHETHER YOU CREATE THE ORDER OR UPDATE THE STATUS FIRST, IF AN ERROR OCCURS A ROLLBACK WILL BE REQUIRED OF ONE OR THE OTHER
				//INSTEAD, WHEN THE ORDER IS RE-PROCESSED, A CHECK IS MADE TO SEE IF THE ORDER IS ALREADY PRESENT IN THE ORDERHEADER TABLE
				//IF PRESENT, AN ATTEMPT IS JUST MADE TO UPDATE THE MAGENTO ORDER STATUS AGAIN
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


				$this->dbObj->absDbBeginTransaction();
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

				$customer = $this->objectManager->create('Magento\Customer\Model\Customer')->load($customerId);

				magentoDbFuncs::buildInsertStmt('cust', $customer->getCust(), 'TXT', $ordHdrFldNames, $ordHdrFldValues);

				//die();

				magentoDbFuncs::buildInsertStmt('deladd', $delAdd, 'INT', $ordHdrFldNames, $ordHdrFldValues);
				magentoDbFuncs::buildInsertStmt('cordernum', $order->getCustomerOrderNo(), 'TXT', $ordHdrFldNames, $ordHdrFldValues);
				magentoDbFuncs::buildInsertStmt('mordernum', $orderId, 'TXT', $ordHdrFldNames, $ordHdrFldValues);


				$odate = $order->getCreatedAt();
				//generalLib::mesg('order date created at = ' . $odate);

				$dateTimeSplitPos = strpos($odate, ' ');

				if ($dateTimeSplitPos !== false) {
					$odate = substr($odate, 0, ( strlen($odate) - ( $dateTimeSplitPos - 1) ) );
					//generalLib::mesg('order date substr = ' . $odate);
				}

				$odate .= ' ' . date('H:i:s', magentoGeneral::getLocalTimeStamp( $order->getCreatedAt() ) );
				//generalLib::mesg('order date = ' . $odate);

				magentoDbFuncs::buildInsertStmt('odate', $odate, 'DAT', $ordHdrFldNames, $ordHdrFldValues);
				//magentoDbFuncs::buildInsertStmt('odate', date('Y-m-d H:i:s', magentoGeneral::getLocalTimeStamp($order->getCreatedAt())), 'DAT', $ordHdrFldNames, $ordHdrFldValues);

				magentoDbFuncs::buildInsertStmt('status', 'WEB', 'TXT', $ordHdrFldNames, $ordHdrFldValues);
				magentoDbFuncs::buildInsertStmt('cnotes', $order->getOnestepcheckoutCustomercomment(), 'TXT', $ordHdrFldNames, $ordHdrFldValues);

				$giftMessageId = $order->getGiftMessageId();

				if ( is_numeric( $giftMessageId ) ) {
					$giftMesg = $this->objectManager->create('\Magento\GiftMessage\Model\Message')->load( $giftMessageId );

					magentoDbFuncs::buildInsertStmt('giftnotefrom', $giftMesg->getSender(), 'TXT', $ordHdrFldNames, $ordHdrFldValues);
					magentoDbFuncs::buildInsertStmt('giftnoteto', $giftMesg->getRecipient(), 'TXT', $ordHdrFldNames, $ordHdrFldValues);
					magentoDbFuncs::buildInsertStmt('giftnote', $giftMesg->getMessage(), 'TXT', $ordHdrFldNames, $ordHdrFldValues);
				}

				magentoDbFuncs::buildInsertStmt('b2c', $b2c, 'TXT', $ordHdrFldNames, $ordHdrFldValues);

				if ($b2c == 'Y') {
					$addrName = trim($shippingAddr->getPrefix());

					if (!empty($addrName)) {
						$addrName .= ' ';
					}

					$addrName .= trim($shippingAddr->getFirstname()) . ' ' . trim($shippingAddr->getLastname());

					$streetArr = $shippingAddr->getStreet();

					magentoDbFuncs::buildInsertStmt('b2caddress', $addrName, 'TXT', $ordHdrFldNames, $ordHdrFldValues);
					magentoDbFuncs::buildInsertStmt('b2caddress1', $streetArr[0], 'TXT', $ordHdrFldNames, $ordHdrFldValues);

					if ( isset($streetArr[1] ) ) {
						magentoDbFuncs::buildInsertStmt('b2caddress2', $streetArr[1], 'TXT', $ordHdrFldNames, $ordHdrFldValues);
					}

					magentoDbFuncs::buildInsertStmt('b2caddress3', $shippingAddr->getCity(), 'TXT', $ordHdrFldNames, $ordHdrFldValues);
					magentoDbFuncs::buildInsertStmt('b2caddress4', $shippingAddr->getRegion(), 'TXT', $ordHdrFldNames, $ordHdrFldValues);

					$countryCode = $shippingAddr->getCountry();

					if ($countryCode != '') {
						$country = Mage::getModel('directory/country')->loadByCode($countryCode);
						$countryName = $country->getName();
					} else {
						$countryName = '';
					}

					magentoDbFuncs::buildInsertStmt('b2caddress5', $countryName, 'TXT', $ordHdrFldNames, $ordHdrFldValues);
					magentoDbFuncs::buildInsertStmt('b2cpostcode', $shippingAddr->getPostcode(), 'TXT', $ordHdrFldNames, $ordHdrFldValues);
					magentoDbFuncs::buildInsertStmt('b2ctelephone', $shippingAddr->getTelephone(), 'TXT', $ordHdrFldNames, $ordHdrFldValues);
					magentoDbFuncs::buildInsertStmt('b2cspecialinstruction', $order->getSpecialDeliveryInstructions(), 'TXT', $ordHdrFldNames, $ordHdrFldValues);
					magentoDbFuncs::buildInsertStmt('b2cemail', $order->getCustomerEmail(), 'TXT', $ordHdrFldNames, $ordHdrFldValues);
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

				magentoDbFuncs::buildInsertStmt('paymenttype', $paymentType, 'TXT', $ordHdrFldNames, $ordHdrFldValues);

				if ( ( $paymentMethod == 'worldpay' ) || ( $paymentMethod == 'worldpay_cc') ) {
					magentoDbFuncs::buildInsertStmt('creditcardtype', $payment->getCcType(), 'TXT', $ordHdrFldNames, $ordHdrFldValues);
					magentoDbFuncs::buildInsertStmt('creditcardtransid', $payment->getLastTransId(), 'TXT', $ordHdrFldNames, $ordHdrFldValues);
					magentoDbFuncs::buildInsertStmt('creditcardavs', $payment->getCcAvsStatus(), 'TXT', $ordHdrFldNames, $ordHdrFldValues);
				}

				magentoDbFuncs::buildInsertStmt('actiontype', 'U', 'TXT', $ordHdrFldNames, $ordHdrFldValues);

				$ordHdrInsStmt = magentoDbFuncs::finishInsertStmt('orderheader', $ordHdrFldNames, $ordHdrFldValues);
				//generalLib::mesg('ordHdrInsStmt = ' . $ordHdrInsStmt);
				//die();

				//echo 'ordHdrInsStmt = ' . $ordHdrInsStmt . "\n";

				try {
					$this->dbObj->absDbQuery($ordHdrInsStmt);
					$ordHdrId = $this->dbObj->absLastInsertId();
				}

				catch (Exception $ex) {
					generalLib::error($ex->getMessage());
					$this->dbObj->absDbRollbackTransaction();
					continue;
				}

				///NOTE: NEED TO AD AN EXTRA ORDER LINE FOR SHIPPING

				//NOTE: NEED A SHIPMENT TABLE, WHICH WILL BE USED TO INDICATE WHICH ITEMS HAVE BEEN DELIVERED AND A TRACKING REF TO CREATE A SHIPMENT
				//WHEN ALL ITEMS ARE SHIPPED THE ORDER WILL BE SET TO COMPLETE

				$orderItems = $order->getAllItems();

				$itemNum = 1;
				$savedItems = false;

				foreach($orderItems AS $orderItem) {
					$ordItmFldNames = '';
					$ordItmFldValues = '';
					//print_r($orderItem->getData());
					//continue;


					$taxPerc = $orderItem->getTaxPercent();

					//echo 'taxPerc = ' . $taxPerc . "\n";

					if ($taxPerc != 0) {
						$vatCode = 'S';
					} else {
						$vatCode = 'Z';
					}

					//echo 'order id = ' . $order->getIncrementId() . "\n";
					//echo 'order item = ' . $orderItem->getSku() . "\n";

					if ($orderItem->getActiveDiscounts() != '') {
						$nunitPrice = $orderItem->getPrice();
						$discPerc = 0;
						$punitPrice = $nunitPrice;
					} else {
						$orderItmPrice = $orderItem->getOriginalPrice();

						if ($orderItmPrice == 0) {
							$orderItmPrice = $orderItem->getPrice();
						}

						//echo 'order item price = ' . $orderItmPrice . "\n";

						$discAmt = $orderItem->getDiscountAmount();
						$discPerc = ( $discAmt / $orderItmPrice ) * 100;
						//echo 'discPerc = ' . $discPerc . "\n";
						$nunitPrice = $orderItmPrice - $discAmt;
						$punitPrice = 0;
					}

					//continue;


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

					magentoDbFuncs::buildInsertStmt('id', $ordHdrId, 'INT', $ordItmFldNames, $ordItmFldValues);
					magentoDbFuncs::buildInsertStmt('item', $itemNum, 'INT', $ordItmFldNames, $ordItmFldValues);
					magentoDbFuncs::buildInsertStmt('part', $partNum, 'TXT', $ordItmFldNames, $ordItmFldValues);
					magentoDbFuncs::buildInsertStmt('descr', $orderItem->getName(), 'TXT', $ordItmFldNames, $ordItmFldValues);
					magentoDbFuncs::buildInsertStmt('qty', $orderItem->getQtyOrdered(), 'INT', $ordItmFldNames, $ordItmFldValues);
					magentoDbFuncs::buildInsertStmt('unitprice', $orderItem->getOriginalPrice(), 'DEC', $ordItmFldNames, $ordItmFldValues);
					magentoDbFuncs::buildInsertStmt('discount', $discPerc, 'DEC', $ordItmFldNames, $ordItmFldValues);
					magentoDbFuncs::buildInsertStmt('nunitprice', $nunitPrice, 'DEC', $ordItmFldNames, $ordItmFldValues);
					magentoDbFuncs::buildInsertStmt('value', $orderItem->getRowTotal(), 'DEC', $ordItmFldNames, $ordItmFldValues);
					magentoDbFuncs::buildInsertStmt('vatcode', $vatCode, 'TXT', $ordItmFldNames, $ordItmFldValues);
					magentoDbFuncs::buildInsertStmt('vatrate', $taxPerc, 'DEC', $ordItmFldNames, $ordItmFldValues);
					magentoDbFuncs::buildInsertStmt('vatvalue', $orderItem->getTaxAmount(), 'DEC', $ordItmFldNames, $ordItmFldValues);
					magentoDbFuncs::buildInsertStmt('goodstotal', $orderItem->getRowTotalInclTax(), 'DEC', $ordItmFldNames, $ordItmFldValues);
					magentoDbFuncs::buildInsertStmt('currency', $order->getOrderCurrencyCode(), 'TXT', $ordItmFldNames, $ordItmFldValues);
					magentoDbFuncs::buildInsertStmt('currate', $order->getStoreToOrderRate(), 'DEC', $ordItmFldNames, $ordItmFldValues);

					if ($note != '') {
						magentoDbFuncs::buildInsertStmt('note', $note, 'TXT', $ordItmFldNames, $ordItmFldValues);
					}

					magentoDbFuncs::buildInsertStmt('punitprice', $punitPrice, 'DEC', $ordItmFldNames, $ordItmFldValues);
					magentoDbFuncs::buildInsertStmt('actiontype', 'U', 'TXT', $ordItmFldNames, $ordItmFldValues);

					$itemNum++;

					$ordItmInsStmt = magentoDbFuncs::finishInsertStmt('orderitem', $ordItmFldNames, $ordItmFldValues);

					//echo 'ordItmInsStmt = ' . $ordItmInsStmt . "\n";

					$result = $this->dbObj->absDbQuery($ordItmInsStmt);

					if ($result) {
						$savedItems = true;
					} else {
						$this->dbObj->absDbRollbackTransaction();
						$savedItems = false;
						break;
					}
				}

				//die();

				if (!$savedItems) {
					continue;
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

				$ordItmFldNames = '';
				$ordItmFldValues = '';

				magentoDbFuncs::buildInsertStmt('id', $ordHdrId, 'INT', $ordItmFldNames, $ordItmFldValues);
				magentoDbFuncs::buildInsertStmt('item', $itemNum, 'INT', $ordItmFldNames, $ordItmFldValues);
				magentoDbFuncs::buildInsertStmt('part', $shipPartNum, 'TXT', $ordItmFldNames, $ordItmFldValues);
				magentoDbFuncs::buildInsertStmt('descr', $shipDesc, 'TXT', $ordItmFldNames, $ordItmFldValues);
				magentoDbFuncs::buildInsertStmt('qty', 1, 'INT', $ordItmFldNames, $ordItmFldValues);
				magentoDbFuncs::buildInsertStmt('unitprice', $order->getShippingAmount(), 'DEC', $ordItmFldNames, $ordItmFldValues);
				magentoDbFuncs::buildInsertStmt('discount', '0', 'DEC', $ordItmFldNames, $ordItmFldValues);
				magentoDbFuncs::buildInsertStmt('nunitprice', $order->getShippingAmount(), 'DEC', $ordItmFldNames, $ordItmFldValues);
				magentoDbFuncs::buildInsertStmt('value', $order->getShippingAmount(), 'DEC', $ordItmFldNames, $ordItmFldValues);
				magentoDbFuncs::buildInsertStmt('vatcode', $vatCode, 'TXT', $ordItmFldNames, $ordItmFldValues);
				magentoDbFuncs::buildInsertStmt('vatrate', $taxPerc, 'DEC', $ordItmFldNames, $ordItmFldValues);
				magentoDbFuncs::buildInsertStmt('vatvalue', $order->getShippingTaxAmount(), 'DEC', $ordItmFldNames, $ordItmFldValues);
				magentoDbFuncs::buildInsertStmt('goodstotal', $order->getShippingInclTax(), 'DEC', $ordItmFldNames, $ordItmFldValues);
				magentoDbFuncs::buildInsertStmt('currency', $order->getOrderCurrencyCode(), 'TXT', $ordItmFldNames, $ordItmFldValues);
				magentoDbFuncs::buildInsertStmt('currate', $order->getStoreToOrderRate(), 'DEC', $ordItmFldNames, $ordItmFldValues);
				magentoDbFuncs::buildInsertStmt('punitprice', '0', 'DEC', $ordItmFldNames, $ordItmFldValues);
				magentoDbFuncs::buildInsertStmt('actiontype', 'U', 'TXT', $ordItmFldNames, $ordItmFldValues);


				$ordItmInsStmt = magentoDbFuncs::finishInsertStmt('orderitem', $ordItmFldNames, $ordItmFldValues);

				//echo 'ordItmInsStmt = ' . $ordItmInsStmt . "\n";

				$result = $this->dbObj->absDbQuery($ordItmInsStmt);

				if ($result) {
					$savedItems = true;
				} else {
					$savedItems = false;
				}


				if ($savedItems) {
					try {
						generalLib::mesg('Saving dar order auto increment id \'' . $ordHdrId . '\' for magento order id \'' . $orderId . '\'');
						$this->dbObj->absDbCommitTransaction();
						$savedItems = true;
					}

					catch(Exception $ex) {
						generalLib::error($ex->getMessage());
						generalLib::mesg('Order not saved');
						$this->dbObj->absDbRollbackTransaction();
						$savedItems = false;
					}

					if ($savedItems) {
						$ordStatus = 'order_sent';

						$rc = magentoGeneral::updateOrderStatus($orderId, '', $ordStatus, true, 'DAR DB Processor');

						if ($rc) {
							magentoGeneral::updateOrderExportStatus($orderId, $ordStatus);
						}
					}
				} else {
					$this->dbObj->absDbRollbackTransaction();
				}

				//die();
			}

		}
			//die();
	}
}

?>
